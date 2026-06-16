<?php
/**
 * Invyrr API — Locations
 * GET    /api/locations.php        → list all locations
 * GET    /api/locations.php?id=N   → single location with per-product stock
 * POST   /api/locations.php        → create location
 * PUT    /api/locations.php        → update (id in body); set is_default clears others
 * DELETE /api/locations.php?id=N   → delete (blocked if only one location remains)
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

// ── GET ──────────────────────────────────────────────────
if ($method === 'GET') {
    if (!empty($_GET['id'])) {
        $loc = $pdo->prepare('SELECT * FROM locations WHERE id=?');
        $loc->execute([(int)$_GET['id']]);
        $row = $loc->fetch();
        if (!$row) jsonError('Location not found', 404);

        // Attach per-product stock for this location
        $stock = $pdo->prepare('
            SELECT pl.product_id, pl.stock, pl.min_stock,
                   p.name, p.sku, p.brand, p.category, p.unit
            FROM product_locations pl
            JOIN products p ON p.id = pl.product_id
            WHERE pl.location_id = ?
            ORDER BY p.name');
        $stock->execute([$row['id']]);
        $row['products'] = $stock->fetchAll();
        jsonOk($row);
    }

    $rows = $pdo->query('
        SELECT l.*,
               COUNT(DISTINCT pl.product_id)    AS product_count,
               COALESCE(SUM(pl.stock), 0)       AS total_units,
               COALESCE(SUM(pl.stock * p.cost), 0) AS stock_value
        FROM locations l
        LEFT JOIN product_locations pl ON pl.location_id = l.id
        LEFT JOIN products p ON p.id = pl.product_id
        GROUP BY l.id
        ORDER BY l.is_default DESC, l.name')->fetchAll();

    foreach ($rows as &$r) {
        $r['stock_value'] = round($r['stock_value'], 2);
    }
    jsonList($rows);
}

// ── POST ─────────────────────────────────────────────────
if ($method === 'POST') {
    $b = getBody();
    requireFields($b, ['name']);

    $pdo->beginTransaction();
    try {
        $isDefault = !empty($b['is_default']) ? 1 : 0;

        // If setting as default, clear existing default first
        if ($isDefault) {
            $pdo->exec('UPDATE locations SET is_default=0');
        }

        $stmt = $pdo->prepare('INSERT INTO locations (name, address, phone, is_default) VALUES (?,?,?,?)');
        $stmt->execute([
            trim($b['name']),
            trim($b['address'] ?? ''),
            trim($b['phone'] ?? ''),
            $isDefault,
        ]);
        $locId = (int)$pdo->lastInsertId();

        // Create product_locations rows for all existing products (stock=0)
        $pdo->exec("
            INSERT IGNORE INTO product_locations (product_id, location_id, stock, min_stock)
            SELECT id, $locId, 0, 0 FROM products");

        $pdo->commit();
        $row = $pdo->query("SELECT * FROM locations WHERE id=$locId")->fetch();
        jsonOk($row, 'Location created');
    } catch (PDOException $e) {
        $pdo->rollBack();
        jsonError('Failed: ' . $e->getMessage(), 500);
    }
}

// ── PUT ──────────────────────────────────────────────────
if ($method === 'PUT') {
    $b = getBody();
    requireFields($b, ['id', 'name']);
    $id        = (int)$b['id'];
    $isDefault = !empty($b['is_default']) ? 1 : 0;

    $pdo->beginTransaction();
    try {
        if ($isDefault) {
            $pdo->exec('UPDATE locations SET is_default=0');
        }
        $stmt = $pdo->prepare('UPDATE locations SET name=?, address=?, phone=?, is_default=? WHERE id=?');
        $stmt->execute([trim($b['name']), trim($b['address'] ?? ''), trim($b['phone'] ?? ''), $isDefault, $id]);
        $pdo->commit();
        jsonOk(null, 'Location updated');
    } catch (PDOException $e) {
        $pdo->rollBack();
        jsonError('Failed: ' . $e->getMessage(), 500);
    }
}

// ── DELETE ───────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('Location ID required');

    $count = (int)$pdo->query('SELECT COUNT(*) FROM locations')->fetchColumn();
    if ($count <= 1) jsonError('Cannot delete the only location. Add another location first.');

    // Check if it's the default — if so, refuse
    $loc = $pdo->query("SELECT is_default FROM locations WHERE id=$id")->fetch();
    if (!$loc) jsonError('Location not found', 404);
    if ($loc['is_default']) jsonError('Cannot delete the default location. Set another location as default first.');

    $pdo->prepare('DELETE FROM locations WHERE id=?')->execute([$id]);
    jsonOk(null, 'Location deleted');
}
