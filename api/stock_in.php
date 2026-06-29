<?php
/**
 * Invyrr API — Stock In
 * GET    /api/stock_in.php                    → history (optional ?location_id=N, ?product_id=N)
 * POST   /api/stock_in.php                    → record (updates product_locations + products aggregate)
 * DELETE /api/stock_in.php?id=N               → reverse
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

// ── GET ──────────────────────────────────────────────────
if ($method === 'GET') {
    $where  = ['1=1'];
    $params = [];

    if (!empty($_GET['product_id']))  { $where[] = 'si.product_id = ?';  $params[] = (int)$_GET['product_id']; }
    if (!empty($_GET['location_id'])) { $where[] = 'si.location_id = ?'; $params[] = (int)$_GET['location_id']; }
    if (!empty($_GET['from'])) { $where[] = 'si.date >= ?'; $params[] = $_GET['from']; }
    if (!empty($_GET['to']))   { $where[] = 'si.date <= ?'; $params[] = $_GET['to']; }

    $sql = 'SELECT si.*, p.name AS product_name, p.unit,
                   v.name AS vendor_name,
                   l.name AS location_name
            FROM stock_in si
            JOIN products p ON p.id = si.product_id
            LEFT JOIN vendors v ON v.id = si.vendor_id
            LEFT JOIN locations l ON l.id = si.location_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY si.date DESC, si.id DESC
            LIMIT ' . min((int)($_GET['limit'] ?? 500), 1000);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) { $r['total'] = round($r['qty'] * $r['cost'], 2); }
    jsonList($rows);
}

// ── POST ─────────────────────────────────────────────────
if ($method === 'POST') {
    $b = getBody();
    requireFields($b, ['product_id', 'qty', 'date']);

    $productId  = (int)$b['product_id'];
    $qty        = (int)$b['qty'];
    if ($qty < 1) jsonError('Quantity must be at least 1');

    // Resolve location: use provided, fall back to default
    $locationId = !empty($b['location_id']) ? (int)$b['location_id'] : null;
    if (!$locationId) {
        $def = $pdo->query('SELECT id FROM locations WHERE is_default=1 LIMIT 1')->fetch();
        if (!$def) $def = $pdo->query('SELECT id FROM locations ORDER BY id LIMIT 1')->fetch();
        $locationId = $def ? (int)$def['id'] : null;
    }

    $pdo->beginTransaction();
    try {
        $p = $pdo->prepare('SELECT id, cost FROM products WHERE id=? FOR UPDATE');
        $p->execute([$productId]);
        $product = $p->fetch();
        if (!$product) { $pdo->rollBack(); jsonError('Product not found', 404); }

        $cost = isset($b['cost']) && $b['cost'] !== '' ? (float)$b['cost'] : (float)$product['cost'];

        // Insert transaction
        $ins = $pdo->prepare('
            INSERT INTO stock_in (product_id, location_id, vendor_id, qty, cost, date, note)
            VALUES (:pid, :lid, :vid, :qty, :cost, :date, :note)');
        $ins->execute([
            ':pid'  => $productId,
            ':lid'  => $locationId,
            ':vid'  => !empty($b['vendor_id']) ? (int)$b['vendor_id'] : null,
            ':qty'  => $qty, ':cost' => $cost,
            ':date' => $b['date'],
            ':note' => trim($b['note'] ?? ''),
        ]);
        $txnId = (int)$pdo->lastInsertId();

        // Update aggregate product stock + cost
        $pdo->prepare('UPDATE products SET stock = stock + :qty, cost = :cost WHERE id = :id')
            ->execute([':qty' => $qty, ':cost' => $cost, ':id' => $productId]);

        // Upsert per-location stock
        if ($locationId) {
            $pdo->prepare('
                INSERT INTO product_locations (product_id, location_id, stock, min_stock)
                VALUES (:pid, :lid, :qty, 0)
                ON DUPLICATE KEY UPDATE stock = stock + VALUES(stock)')
                ->execute([':pid' => $productId, ':lid' => $locationId, ':qty' => $qty]);
        }

        $pdo->commit();

        $row = $pdo->query("
            SELECT si.*, p.name AS product_name, p.unit,
                   v.name AS vendor_name, l.name AS location_name
            FROM stock_in si
            JOIN products p ON p.id = si.product_id
            LEFT JOIN vendors v ON v.id = si.vendor_id
            LEFT JOIN locations l ON l.id = si.location_id
            WHERE si.id = $txnId")->fetch();
        $row['total'] = round($row['qty'] * $row['cost'], 2);
        $pname=$pdo->query("SELECT name FROM products WHERE id=$productId")->fetchColumn();
        auditLog($pdo,'stock_in','product',$productId,"+{$qty} units of {$pname} @ ₹{$cost} each");
        jsonOk($row, "Stock In recorded: +{$qty} units");

    } catch (PDOException $e) {
        $pdo->rollBack();
        jsonError('Transaction failed: ' . $e->getMessage(), 500);
    }
}

// ── DELETE (reverse) ─────────────────────────────────────
if ($method === 'DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('Transaction ID required');

    $pdo->beginTransaction();
    try {
        $txn = $pdo->query("SELECT * FROM stock_in WHERE id=$id")->fetch();
        if (!$txn) { $pdo->rollBack(); jsonError('Transaction not found', 404); }

        $prod = $pdo->query("SELECT stock FROM products WHERE id={$txn['product_id']}")->fetch();
        if ($prod['stock'] < $txn['qty']) {
            $pdo->rollBack();
            jsonError("Cannot reverse: aggregate stock ({$prod['stock']}) is less than transaction quantity ({$txn['qty']})");
        }

        // Reverse aggregate
        $pdo->exec("UPDATE products SET stock = stock - {$txn['qty']} WHERE id = {$txn['product_id']}");

        // Reverse per-location stock
        if ($txn['location_id']) {
            $pdo->exec("UPDATE product_locations SET stock = GREATEST(0, stock - {$txn['qty']})
                        WHERE product_id = {$txn['product_id']} AND location_id = {$txn['location_id']}");
        }

        $pdo->exec("DELETE FROM stock_in WHERE id=$id");
        $pname=$pdo->query("SELECT name FROM products WHERE id={$txn['product_id']}")->fetchColumn();
        auditLog($pdo,'stock_in_reversed','product',$txn['product_id'],"-{$txn['qty']} units of {$pname} reversed");
        $pdo->commit();
        jsonOk(null, 'Transaction reversed');

    } catch (PDOException $e) {
        $pdo->rollBack();
        jsonError('Reversal failed: ' . $e->getMessage(), 500);
    }
}
