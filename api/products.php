<?php
/**
 * Products API — full CRUD + location-aware stock
 */
require __DIR__.'/../includes/db.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}
startSession();
$method=$_SERVER['REQUEST_METHOD'];
$pdo=getDB();

// Ensure sku_prefix column exists
try { $pdo->exec("ALTER TABLE categories ADD COLUMN sku_prefix VARCHAR(10) DEFAULT NULL AFTER name"); } catch (Exception $e) {}

// Ensure list_price column exists on products (vendor's list/rate-card price before formula)
try { $pdo->exec("ALTER TABLE products ADD COLUMN list_price DECIMAL(12,2) DEFAULT NULL AFTER cost"); } catch (Exception $e) {}

// Ensure procurement_active column exists (1 = include in procurement, 0 = skip)
try { $pdo->exec("ALTER TABLE products ADD COLUMN procurement_active TINYINT(1) NOT NULL DEFAULT 1 AFTER combo"); } catch (Exception $e) {}

if ($method==='GET') {
    if (isset($_GET['categories'])) { jsonList($pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category<>'' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN)); }
    if (isset($_GET['brands']))     { jsonList($pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand<>'' ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN)); }

    // ── Duplicate detection ───────────────────────────────
    if (isset($_GET['duplicates'])) {
        $products = $pdo->query("SELECT id, name, sku, category, brand, cost, sell, stock, COALESCE(vendor_id,0) AS vendor_id FROM products ORDER BY name")->fetchAll();

        // By name+brand — normalise: lowercase, strip spaces/punctuation
        // Same name across different brands is NOT a duplicate (e.g. "Flower Pots" from Queen vs Mariammal)
        $nameGroups = [];
        foreach ($products as $p) {
            $normName  = preg_replace('/[\s\-_\.]+/', '', strtolower($p['name']));
            $normBrand = strtolower(trim($p['brand'] ?? ''));
            $key       = $normName . '|' . $normBrand;
            $nameGroups[$key][] = $p;
        }
        $byName = [];
        foreach ($nameGroups as $key => $group) {
            if (count($group) > 1) {
                $byName[] = ['key' => $group[0]['name'], 'products' => $group];
            }
        }

        // By SKU+Vendor+Brand — flag same SKU from same vendor AND same brand (true duplicate)
        // Same SKU across different vendors OR different brands is expected and NOT flagged.
        // Also: if only first 4 digits of SKU match but brand differs — NOT a duplicate.
        $skuVendorGroups = [];
        foreach ($products as $p) {
            if (empty(trim($p['sku']))) continue;
            $sku   = strtolower(trim($p['sku']));
            $brand = strtolower(trim($p['brand'] ?? ''));
            $key   = $sku . '|' . (int)$p['vendor_id'] . '|' . $brand;
            $skuVendorGroups[$key][] = $p;
        }
        $bySku = [];
        foreach ($skuVendorGroups as $key => $group) {
            if (count($group) > 1) {
                $skuPart = explode('|', $key)[0];
                $bySku[] = ['key' => $skuPart, 'products' => $group];
            }
        }

        // All — union of both
        $seen = [];
        $all  = [];
        foreach (array_merge($byName, $bySku) as $group) {
            $ids = implode(',', array_column($group['products'], 'id'));
            if (!in_array($ids, $seen)) { $seen[] = $ids; $all[] = $group; }
        }

        jsonOk(['name' => $byName, 'sku' => $bySku, 'all' => $all]);
    }

    // Fetch all locations for attaching per-location stock to each product
    $allLocs = $pdo->query("SELECT id, name FROM locations ORDER BY is_default DESC, name")->fetchAll();

    $locId = !empty($_GET['location_id']) ? (int)$_GET['location_id'] : null;

    if (!empty($_GET['id'])) {
        $s=$pdo->prepare("SELECT p.*,v.name AS vendor_name,c.sku_prefix AS category_sku_prefix FROM products p LEFT JOIN vendors v ON v.id=p.vendor_id LEFT JOIN categories c ON c.name=p.category WHERE p.id=?");
        $s->execute([(int)$_GET['id']]); $row=$s->fetch(); if (!$row) jsonError('Not found',404);
        $row['margin']=$row['sell']>0?round((($row['sell']-$row['cost'])/$row['sell'])*100,1):0;
        $row['location_stocks'] = getLocationStocks($pdo, (int)$_GET['id'], $allLocs);
        jsonOk($row);
    }

    $where=['1=1']; $params=[];
    if (!empty($_GET['q'])) { $like='%'.$_GET['q'].'%'; $where[]='(p.name LIKE ? OR p.sku LIKE ? OR p.category LIKE ? OR p.brand LIKE ?)'; $params=array_merge($params,[$like,$like,$like,$like]); }
    if (!empty($_GET['category'])) { $where[]='p.category=?'; $params[]=$_GET['category']; }
    if (!empty($_GET['brand']))    { $where[]='p.brand=?';    $params[]=$_GET['brand']; }
    if (!empty($_GET['vendor_id'])) { $where[]='p.vendor_id=?'; $params[]=(int)$_GET['vendor_id']; }
    if (isset($_GET['procurement_active']) && $_GET['procurement_active']!=='') {
        $where[]='p.procurement_active=?'; $params[]=(int)$_GET['procurement_active'];
    }
    if (isset($_GET['combo_filter']) && $_GET['combo_filter']!=='') {
        $where[]='p.combo=?'; $params[]=(int)$_GET['combo_filter'];
    }
    if (!empty($_GET['stock_filter'])) {
        $sf = $_GET['stock_filter'];
        if ($sf==='no_sku') {
            $where[]="(p.sku IS NULL OR TRIM(p.sku)='')";
        } elseif ($sf==='on_order') {
            $where[]="EXISTS(SELECT 1 FROM purchase_order_items poi
                JOIN purchase_orders po ON po.id=poi.po_id
                WHERE poi.product_id=p.id
                AND po.status IN ('draft','sent','partial')
                AND poi.qty_ordered > COALESCE(poi.qty_received,0))";
        } elseif ($locId) {
            if($sf==='low')     $where[]="EXISTS(SELECT 1 FROM product_locations pl WHERE pl.product_id=p.id AND pl.location_id=$locId AND pl.stock>0 AND pl.stock<=pl.min_stock AND pl.min_stock>0)";
            elseif($sf==='out') $where[]="EXISTS(SELECT 1 FROM product_locations pl WHERE pl.product_id=p.id AND pl.location_id=$locId AND pl.stock<=0)";
            elseif($sf==='ok')  $where[]="EXISTS(SELECT 1 FROM product_locations pl WHERE pl.product_id=p.id AND pl.location_id=$locId AND pl.stock>pl.min_stock)";
        } else {
            if($sf==='low')     $where[]='p.stock>0 AND p.stock<=p.min_stock AND p.min_stock>0';
            elseif($sf==='out') $where[]='p.stock<=0';
            elseif($sf==='ok')  $where[]='p.stock>p.min_stock';
        }
    }

    $s=$pdo->prepare("SELECT p.*,CAST(p.combo AS UNSIGNED) AS combo,CAST(p.procurement_active AS UNSIGNED) AS procurement_active,v.name AS vendor_name,c.sku_prefix AS category_sku_prefix FROM products p LEFT JOIN vendors v ON v.id=p.vendor_id LEFT JOIN categories c ON c.name=p.category WHERE ".implode(' AND ',$where)." ORDER BY CAST(p.item_code AS UNSIGNED), p.sku, p.name");
    $s->execute($params); $rows=$s->fetchAll();

    // Attach per-location stocks + compute effective stock/margin
    foreach ($rows as &$r) {
        $r['margin']=$r['sell']>0?round((($r['sell']-$r['cost'])/$r['sell'])*100,1):0;
        $r['location_stocks'] = getLocationStocks($pdo, (int)$r['id'], $allLocs);
        if ($locId) {
            // Override stock/min_stock with location-specific values for display
            $ls = array_filter($r['location_stocks'], function($l) use ($locId){ return $l['location_id']==$locId; });
            $ls = reset($ls);
            $r['display_stock']     = $ls ? (int)$ls['stock']     : 0;
            $r['display_min_stock'] = $ls ? (int)$ls['min_stock'] : (int)$r['min_stock'];
        } else {
            $r['display_stock']     = (int)$r['stock'];
            $r['display_min_stock'] = (int)$r['min_stock'];
        }
    }
    jsonList($rows);
}

