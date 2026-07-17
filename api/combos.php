<?php
/**
 * Invyrr API — Combo Builder
 * Build assorted product combos (e.g. ₹3000 / ₹5000 cracker gift boxes).
 *
 * GET    /api/combos.php            → list combos with computed totals
 * GET    /api/combos.php?id=N       → single combo with items + live product data
 * POST   /api/combos.php            → create { name, target_price, notes, items:[{product_id, qty}] }
 * PUT    /api/combos.php            → update (same body + id)
 * DELETE /api/combos.php?id=N       → delete (admin only)
 */
require __DIR__ . '/../includes/db.php';
startSession(); requireAuth();

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── Ensure tables exist ───────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS combos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    target_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    sell_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ensure packing_charges column exists
try { $pdo->exec("ALTER TABLE combos ADD COLUMN packing_charges DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER notes"); } catch (Exception $e) {}

$pdo->exec("CREATE TABLE IF NOT EXISTS combo_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    combo_id INT UNSIGNED NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    FOREIGN KEY (combo_id) REFERENCES combos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── GET ───────────────────────────────────────────────────
if ($method === 'GET') {

    if (!empty($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM combos WHERE id=?");
        $stmt->execute([$id]);
        $combo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$combo) jsonError('Combo not found', 404);

        // Items with live product data (price, cost, total stock)
        $items = $pdo->prepare("
            SELECT ci.id, ci.product_id, ci.qty,
                   p.name, p.sku, p.item_code, p.brand, p.category, p.unit,
                   p.sell AS sell_price, p.cost, COALESCE(p.landing_cost, p.cost) AS landing_cost,
                   COALESCE((SELECT SUM(pl.stock) FROM product_locations pl WHERE pl.product_id = p.id), 0) AS total_stock
            FROM combo_items ci
            JOIN products p ON p.id = ci.product_id
            WHERE ci.combo_id = ?
            ORDER BY p.name");
        $items->execute([$id]);
        $combo['items'] = $items->fetchAll(PDO::FETCH_ASSOC);
        jsonOk($combo);
    }

    // List with computed totals
    $rows = $pdo->query("
        SELECT c.*,
               (SELECT COUNT(*)                    FROM combo_items ci WHERE ci.combo_id=c.id) AS item_count,
               (SELECT COALESCE(SUM(ci.qty),0)     FROM combo_items ci WHERE ci.combo_id=c.id) AS total_units,
               (SELECT COALESCE(SUM(ci.qty*p.sell),0) FROM combo_items ci JOIN products p ON p.id=ci.product_id WHERE ci.combo_id=c.id) AS sell_total,
               (SELECT COALESCE(SUM(ci.qty*COALESCE(p.landing_cost,p.cost)),0) FROM combo_items ci JOIN products p ON p.id=ci.product_id WHERE ci.combo_id=c.id) + COALESCE(c.packing_charges,0) AS cost_total,
               (SELECT p2.id FROM products p2 WHERE p2.combo=1 AND LOWER(TRIM(CONVERT(p2.name USING utf8mb4)))=LOWER(TRIM(CONVERT(c.name USING utf8mb4))) LIMIT 1) AS linked_product_id
        FROM combos c
        ORDER BY c.name")->fetchAll(PDO::FETCH_ASSOC);
    jsonList($rows);
}

// ── POST: create ──────────────────────────────────────────
if ($method === 'POST') {
    $b = getBody();
    $name = trim($b['name'] ?? '');
    if (!$name) jsonError('Combo name is required');
    $items = is_array($b['items'] ?? null) ? $b['items'] : [];
    if (!count($items)) jsonError('Add at least one product to the combo');

    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO combos (name, target_price, sell_price, notes, packing_charges) VALUES (?,?,?,?,?)")
            ->execute([
                $name,
                round((float)($b['target_price'] ?? 0), 2),
                round((float)($b['sell_price'] ?? 0), 2),
                trim($b['notes'] ?? '') ?: null,
                round((float)($b['packing_charges'] ?? 0), 2),
            ]);
        $comboId = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare("INSERT INTO combo_items (combo_id, product_id, qty) VALUES (?,?,?)");
        foreach ($items as $it) {
            $pid = (int)($it['product_id'] ?? 0);
            $qty = max(1, (int)($it['qty'] ?? 1));
            if ($pid) $ins->execute([$comboId, $pid, $qty]);
        }
        $pdo->commit();
        auditLog($pdo, 'create', 'combo', $comboId, "Created combo: {$name}");
        jsonOk(['id' => $comboId], 'Combo saved');
    } catch (PDOException $e) {
        $pdo->rollBack();
        jsonError('Could not save combo: ' . $e->getMessage());
    }
}

// ── PUT: update ───────────────────────────────────────────
if ($method === 'PUT') {
    $b  = getBody();
    $id = (int)($b['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $name = trim($b['name'] ?? '');
    if (!$name) jsonError('Combo name is required');
    $items = is_array($b['items'] ?? null) ? $b['items'] : [];
    if (!count($items)) jsonError('Add at least one product to the combo');

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE combos SET name=?, target_price=?, sell_price=?, notes=?, packing_charges=? WHERE id=?")
            ->execute([
                $name,
                round((float)($b['target_price'] ?? 0), 2),
                round((float)($b['sell_price'] ?? 0), 2),
                trim($b['notes'] ?? '') ?: null,
                round((float)($b['packing_charges'] ?? 0), 2),
                $id,
            ]);
        $pdo->prepare("DELETE FROM combo_items WHERE combo_id=?")->execute([$id]);
        $ins = $pdo->prepare("INSERT INTO combo_items (combo_id, product_id, qty) VALUES (?,?,?)");
        foreach ($items as $it) {
            $pid = (int)($it['product_id'] ?? 0);
            $qty = max(1, (int)($it['qty'] ?? 1));
            if ($pid) $ins->execute([$id, $pid, $qty]);
        }
        $pdo->commit();
        auditLog($pdo, 'update', 'combo', $id, "Updated combo: {$name}");
        jsonOk(['id' => $id], 'Combo updated');
    } catch (PDOException $e) {
        $pdo->rollBack();
        jsonError('Could not update combo: ' . $e->getMessage());
    }
}

// ── DELETE ────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $pdo->prepare("DELETE FROM combos WHERE id=?")->execute([$id]);
    auditLog($pdo, 'delete', 'combo', $id, "Deleted combo #{$id}");
    jsonOk([], 'Combo deleted');
}

jsonError('Unknown action', 400);
