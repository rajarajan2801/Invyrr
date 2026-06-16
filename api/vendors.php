<?php
/**
 * Invyrr API — Vendors
 * GET    /api/vendors.php        → list (optional ?q=search&type=Fireworks)
 * GET    /api/vendors.php?id=N   → single vendor
 * POST   /api/vendors.php        → create
 * PUT    /api/vendors.php        → update (id in body)
 * DELETE /api/vendors.php?id=N   → delete
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../includes/db.php';
startSession(); requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

// Ensure pricing_formula column exists (JSON array of {op,value} steps)
try { $pdo->exec("ALTER TABLE vendors ADD COLUMN pricing_formula TEXT DEFAULT NULL"); } catch (Exception $e) {}

// Ensure case_margin column exists (₹ per case, overrides the global default in Settings if set)
try { $pdo->exec("ALTER TABLE vendors ADD COLUMN case_margin DECIMAL(12,2) DEFAULT NULL"); } catch (Exception $e) {}

$allowed_types = ['Fireworks', 'Agent', 'Both', 'Other'];

// ── GET ──────────────────────────────────────────────────
if ($method === 'GET') {
    if (!empty($_GET['id'])) {
        $stmt = $pdo->prepare('SELECT * FROM vendors WHERE id=?');
        $stmt->execute([(int)$_GET['id']]);
        $row = $stmt->fetch();
        if (!$row) jsonError('Vendor not found', 404);
        jsonOk($row);
    }

    if (isset($_GET['duplicates'])) {
        $vendors = $pdo->query("SELECT id, name, type, city, phone FROM vendors ORDER BY name")->fetchAll();
        $groups  = [];
        foreach ($vendors as $v) {
            $key = preg_replace('/[\s\-_\.&]+/', '', strtolower($v['name']));
            $groups[$key][] = $v;
        }
        $dupes = [];
        foreach ($groups as $key => $items) {
            if (count($items) > 1) $dupes[] = ['key' => $items[0]['name'], 'items' => $items];
        }
        jsonList($dupes);
    }

    $where  = ['1=1'];
    $params = [];
    if (!empty($_GET['q'])) {
        $like    = '%' . $_GET['q'] . '%';
        $where[] = '(v.name LIKE ? OR v.city LIKE ? OR v.phone LIKE ? OR v.contact LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    if (!empty($_GET['type']) && in_array($_GET['type'], $allowed_types)) {
        $where[] = 'v.type = ?';
        $params[] = $_GET['type'];
    }

    $sql = 'SELECT v.*, COUNT(p.id) AS product_count
            FROM vendors v LEFT JOIN products p ON p.vendor_id = v.id
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY v.id ORDER BY v.name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    jsonList($stmt->fetchAll());
}

// ── POST ─────────────────────────────────────────────────
if ($method === 'POST') {
    requireAuth();
    $b = getBody();
    requireFields($b, ['name']);
    $type = in_array($b['type'] ?? '', $allowed_types) ? $b['type'] : '';

    $pricingFormula = isset($b['pricing_formula']) ? json_encode(json_decode($b['pricing_formula'], true) ?: []) : '[]';
    $caseMargin = (isset($b['case_margin']) && $b['case_margin'] !== '' && $b['case_margin'] !== null) ? (float)$b['case_margin'] : null;
    $stmt = $pdo->prepare('
        INSERT INTO vendors (name, type, contact, phone, email, city, gst, address, pricing_formula, case_margin)
        VALUES (:name,:type,:contact,:phone,:email,:city,:gst,:address,:pricing_formula,:case_margin)');
    $stmt->execute([
        ':name'    => trim($b['name']),
        ':type'    => $type,
        ':contact' => trim($b['contact'] ?? ''),
        ':phone'   => trim($b['phone']   ?? ''),
        ':email'   => trim($b['email']   ?? ''),
        ':city'    => trim($b['city']    ?? ''),
        ':gst'     => trim($b['gst']     ?? ''),
        ':address' => trim($b['address'] ?? ''),
        ':pricing_formula' => $pricingFormula,
        ':case_margin' => $caseMargin,
    ]);
    $id = (int)$pdo->lastInsertId();
    // Auto-link any products imported with this vendor name but no vendor_id
    $linked = $pdo->prepare("UPDATE products SET vendor_id=?, pending_vendor_name=NULL WHERE pending_vendor_name=? AND vendor_id IS NULL");
    $linked->execute([$id, trim($b['name'])]);
    $linkedCount = $linked->rowCount();
    $msg = 'Vendor created' . ($linkedCount > 0 ? " — linked $linkedCount product(s)" : '');
    jsonOk($pdo->query("SELECT * FROM vendors WHERE id=$id")->fetch(), $msg);
}

// ── PUT ──────────────────────────────────────────────────
if ($method === 'PUT') {
    requireAuth();
    $b = getBody();
    requireFields($b, ['id', 'name']);
    $type = in_array($b['type'] ?? '', $allowed_types) ? $b['type'] : '';

    $pricingFormula = isset($b['pricing_formula']) ? json_encode(json_decode($b['pricing_formula'], true) ?: []) : '[]';
    $caseMargin = (isset($b['case_margin']) && $b['case_margin'] !== '' && $b['case_margin'] !== null) ? (float)$b['case_margin'] : null;
    $pdo->prepare('
        UPDATE vendors SET name=:name, type=:type, contact=:contact, phone=:phone,
            email=:email, city=:city, gst=:gst, address=:address, pricing_formula=:pricing_formula, case_margin=:case_margin
        WHERE id=:id')->execute([
        ':id'=>(int)$b['id'], ':name'=>trim($b['name']), ':type'=>$type,
        ':contact'=>trim($b['contact']??''), ':phone'=>trim($b['phone']??''),
        ':email'=>trim($b['email']??''), ':city'=>trim($b['city']??''),
        ':gst'=>trim($b['gst']??''), ':address'=>trim($b['address']??''),
        ':pricing_formula'=>$pricingFormula,
        ':case_margin'=>$caseMargin,
    ]);
    // Auto-link any products with matching pending vendor name
    $linked = $pdo->prepare("UPDATE products SET vendor_id=?, pending_vendor_name=NULL WHERE pending_vendor_name=? AND vendor_id IS NULL");
    $linked->execute([(int)$b['id'], trim($b['name'])]);
    $linkedCount = $linked->rowCount();
    $msg = 'Vendor updated' . ($linkedCount > 0 ? " — linked $linkedCount product(s)" : '');
    auditLog($pdo,'update_vendor','vendor',(int)$b['id'],trim($b['name']));
    jsonOk(null, $msg);
}

// ── DELETE ───────────────────────────────────────────────
if ($method === 'DELETE') {
    requireRole('admin', 'manager');
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('Vendor ID required');
    $pdo->prepare('DELETE FROM vendors WHERE id=?')->execute([$id]);
    jsonOk(null, 'Vendor deleted');
}
