<?php
/**
 * Invoices API
 * GET    /api/invoices.php            → list (?from=&to=&customer_id=&location_id=&status=)
 * GET    /api/invoices.php?id=N       → single invoice with items
 * POST   /api/invoices.php            → create invoice (deducts stock per item)
 * PUT    /api/invoices.php            → update status/notes only
 * DELETE /api/invoices.php?id=N       → cancel (restores stock)
 * GET    /api/invoices.php?print=N    → HTML invoice for printing
 */
require __DIR__.'/../includes/db.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}
startSession();
$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

// ── Print invoice (HTML) ─────────────────────────────────
if ($method==='GET' && !empty($_GET['print'])) {
    $inv = getFullInvoice($pdo,(int)$_GET['print']);
    if (!$inv) { http_response_code(404); echo 'Not found'; exit; }
    $biz = getAllSettings($pdo);
    outputInvoiceHTML($inv,$biz);
    exit;
}

// ── GET list ─────────────────────────────────────────────
if ($method==='GET' && empty($_GET['id'])) {
    $where=['1=1']; $params=[];
    if (!empty($_GET['customer_id'])) { $where[]='i.customer_id=?'; $params[]=(int)$_GET['customer_id']; }
    if (!empty($_GET['location_id']))  { $where[]='i.location_id=?';  $params[]=(int)$_GET['location_id']; }
    if (!empty($_GET['status']))       { $where[]='i.status=?';        $params[]=$_GET['status']; }
    if (!empty($_GET['from']))         { $where[]='i.date>=?';         $params[]=$_GET['from']; }
    if (!empty($_GET['to']))           { $where[]='i.date<=?';         $params[]=$_GET['to']; }
    if (!empty($_GET['q'])) { $like='%'.$_GET['q'].'%'; $where[]='(i.invoice_number LIKE ? OR i.customer_name LIKE ?)'; $params[]=$like; $params[]=$like; }
    $sql="SELECT i.*,l.name AS location_name,
          (SELECT COUNT(*) FROM invoice_items ii WHERE ii.invoice_id=i.id) AS item_count
          FROM invoices i LEFT JOIN locations l ON l.id=i.location_id
          WHERE ".implode(' AND ',$where)." ORDER BY i.date DESC,i.id DESC LIMIT 500";
    $s=$pdo->prepare($sql); $s->execute($params); jsonList($s->fetchAll());
}

// ── GET single ───────────────────────────────────────────
if ($method==='GET' && !empty($_GET['id'])) {
    $inv = getFullInvoice($pdo,(int)$_GET['id']);
    if (!$inv) jsonError('Invoice not found',404);
    jsonOk($inv);
}

