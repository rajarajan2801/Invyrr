<?php
/**
 * Invyrr API — Categories
 * GET    /api/categories.php        → list all
 * GET    /api/categories.php?id=N   → single
 * POST   /api/categories.php        → create
 * PUT    /api/categories.php        → update
 * DELETE /api/categories.php?id=N   → delete
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../includes/db.php';
startSession(); requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

// Ensure sku_prefix column exists (safe to run every time — silently ignored if already exists)
try { $pdo->exec("ALTER TABLE categories ADD COLUMN sku_prefix VARCHAR(10) DEFAULT NULL AFTER name"); } catch (Exception $e) {}

// Auto-populate sku_prefix from products SKU where still NULL
// Picks the most common 2-digit SKU prefix among products in each category
try {
    $nullCats = $pdo->query("SELECT id, name FROM categories WHERE sku_prefix IS NULL OR sku_prefix = ''")->fetchAll();
    if ($nullCats) {
        $updateStmt = $pdo->prepare("UPDATE categories SET sku_prefix=? WHERE id=?");
        foreach ($nullCats as $cat) {
            $row = $pdo->prepare("
                SELECT LEFT(p.sku, 2) AS prefix, COUNT(*) AS cnt
                FROM products p
                WHERE p.category = ? AND p.sku REGEXP '^[0-9]'
                GROUP BY LEFT(p.sku, 2)
                ORDER BY cnt DESC
                LIMIT 1");
            $row->execute([$cat['name']]);
            $found = $row->fetch();
            if ($found && $found['prefix'] !== '') {
                $updateStmt->execute([$found['prefix'], $cat['id']]);
            }
        }
    }
} catch (Exception $e) {}

if ($method === 'GET') {
    if (!empty($_GET['id'])) {
        $s = $pdo->prepare("SELECT c.id, c.name, c.sku_prefix, c.description, c.color, c.created_at, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category = c.name WHERE c.id=? GROUP BY c.id");
        $s->execute([(int)$_GET['id']]);
        $row = $s->fetch(); if (!$row) jsonError('Not found', 404);
        jsonOk($row);
    }

    if (isset($_GET['duplicates'])) {
        $cats  = $pdo->query("SELECT c.id, c.name, c.sku_prefix, c.description, c.color, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category=c.name GROUP BY c.id ORDER BY c.name")->fetchAll();
        $groups = [];
        foreach ($cats as $c) {
            $key = preg_replace('/[\s\-_\.]+/', '', strtolower($c['name']));
            $groups[$key][] = $c;
        }
        $dupes = [];
        foreach ($groups as $key => $items) {
            if (count($items) > 1) $dupes[] = ['key' => $items[0]['name'], 'items' => $items];
        }
        jsonList($dupes);
    }
    $q = $_GET['q'] ?? '';
    $where = $q ? "WHERE c.name LIKE ?" : "";
    $params = $q ? ['%'.$q.'%'] : [];
    $s = $pdo->prepare("SELECT c.id, c.name, c.sku_prefix, c.description, c.color, c.created_at, COUNT(p.id) AS product_count
                        FROM categories c LEFT JOIN products p ON p.category = c.name
                        $where GROUP BY c.id ORDER BY CAST(c.sku_prefix AS UNSIGNED), c.name");
    $s->execute($params);
    jsonList($s->fetchAll());
}

if ($method === 'POST') {
    requireAuth(); $b = getBody(); requireFields($b, ['name']);
    $name = trim($b['name']);
    // Check duplicate
    $exists = $pdo->prepare("SELECT id FROM categories WHERE name=?");
    $exists->execute([$name]);
    if ($exists->fetch()) jsonError('Category already exists', 409);
    $skuPrefix = trim($b['sku_prefix'] ?? '') ?: null;
    $pdo->prepare("INSERT INTO categories (name, sku_prefix, description, color) VALUES (?,?,?,?)")
        ->execute([$name, $skuPrefix, trim($b['description'] ?? ''), trim($b['color'] ?? '')]);
    $id = (int)$pdo->lastInsertId();
    auditLog($pdo, 'create_category', 'category', $id, $name);
    jsonOk(['id' => $id, 'name' => $name], 'Category created');
}

if ($method === 'PUT') {
    requireAuth(); $b = getBody(); requireFields($b, ['id', 'name']);
    $name = trim($b['name']);
    $oldName = $pdo->query("SELECT name FROM categories WHERE id=".(int)$b['id'])->fetchColumn();
    // Check duplicate (exclude self)
    $exists = $pdo->prepare("SELECT id FROM categories WHERE name=? AND id<>?");
    $exists->execute([$name, (int)$b['id']]);
    if ($exists->fetch()) jsonError('Category name already exists', 409);
    $skuPrefix = trim($b['sku_prefix'] ?? '') ?: null;
    $pdo->prepare("UPDATE categories SET name=?, sku_prefix=?, description=?, color=? WHERE id=?")
        ->execute([$name, $skuPrefix, trim($b['description'] ?? ''), trim($b['color'] ?? ''), (int)$b['id']]);
    // Rename category on all products
    if ($oldName && $oldName !== $name) {
        $pdo->prepare("UPDATE products SET category=? WHERE category=?")->execute([$name, $oldName]);
    }
    auditLog($pdo, 'update_category', 'category', (int)$b['id'], $name);
    jsonOk(null, 'Category updated');
}

if ($method === 'DELETE') {
    requireRole('admin', 'manager');
    $id = (int)($_GET['id'] ?? 0); if (!$id) jsonError('ID required');
    $name = $pdo->query("SELECT name FROM categories WHERE id=$id")->fetchColumn();
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
    auditLog($pdo, 'delete_category', 'category', $id, $name);
    jsonOk(null, 'Category deleted');
}