function getLocationStocks(PDO $pdo, int $productId, array $allLocs): array {
    $rows = $pdo->prepare("SELECT pl.location_id, l.name AS location_name, pl.stock, pl.min_stock
                           FROM product_locations pl JOIN locations l ON l.id=pl.location_id
                           WHERE pl.product_id=? ORDER BY l.is_default DESC, l.name");
    $rows->execute([$productId]);
    $found = $rows->fetchAll();
    // Ensure every location appears even if no row exists
    $map = [];
    foreach ($found as $f) $map[$f['location_id']] = $f;
    $result = [];
    foreach ($allLocs as $loc) {
        $result[] = $map[$loc['id']] ?? ['location_id'=>$loc['id'],'location_name'=>$loc['name'],'stock'=>0,'min_stock'=>0];
    }
    return $result;
}

// Image upload handled as multipart POST
if ($method==='POST' && isset($_FILES['image'])) {
    requireAuth();
    $pid=(int)($_POST['product_id']??0); if (!$pid) jsonError('product_id required');
    $file=$_FILES['image'];
    if ($file['error']!==UPLOAD_ERR_OK) jsonError('Upload error');
    $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
    if (!in_array($ext,['jpg','jpeg','png','webp'])) jsonError('Only jpg/png/webp allowed');
    $dir=__DIR__.'/../assets/images/products/';
    if (!is_dir($dir)) mkdir($dir,0755,true);
    $fname="product_{$pid}_".time().".$ext";
    move_uploaded_file($file['tmp_name'],$dir.$fname);
    $pdo->prepare("UPDATE products SET image=? WHERE id=?")->execute([$fname,$pid]);
    jsonOk(['image'=>$fname],'Image uploaded');
}

if ($method==='POST') {
    requireAuth(); $b=getBody(); requireFields($b,['name','cost']);
    $sku = trim($b['sku']??'');
    $itemCode = ($sku !== '' && preg_match('/^(\d+)/', $sku, $icm)) ? (int)$icm[1] : null;
    $pdo->prepare("INSERT INTO products (name,sku,item_code,brand,category,vendor_id,cost,list_price,sell,wholesale_price,stock,min_stock,unit,description,case_content,box_content,landing_cost,combo,procurement_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([trim($b['name']),$sku,$itemCode,trim($b['brand']??''),trim($b['category']??''),!empty($b['vendor_id'])?(int)$b['vendor_id']:null,(float)$b['cost'],isset($b['list_price'])&&$b['list_price']!==''&&$b['list_price']!==null?(float)$b['list_price']:null,(float)$b['sell'],isset($b['wholesale_price'])&&$b['wholesale_price']!==''&&$b['wholesale_price']!==null?(float)$b['wholesale_price']:null,(int)($b['stock']??0),(int)($b['min_stock']??0),trim($b['unit']??'Box'),trim($b['description']??''),isset($b['case_content'])&&$b['case_content']!==''&&$b['case_content']!==null?(int)$b['case_content']:null,isset($b['box_content'])&&$b['box_content']!=='' ? trim((string)$b['box_content']):null,isset($b['landing_cost'])&&$b['landing_cost']!==''&&$b['landing_cost']!==null?(float)$b['landing_cost']:null,isset($b['combo'])?(int)(bool)$b['combo']:0,isset($b['procurement_active'])?(int)(bool)$b['procurement_active']:1]);
    $id=(int)$pdo->lastInsertId();
    // Seed product_locations
    $locs=$pdo->query("SELECT id,is_default FROM locations")->fetchAll();
    $defId=null; foreach ($locs as $l) if ($l['is_default']) $defId=$l['id'];
    if (!$defId && $locs) $defId=$locs[0]['id'];
    foreach ($locs as $l) {
        $s=$l['id']==$defId?(int)($b['stock']??0):0;
        $ms=$l['id']==$defId?(int)($b['min_stock']??0):0;
        $pdo->exec("INSERT IGNORE INTO product_locations (product_id,location_id,stock,min_stock) VALUES ($id,{$l['id']},$s,$ms)");
    }
    auditLog($pdo,'create_product','product',$id,$b['name']);
    $p=$pdo->query("SELECT p.*,v.name AS vendor_name FROM products p LEFT JOIN vendors v ON v.id=p.vendor_id WHERE p.id=$id")->fetch();
    jsonOk($p,'Product created');
}
if ($method==='PUT') {
    requireAuth(); $b=getBody(); requireFields($b,['id']);
    // Bulk single-field update
    if (!empty($b['_bulk'])) {
        $id=(int)$b['id'];
        if (isset($b['category']))  { $pdo->prepare("UPDATE products SET category=? WHERE id=?")->execute([trim($b['category']),$id]); }
        elseif (isset($b['brand'])) { $pdo->prepare("UPDATE products SET brand=? WHERE id=?")->execute([trim($b['brand']),$id]); }
        elseif (array_key_exists('vendor_id',$b)) {
            $vid = $b['vendor_id']!==null && $b['vendor_id']!=='' ? (int)$b['vendor_id'] : null;
            $pdo->prepare("UPDATE products SET vendor_id=? WHERE id=?")->execute([$vid,$id]);
        }
        elseif (array_key_exists('cost',$b)) {
            $pdo->prepare("UPDATE products SET cost=? WHERE id=?")->execute([(float)$b['cost'],$id]);
        }
        elseif (array_key_exists('sell',$b)) {
            $pdo->prepare("UPDATE products SET sell=? WHERE id=?")->execute([(float)$b['sell'],$id]);
        }
        elseif (array_key_exists('wholesale_price',$b)) {
            $v = ($b['wholesale_price'] !== null && $b['wholesale_price'] !== '') ? (float)$b['wholesale_price'] : null;
            $pdo->prepare("UPDATE products SET wholesale_price=? WHERE id=?")->execute([$v,$id]);
        }
        elseif (array_key_exists('landing_cost',$b)) {
            $v = ($b['landing_cost'] !== null && $b['landing_cost'] !== '') ? (float)$b['landing_cost'] : null;
            $pdo->prepare("UPDATE products SET landing_cost=? WHERE id=?")->execute([$v,$id]);
        }
        elseif (array_key_exists('list_price',$b)) {
            $v = ($b['list_price'] !== null && $b['list_price'] !== '') ? (float)$b['list_price'] : null;
            $pdo->prepare("UPDATE products SET list_price=? WHERE id=?")->execute([$v,$id]);
        }
        elseif (array_key_exists('case_content',$b)) {
            $v = ($b['case_content'] !== null && $b['case_content'] !== '') ? (int)$b['case_content'] : null;
            $pdo->prepare("UPDATE products SET case_content=? WHERE id=?")->execute([$v,$id]);
        }
        elseif (array_key_exists('box_content',$b)) {
            $v = ($b['box_content'] !== null && $b['box_content'] !== '') ? trim((string)$b['box_content']) : null;
            $pdo->prepare("UPDATE products SET box_content=? WHERE id=?")->execute([$v,$id]);
        }
        elseif (array_key_exists('min_stock',$b)) {
            $pdo->prepare("UPDATE products SET min_stock=? WHERE id=?")->execute([(int)$b['min_stock'],$id]);
        }
        elseif (array_key_exists('name',$b) && !empty(trim($b['name']))) {
            $pdo->prepare("UPDATE products SET name=? WHERE id=?")->execute([trim($b['name']),$id]);
        }
        elseif (array_key_exists('sku',$b)) {
            $sku = trim($b['sku']);
            $nums = preg_replace('/\D/','',$sku);
            $ic = $nums !== '' ? (int)$nums : null;
            $pdo->prepare("UPDATE products SET sku=?, item_code=? WHERE id=?")->execute([$sku,$ic,$id]);
        }
        elseif (array_key_exists('procurement_active',$b)) {
            $pdo->prepare("UPDATE products SET procurement_active=? WHERE id=?")->execute([(int)(bool)$b['procurement_active'],$id]);
        }
        elseif (array_key_exists('combo',$b)) {
            $pdo->prepare("UPDATE products SET combo=? WHERE id=?")->execute([(int)(bool)$b['combo'],$id]);
        }
        jsonOk(null,'Updated');
    }
    requireFields($b,['name','cost']);
    $sku = trim($b['sku']??'');
    $itemCode = ($sku !== '' && preg_match('/^(\d+)/', $sku, $icm)) ? (int)$icm[1] : null;
    $pdo->prepare("UPDATE products SET name=?,sku=?,item_code=?,brand=?,category=?,vendor_id=?,cost=?,list_price=?,sell=?,wholesale_price=?,min_stock=?,unit=?,description=?,case_content=?,box_content=?,landing_cost=?,combo=?,procurement_active=? WHERE id=?")
        ->execute([trim($b['name']),$sku,$itemCode,trim($b['brand']??''),trim($b['category']??''),!empty($b['vendor_id'])?(int)$b['vendor_id']:null,(float)$b['cost'],isset($b['list_price'])&&$b['list_price']!==''&&$b['list_price']!==null?(float)$b['list_price']:null,(float)$b['sell'],isset($b['wholesale_price'])&&$b['wholesale_price']!==''&&$b['wholesale_price']!==null?(float)$b['wholesale_price']:null,(int)($b['min_stock']??0),trim($b['unit']??'Box'),trim($b['description']??''),isset($b['case_content'])&&$b['case_content']!==''&&$b['case_content']!==null?(int)$b['case_content']:null,isset($b['box_content'])&&$b['box_content']!=='' ? trim((string)$b['box_content']):null,isset($b['landing_cost'])&&$b['landing_cost']!==''&&$b['landing_cost']!==null?(float)$b['landing_cost']:null,isset($b['combo'])?(int)(bool)$b['combo']:0,isset($b['procurement_active'])?(int)(bool)$b['procurement_active']:1,(int)$b['id']]);
    auditLog($pdo,'update_product','product',(int)$b['id'],$b['name']);
    jsonOk(null,'Product updated');
}
if ($method==='DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    requireRole('admin','manager','partner');
    $id=(int)($_GET['id']??0);
    $name=$pdo->query("SELECT name FROM products WHERE id=$id")->fetchColumn();
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
    auditLog($pdo,'delete_product','product',$id,$name);
    jsonOk(null,'Product deleted');
}