// ── POST create ──────────────────────────────────────────
if ($method==='POST') {
    $u = requireAuth();
    $b = getBody();
    requireFields($b,['items','date']);
    if (empty($b['items']) || !is_array($b['items'])) jsonError('Invoice must have at least one item');

    $pdo->beginTransaction();
    try {
        $biz        = getAllSettings($pdo);
        $taxRate    = (float)($b['tax_rate'] ?? $biz['tax_rate'] ?? 0);
        $discount   = (float)($b['discount'] ?? 0);
        $locId      = !empty($b['location_id']) ? (int)$b['location_id'] : getDefaultLocationId($pdo);
        $custId     = !empty($b['customer_id']) ? (int)$b['customer_id'] : null;
        $custName   = '';
        if ($custId) {
            $cn = $pdo->query("SELECT name FROM customers WHERE id=$custId")->fetchColumn();
            $custName = $cn ?: '';
        } else {
            $custName = trim($b['customer_name'] ?? '');
        }

        // Generate invoice number
        $prefix  = $biz['invoice_prefix'] ?? 'INV';
        $last    = (int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
        $invNum  = $prefix.'-'.date('Ymd').'-'.str_pad($last+1,4,'0',STR_PAD_LEFT);

        // Calculate totals
        $subtotal = 0;
        $lineItems = [];
        foreach ($b['items'] as $item) {
            if (empty($item['product_id']) || empty($item['qty'])) continue;
            $pid = (int)$item['product_id'];
            $qty = (int)$item['qty'];
            $p   = $pdo->query("SELECT id,name,cost,sell,stock,unit FROM products WHERE id=$pid FOR UPDATE")->fetch();
            if (!$p) throw new Exception("Product ID $pid not found");
            if ($qty <= 0) throw new Exception("Quantity must be greater than 0 for {$p['name']}");
            // Check available stock — use location stock if location set, else aggregate
            if ($locId) {
                $ls    = $pdo->query("SELECT stock FROM product_locations WHERE product_id=$pid AND location_id=$locId FOR UPDATE")->fetch();
                $avail = $ls ? (int)$ls['stock'] : 0;
            } else {
                $avail = (int)$p['stock'];
            }
            if ($avail <= 0) throw new Exception("'{$p['name']}' is out of stock (0 available)");
            if ($avail < $qty) throw new Exception("Insufficient stock for '{$p['name']}': only $avail {$p['unit']} available");
            $price = isset($item['unit_price']) && $item['unit_price']!=='' ? (float)$item['unit_price'] : (float)$p['sell'];
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;
            $lineItems[] = ['pid'=>$pid,'name'=>$p['name'],'qty'=>$qty,'price'=>$price,'cost'=>(float)$p['cost'],'total'=>$lineTotal];
        }
        if (!$lineItems) throw new Exception('No valid items');

        $subtotalAfterDiscount = max(0, $subtotal - $discount);
        $taxAmount = round($subtotalAfterDiscount * $taxRate / 100, 2);
        $packing   = round((float)($b['packing_charges']??0), 2);
        $misc      = round((float)($b['misc_charges']??0), 2);
        $total     = round($subtotalAfterDiscount + $taxAmount + $packing + $misc, 2);

        // Insert estimate
        $iStmt = $pdo->prepare("INSERT INTO invoices (invoice_number,customer_id,customer_name,location_id,subtotal,discount,tax_rate,tax_amount,packing_charges,misc_charges,total,payment_method,amount_received,status,notes,date,created_by)
                                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $iStmt->execute([$invNum,$custId,$custName,$locId,$subtotal,$discount,$taxRate,$taxAmount,$packing,$misc,$total,
                         $b['payment_method']??'',round((float)($b['amount_received']??0),2),'paid',$b['notes']??'',$b['date'],$u['id']]);
        $invId = (int)$pdo->lastInsertId();

        // Insert items + stock_out + deduct stock
        foreach ($lineItems as $li) {
            $pdo->prepare("INSERT INTO invoice_items (invoice_id,product_id,product_name,qty,unit_price,cost,total) VALUES (?,?,?,?,?,?,?)")
                ->execute([$invId,$li['pid'],$li['name'],$li['qty'],$li['price'],$li['cost'],$li['total']]);
            $pdo->prepare("INSERT INTO stock_out (product_id,location_id,invoice_id,qty,sell_price,cost,customer,date,created_by) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$li['pid'],$locId,$invId,$li['qty'],$li['price'],$li['cost'],$custName,$b['date'],$u['id']]);
            $pdo->exec("UPDATE products SET stock=stock-{$li['qty']} WHERE id={$li['pid']}");
            $pdo->exec("UPDATE product_locations SET stock=GREATEST(0,stock-{$li['qty']}) WHERE product_id={$li['pid']} AND location_id=$locId");
        }

        auditLog($pdo,'create_invoice','invoice',$invId,"Estimate $invNum total ₹$total");
        $pdo->commit();
        jsonOk(getFullInvoice($pdo,$invId), "Estimate $invNum created");
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonError($e->getMessage(), 500);
    }
}

// ── PUT update (full edit for draft estimates) ───────────
if ($method==='PUT') {
    $u = requireAuth();
    $b = getBody();
    requireFields($b,['id']);
    $id = (int)$b['id'];
    $inv = $pdo->query("SELECT * FROM invoices WHERE id=$id")->fetch();
    if (!$inv) jsonError('Not found',404);

    // Full edit only allowed on draft/paid (not cancelled)
    if (!empty($b['items']) && is_array($b['items'])) {
        $pdo->beginTransaction();
        try {
            $custId   = !empty($b['customer_id'])   ? (int)$b['customer_id']   : null;
            $custName = trim($b['customer_name']     ?? $inv['customer_name']   ?? 'Walk-in');
            $locId    = !empty($b['location_id'])    ? (int)$b['location_id']   : (int)$inv['location_id'];
            $discount = round((float)($b['discount'] ?? $inv['discount']), 2);
            $taxRate  = round((float)($b['tax_rate'] ?? $inv['tax_rate']), 2);
            $packing  = round((float)($b['packing_charges'] ?? $inv['packing_charges']), 2);
            $misc     = round((float)($b['misc_charges'] ?? $inv['misc_charges']), 2);

            // Restore old stock
            $oldItems = $pdo->query("SELECT * FROM invoice_items WHERE invoice_id=$id")->fetchAll();
            foreach ($oldItems as $oi) {
                $pdo->exec("UPDATE products SET stock=stock+{$oi['qty']} WHERE id={$oi['product_id']}");
                $pdo->exec("UPDATE product_locations SET stock=stock+{$oi['qty']} WHERE product_id={$oi['product_id']} AND location_id={$inv['location_id']}");
            }
            $pdo->exec("DELETE FROM invoice_items WHERE invoice_id=$id");
            $pdo->exec("DELETE FROM stock_out WHERE invoice_id=$id");

            // Rebuild items
            $subtotal = 0;
            foreach ($b['items'] as $item) {
                $pid = (int)$item['product_id'];
                $qty = max(1, (int)$item['qty']);
                if ($qty <= 0) continue;
                $price = round((float)$item['unit_price'], 2);
                $p = $pdo->query("SELECT * FROM products WHERE id=$pid FOR UPDATE")->fetch();
                if (!$p) continue;
                // Stock check (stock was already restored above)
                if ($locId) {
                    $ls = $pdo->query("SELECT stock FROM product_locations WHERE product_id=$pid AND location_id=$locId")->fetch();
                    $avail = $ls ? (int)$ls['stock'] : 0;
                } else {
                    $avail = (int)$p['stock'];
                }
                if ($avail < $qty) throw new Exception("Insufficient stock for '{$p['name']}': only $avail available after restoring previous stock");
                $lineTotal = $qty * $price;
                $subtotal += $lineTotal;
                $pdo->prepare("INSERT INTO invoice_items (invoice_id,product_id,product_name,qty,unit_price,cost,total) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$id,$pid,$p['name'],$qty,$price,(float)$p['cost'],$lineTotal]);
                $pdo->prepare("INSERT INTO stock_out (product_id,location_id,invoice_id,qty,sell_price,cost,customer,date,created_by) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$pid,$locId,$id,$qty,$price,(float)$p['cost'],$custName,$b['date']??$inv['date'],$u['id']]);
                $pdo->exec("UPDATE products SET stock=stock-$qty WHERE id=$pid");
                $pdo->exec("UPDATE product_locations SET stock=GREATEST(0,stock-$qty) WHERE product_id=$pid AND location_id=$locId");
            }
            $afterDiscount = max(0, $subtotal - $discount);
            $taxAmount = round($afterDiscount * $taxRate / 100, 2);
            $total = round($afterDiscount + $taxAmount + $packing + $misc, 2);
            $pdo->prepare("UPDATE invoices SET customer_id=?,customer_name=?,location_id=?,date=?,payment_method=?,amount_received=?,subtotal=?,discount=?,tax_rate=?,tax_amount=?,packing_charges=?,misc_charges=?,total=?,notes=?,status=? WHERE id=?")
                ->execute([$custId,$custName,$locId,$b['date']??$inv['date'],$b['payment_method']??$inv['payment_method'],
                           round((float)($b['amount_received']??$inv['amount_received']??0),2),
                           $subtotal,$discount,$taxRate,$taxAmount,$packing,$misc,$total,
                           $b['notes']??$inv['notes'],$b['status']??$inv['status'],$id]);
            auditLog($pdo,'update_invoice','invoice',$id,"Updated estimate #".$inv['invoice_number']);
            $pdo->commit();
            jsonOk(getFullInvoice($pdo,$id),'Estimate updated');
        } catch(Exception $e){ $pdo->rollBack(); jsonError($e->getMessage(),500); }
    } else {
        // Simple status/notes update
        $pdo->prepare("UPDATE invoices SET status=?,notes=? WHERE id=?")
            ->execute([$b['status']??$inv['status'],$b['notes']??$inv['notes'],$id]);
        jsonOk(null,'Estimate updated');
    }
}

// ── DELETE cancel ────────────────────────────────────────
if ($method==='DELETE') {
    requireRole('admin','manager');
    $id  = (int)($_GET['id']??0);
    $inv = getFullInvoice($pdo,$id);
    if (!$inv) jsonError('Not found',404);
    if ($inv['status']==='cancelled') jsonError('Already cancelled');
    $pdo->beginTransaction();
    try {
        // Restore stock
        foreach ($inv['items'] as $item) {
            $pdo->exec("UPDATE products SET stock=stock+{$item['qty']} WHERE id={$item['product_id']}");
            if ($inv['location_id'])
                $pdo->exec("UPDATE product_locations SET stock=stock+{$item['qty']} WHERE product_id={$item['product_id']} AND location_id={$inv['location_id']}");
        }
        $pdo->exec("UPDATE stock_out SET note=CONCAT(COALESCE(note,''),' [CANCELLED]') WHERE invoice_id=$id");
        $pdo->exec("UPDATE invoices SET status='cancelled' WHERE id=$id");
        $pdo->commit();
        auditLog($pdo,'cancel_invoice','invoice',$id);
        jsonOk(null,'Invoice cancelled and stock restored');
    } catch (PDOException $e) { $pdo->rollBack(); jsonError($e->getMessage(),500); }
}

// ── Helpers ──────────────────────────────────────────────
function getFullInvoice(PDO $pdo, int $id): ?array {
    $inv = $pdo->query("SELECT i.*,l.name AS location_name,c.phone AS customer_phone,c.gst AS customer_gst,c.address AS customer_address
                        FROM invoices i LEFT JOIN locations l ON l.id=i.location_id LEFT JOIN customers c ON c.id=i.customer_id
                        WHERE i.id=$id")->fetch();
    if (!$inv) return null;
    $inv['items'] = $pdo->query("SELECT * FROM invoice_items WHERE invoice_id=$id ORDER BY id")->fetchAll();
    return $inv;
}
function getAllSettings(PDO $pdo): array {
    return $pdo->query("SELECT k,v FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
}
function getDefaultLocationId(PDO $pdo): ?int {
    $r = $pdo->query("SELECT id FROM locations WHERE is_default=1 LIMIT 1")->fetch();
    if (!$r) $r = $pdo->query("SELECT id FROM locations ORDER BY id LIMIT 1")->fetch();
    return $r ? (int)$r['id'] : null;
}
function outputInvoiceHTML(array $inv, array $biz): void {
    $sym  = $biz['currency_symbol'] ?? '₹';
    $items = $inv['items'];
    $rows  = '';
    foreach ($items as $it) {
        $rows .= "<tr><td>{$it['product_name']}</td><td style='text-align:center'>{$it['qty']}</td><td style='text-align:right'>{$sym}".number_format($it['unit_price'],2)."</td><td style='text-align:right'>{$sym}".number_format($it['total'],2)."</td></tr>";
    }
    $discount   = (float)$inv['discount'] > 0 ? "<tr><td colspan='3' style='text-align:right;color:#666'>Discount</td><td style='text-align:right;color:#e44'>-{$sym}".number_format($inv['discount'],2)."</td></tr>" : '';
    $tax        = (float)$inv['tax_rate'] > 0  ? "<tr><td colspan='3' style='text-align:right;color:#666'>Tax ({$inv['tax_rate']}%)</td><td style='text-align:right'>{$sym}".number_format($inv['tax_amount'],2)."</td></tr>" : '';
    $packing    = (float)($inv['packing_charges']??0) > 0 ? "<tr><td colspan='3' style='text-align:right;color:#666'>Packing</td><td style='text-align:right'>{$sym}".number_format($inv['packing_charges'],2)."</td></tr>" : '';
    $miscChg    = (float)($inv['misc_charges']??0) > 0 ? "<tr><td colspan='3' style='text-align:right;color:#666'>Misc. Charges</td><td style='text-align:right'>{$sym}".number_format($inv['misc_charges'],2)."</td></tr>" : '';
    $custAddr   = $inv['customer_address'] ? "<div style='color:#666;font-size:13px'>{$inv['customer_address']}</div>" : '';
    $custGst    = $inv['customer_gst']     ? "<div style='color:#666;font-size:13px'>GST: {$inv['customer_gst']}</div>" : '';
    $custPhone  = $inv['customer_phone']   ? "<div style='color:#666;font-size:13px'>{$inv['customer_phone']}</div>" : '';
    $bizGst     = $biz['business_gst']     ? "<div style='color:#666;font-size:13px'>GST: {$biz['business_gst']}</div>" : '';
    $notes      = $inv['notes']            ? "<div style='background:#fffbe6;border-radius:6px;padding:12px;font-size:13px;color:#666'><strong>Notes:</strong> {$inv['notes']}</div>" : '';
    $payMethod  = ucfirst($inv['payment_method'] ?? '');
    echo <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Invoice {$inv['invoice_number']}</title>
<style>
 body{font-family:Arial,sans-serif;color:#222;margin:0;padding:32px;font-size:14px}
 .header{display:flex;justify-content:space-between;margin-bottom:32px}
 .biz-name{font-size:22px;font-weight:700;color:#1a1a2e}
 .inv-title{font-size:28px;font-weight:800;color:#4f8eff;letter-spacing:-1px}
 .inv-meta{margin-top:6px;color:#666;font-size:13px}
 table{width:100%;border-collapse:collapse;margin:24px 0}
 th{background:#f0f4ff;padding:10px 12px;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#555}
 td{padding:10px 12px;border-bottom:1px solid #eee}
 .totals-row td{border:none}
 .total-row td{font-weight:700;font-size:16px;border-top:2px solid #222;padding-top:12px}
 .footer{margin-top:40px;padding-top:16px;border-top:1px solid #eee;color:#999;font-size:12px;text-align:center}
 @media print{body{padding:0}.no-print{display:none}}
</style></head><body>
<div class="no-print" style="margin-bottom:20px">
  <button onclick="window.print()" style="background:#4f8eff;color:#fff;border:none;padding:10px 24px;border-radius:6px;cursor:pointer;font-size:14px">🖨️ Print / Save PDF</button>
  <button onclick="window.close()" style="background:#eee;border:none;padding:10px 24px;border-radius:6px;cursor:pointer;font-size:14px;margin-left:8px">Close</button>
</div>
<div class="header">
  <div>
    <div class="biz-name">{$biz['business_name']}</div>
    <div style="color:#666;font-size:13px;margin-top:4px">{$biz['business_address']}</div>
    <div style="color:#666;font-size:13px">{$biz['business_phone']} {$biz['business_email']}</div>
    $bizGst
  </div>
  <div style="text-align:right">
    <div class="inv-title">ESTIMATE</div>
    <div class="inv-meta"><strong>{$inv['invoice_number']}</strong></div>
    <div class="inv-meta">Date: {$inv['date']}</div>
    <div class="inv-meta">Payment: $payMethod</div>
    <div class="inv-meta">Location: {$inv['location_name']}</div>
  </div>
</div>
<div style="background:#f8faff;border-radius:8px;padding:16px;margin-bottom:8px">
  <div style="font-size:12px;text-transform:uppercase;letter-spacing:.8px;color:#888;margin-bottom:4px">Bill To</div>
  <div style="font-weight:600;font-size:15px">{$inv['customer_name']}</div>
  $custAddr$custGst$custPhone
</div>
<table><thead><tr><th>Item</th><th style='text-align:center'>Qty</th><th style='text-align:right'>Unit Price</th><th style='text-align:right'>Total</th></tr></thead>
<tbody>$rows</tbody>
<tfoot>
  $discount$tax$packing$miscChg
  <tr class='total-row'><td colspan='3' style='text-align:right'>TOTAL</td><td style='text-align:right'>{$sym}
HTML;
    echo number_format($inv['total'],2);
    echo <<<HTML
</td></tr>
</tfoot></table>
$notes
<div class='footer'>Thank you for your business!</div>
</body></html>
HTML;
}
