<?php
/**
 * Invyrr API — Stock Out
 * GET    /api/stock_out.php                   → history (optional ?location_id=N)
 * POST   /api/stock_out.php                   → record (deducts from location + aggregate)
 * DELETE /api/stock_out.php?id=N              → reverse
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

    if (!empty($_GET['product_id']))  { $where[] = 'so.product_id = ?';  $params[] = (int)$_GET['product_id']; }
    if (!empty($_GET['location_id'])) { $where[] = 'so.location_id = ?'; $params[] = (int)$_GET['location_id']; }
    if (!empty($_GET['from'])) { $where[] = 'so.date >= ?'; $params[] = $_GET['from']; }
    if (!empty($_GET['to']))   { $where[] = 'so.date <= ?'; $params[] = $_GET['to']; }

    $sql = 'SELECT so.*, p.name AS product_name, p.unit,
                   l.name AS location_name,
                   (so.sell_price - so.cost) * so.qty AS profit
            FROM stock_out so
            JOIN products p ON p.id = so.product_id
            LEFT JOIN locations l ON l.id = so.location_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY so.date DESC, so.id DESC
            LIMIT ' . min((int)($_GET['limit'] ?? 500), 1000);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    jsonList($stmt->fetchAll());
}

// ── POST ─────────────────────────────────────────────────
if ($method === 'POST') {
    $b = getBody();
    requireFields($b, ['product_id', 'qty', 'date']);

    $productId  = (int)$b['product_id'];
    $qty        = (int)$b['qty'];
    if ($qty < 1) jsonError('Quantity must be at least 1');

    // Resolve location
    $locationId = !empty($b['location_id']) ? (int)$b['location_id'] : null;
    if (!$locationId) {
        $def = $pdo->query('SELECT id FROM locations WHERE is_default=1 LIMIT 1')->fetch();
        if (!$def) $def = $pdo->query('SELECT id FROM locations ORDER BY id LIMIT 1')->fetch();
        $locationId = $def ? (int)$def['id'] : null;
    }

    $pdo->beginTransaction();
    try {
        $p = $pdo->prepare('SELECT id, stock, cost, sell, unit FROM products WHERE id=? FOR UPDATE');
        $p->execute([$productId]);
        $product = $p->fetch();
        if (!$product) { $pdo->rollBack(); jsonError('Product not found', 404); }

        // Check location-level stock first (if location specified)
        if ($locationId) {
            $locStock = $pdo->prepare('SELECT stock FROM product_locations WHERE product_id=? AND location_id=? FOR UPDATE');
            $locStock->execute([$productId, $locationId]);
            $ls = $locStock->fetch();
            $available = $ls ? (int)$ls['stock'] : 0;
            if ($available < $qty) {
                $pdo->rollBack();
                $loc = $pdo->query("SELECT name FROM locations WHERE id=$locationId")->fetch();
                jsonError("Insufficient stock at {$loc['name']}: {$available} {$product['unit']} available, {$qty} requested");
            }
        } else {
            // Fall back to aggregate check
            if ($product['stock'] < $qty) {
                $pdo->rollBack();
                jsonError("Insufficient stock: {$product['stock']} {$product['unit']} available");
            }
        }

        $sellPrice = isset($b['sell_price']) && $b['sell_price'] !== '' ? (float)$b['sell_price'] : (float)$product['sell'];

        $ins = $pdo->prepare('
            INSERT INTO stock_out (product_id, location_id, qty, sell_price, cost, customer, date, note)
            VALUES (:pid, :lid, :qty, :sell, :cost, :customer, :date, :note)');
        $ins->execute([
            ':pid' => $productId, ':lid' => $locationId,
            ':qty' => $qty, ':sell' => $sellPrice, ':cost' => (float)$product['cost'],
            ':customer' => trim($b['customer'] ?? ''),
            ':date' => $b['date'], ':note' => trim($b['note'] ?? ''),
        ]);
        $txnId = (int)$pdo->lastInsertId();

        // Deduct aggregate
        $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?')->execute([$qty, $productId]);

        // Deduct per-location
        if ($locationId) {
            $pdo->exec("UPDATE product_locations SET stock = GREATEST(0, stock - $qty)
                        WHERE product_id = $productId AND location_id = $locationId");
        }

        $pdo->commit();

        $row = $pdo->query("
            SELECT so.*, p.name AS product_name, p.unit, l.name AS location_name,
                   (so.sell_price - so.cost) * so.qty AS profit
            FROM stock_out so
            JOIN products p ON p.id = so.product_id
            LEFT JOIN locations l ON l.id = so.location_id
            WHERE so.id = $txnId")->fetch();
        $pname=$pdo->query("SELECT name FROM products WHERE id=$productId")->fetchColumn();
        auditLog($pdo,'stock_out','product',$productId,"-{$qty} units of {$pname} @ ₹{$sellPrice}");
        jsonOk($row, "Stock Out recorded: -{$qty} units");

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
        $txn = $pdo->query("SELECT * FROM stock_out WHERE id=$id")->fetch();
        if (!$txn) { $pdo->rollBack(); jsonError('Transaction not found', 404); }

        $pdo->exec("UPDATE products SET stock = stock + {$txn['qty']} WHERE id = {$txn['product_id']}");

        if ($txn['location_id']) {
            $pdo->exec("UPDATE product_locations SET stock = stock + {$txn['qty']}
                        WHERE product_id = {$txn['product_id']} AND location_id = {$txn['location_id']}");
        }

        $pdo->exec("DELETE FROM stock_out WHERE id=$id");
        $pdo->commit();
        jsonOk(null, 'Transaction reversed');

    } catch (PDOException $e) {
        $pdo->rollBack();
        jsonError('Reversal failed: ' . $e->getMessage(), 500);
    }
}
