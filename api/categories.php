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

// Ensure sort_order column exists — drives the display order on the public
// shop (shop.php) and this admin list, so staff can match the external
// therrcrackers.com category order (or fine-tune it) without renaming
// anything.
try { $pdo->exec("ALTER TABLE categories ADD COLUMN sort_order INT DEFAULT NULL AFTER sku_prefix"); } catch (Exception $e) {}

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

// Auto-seed sort_order (once per category) from the category order used
// on the external site https://therrcrackers.com/products.php, so a
// freshly-added category lines up with that reference order without
// anyone having to type numbers in by hand. Matching is best-effort
// (normalized exact match, else substring match); categories that don't
// match anything keep sort_order NULL and simply sort after the matched
// ones, alphabetically — never blocking or erroring.
try {
    $refCategoryOrder = [
        'COMBO PACKS', 'WE TWO / IRATTAI KIZHI FIREWORKS',
        'SPARKLERS | RUBY, PARADISE', 'PREMIUM SPARKLERS | RAMESH SPARKLERS',
        'FLOWER POTS', 'PREMIUM FLOWER POTS', 'GROUND CHAKKAR',
        'PREMIUM FANCY CHAKKARS [NIGHT]', 'FOUNTAINS and SHOWERS [NIGHT]',
        'TWINKLING STAR (SATTAI), ROPES, CANDLES & PENCILS',
        'CHILDRENS FANCY NOVELTIES', 'PEACOCK', 'COLOUR MATCHES',
        'FLYING ROCKETS', 'ONE SOUND CRACKERS', 'BIJILI CRACKERS', 'BOMBS',
        'PAPER CONFETTI', 'MULTI SOUND MAGIC CRACKERS',
        'FANCY SKY SHOTS - MINI', 'FANCY SKY SHOTS - MINI | PREMIUM SERIES',
        'SKY SHOTS | BUDGET SERIES', 'SKY SHOTS | PREMIUM SERIES',
        '2026 SPECIALS COLOR NAYAGARA SERIES', 'SKY SHOTS | PREMIUM SERIES - 2',
        'REPEATING MULTI COLOR SKY SHOTS | BUDGET SERIES',
        'REPEATING MULTI COLOR SKY SHOTS | Economy Series',
        'REPEATING MULTI COLOR SKY SHOTS | Premium Series',
        'SET OUTS | PREMIUM SERIES', 'PENTAGON PREMIUM SERIES | ARD Fireworks',
        'MAGIC CRACKERS | BUDGET SERIES', 'MAGIC CRACKERS | ELITE SERIES',
        'MAGIC CRACKERS | PREMIUM SERIES', 'SMOKE SPECIALS',
        'BUDGET FRIENDLY GIFTBOXES',
    ];
    $normCat = function($s) {
        $s = strtoupper($s ?? '');
        $s = preg_replace('/[^A-Z0-9]+/', ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    };
    $refNorm = array_map($normCat, $refCategoryOrder);
    $unseeded = $pdo->query("SELECT id, name FROM categories WHERE sort_order IS NULL")->fetchAll();
    if ($unseeded) {
        $seedStmt = $pdo->prepare("UPDATE categories SET sort_order=? WHERE id=?");
        foreach ($unseeded as $cat) {
            $n = $normCat($cat['name']);
            if ($n === '') continue;
            $bestIdx = null;
            foreach ($refNorm as $idx => $rn) {
                if ($rn === $n) { $bestIdx = $idx; break; }
            }
            if ($bestIdx === null) {
                foreach ($refNorm as $idx => $rn) {
                    if (strpos($rn, $n) !== false || strpos($n, $rn) !== false) { $bestIdx = $idx; break; }
                }
            }
            if ($bestIdx !== null) {
                $seedStmt->execute([($bestIdx + 1) * 10, $cat['id']]);
            }
        }
    }
} catch (Exception $e) {}

if ($method === 'GET') {
    if (!empty($_GET['id'])) {
        $s = $pdo->prepare("SELECT c.id, c.name, c.sku_prefix, c.sort_order, c.description, c.color, c.created_at, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category = c.name WHERE c.id=? GROUP BY c.id");
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
    $s = $pdo->prepare("SELECT c.id, c.name, c.sku_prefix, c.sort_order, c.description, c.color, c.created_at, COUNT(p.id) AS product_count
                        FROM categories c LEFT JOIN products p ON p.category = c.name
                        $where GROUP BY c.id ORDER BY (c.sort_order IS NULL), c.sort_order, c.name");
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
    $sortOrder = (array_key_exists('sort_order', $b) && $b['sort_order'] !== '' && $b['sort_order'] !== null) ? (int)$b['sort_order'] : null;
    $pdo->prepare("INSERT INTO categories (name, sku_prefix, sort_order, description, color) VALUES (?,?,?,?,?)")
        ->execute([$name, $skuPrefix, $sortOrder, trim($b['description'] ?? ''), trim($b['color'] ?? '')]);
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
    $sortOrder = (array_key_exists('sort_order', $b) && $b['sort_order'] !== '' && $b['sort_order'] !== null) ? (int)$b['sort_order'] : null;
    $pdo->prepare("UPDATE categories SET name=?, sku_prefix=?, sort_order=?, description=?, color=? WHERE id=?")
        ->execute([$name, $skuPrefix, $sortOrder, trim($b['description'] ?? ''), trim($b['color'] ?? ''), (int)$b['id']]);
    // Rename category on all products
    if ($oldName && $oldName !== $name) {
        $pdo->prepare("UPDATE products SET category=? WHERE category=?")->execute([$name, $oldName]);
    }
    auditLog($pdo, 'update_category', 'category', (int)$b['id'], $name);
    jsonOk(null, 'Category updated');
}

if ($method === 'DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    requireRole('admin','manager','partner');
    $id = (int)($_GET['id'] ?? 0); if (!$id) jsonError('ID required');
    $name = $pdo->query("SELECT name FROM categories WHERE id=$id")->fetchColumn();
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
    auditLog($pdo, 'delete_category', 'category', $id, $name);
    jsonOk(null, 'Category deleted');
}
