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

$pdo->exec("CREATE TABLE IF NOT EXISTS combo_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    combo_id INT UNSIGNED NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    FOREIGN KEY (combo_id) REFERENCES combos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// The product this combo actually ships as -- e.g. a "3K Combo Box"
// product row with its own SKU and stock count. Lets a combo be sold
// and stocked like any other product (Estimates, Fulfillment, the
// storefront) while combo_items stays purely the recipe/costing
// breakdown of what physically goes inside. Nullable: a combo can exist
// as a pure costing worksheet before it's ever linked to something
// sellable.
try { $pdo->exec("ALTER TABLE combos ADD COLUMN product_id INT NULL"); } catch (Exception $e) {}

// ── GET ───────────────────────────────────────────────────
if ($method === 'GET') {

    if (!empty($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("
            SELECT c.*, p.name AS product_name, p.sku AS product_sku, p.stock AS product_stock
            FROM combos c
            LEFT JOIN products p ON p.id = c.product_id
            WHERE c.id=?");
        $stmt->execute([$id]);
        $combo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$combo) jsonError('Combo not found', 404);

        // Items with live product data (price, cost, total stock)
        $items = $pdo->prepare("
            SELECT ci.id, ci.product_id, ci.qty,
                   p.name, p.sku, p.item_code, p.brand, p.category, p.unit,
                   p.sell AS sell_price, p.cost,
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
        SELECT c.*, p.name AS product_name, p.sku AS product_sku, p.stock AS product_stock,
               (SELECT COUNT(*)                    FROM combo_items ci WHERE ci.combo_id=c.id) AS item_count,
               (SELECT COALESCE(SUM(ci.qty),0)     FROM combo_items ci WHERE ci.combo_id=c.id) AS total_units,
               (SELECT COALESCE(SUM(ci.qty*p2.sell),0) FROM combo_items ci JOIN products p2 ON p2.id=ci.product_id WHERE ci.combo_id=c.id) AS sell_total,
               (SELECT COALESCE(SUM(ci.qty*p2.cost),0) FROM combo_items ci JOIN products p2 ON p2.id=ci.product_id WHERE ci.combo_id=c.id) AS cost_total
        FROM combos c
        LEFT JOIN products p ON p.id = c.product_id
        ORDER BY c.name")->fetchAll(PDO::FETCH_ASSOC);
    jsonList($rows);
}

// ── POST ?action=assemble: batch-produce combo boxes ────────
// Deducts each component's stock (qty * how many boxes) and credits the
// combo's own linked product by that many boxes, all in one
// transaction -- an all-or-nothing "assembly run" for the pre-packed
// batches this shop actually builds ahead of time, not a per-order
// deduction (a combo order is just a normal sale of the linked product
// once it's been assembled and has its own stock).
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'assemble') {
    $u = requireAuth();
    $b = getBody();
    $comboId = (int)($b['combo_id'] ?? 0);
    $assembleQty = (int)($b['qty'] ?? 0);
    $locId = !empty($b['location_id']) ? (int)$b['location_id'] : null;
    $date = !empty($b['date']) ? $b['date'] : date('Y-m-d');
    if (!$comboId) jsonError('Combo is required');
    if ($assembleQty <= 0) jsonError('Enter how many boxes to assemble');

    $comboStmt = $pdo->prepare("SELECT * FROM combos WHERE id=?");
    $comboStmt->execute([$comboId]);
    $combo = $comboStmt->fetch(PDO::FETCH_ASSOC);
    if (!$combo) jsonError('Combo not found', 404);
    if (empty($combo['product_id'])) jsonError('Link a sellable product to this combo first (Edit Combo > Linked Product)');

    $itemsStmt = $pdo->prepare("SELECT ci.product_id, ci.qty, p.name, p.unit FROM combo_items ci JOIN products p ON p.id = ci.product_id WHERE ci.combo_id = ?");
    $itemsStmt->execute([$comboId]);
    $comboItemRows = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    if (!count($comboItemRows)) jsonError('This combo has no items to assemble');

    // Self-heal the reason enum -- stock_adjustments belongs to
    // api/adjustments.php, which may not have run yet on a fresh install.
    try { $pdo->exec("ALTER TABLE stock_adjustments MODIFY reason ENUM('damage','theft','correction','recount','other','fulfillment','combo_assembly') NOT NULL"); } catch (Exception $e) {}

    $pdo->beginTransaction();
    try {
        // Lock and check every component up front -- an all-or-nothing
        // batch, never partially deducted because the 30th of 45 items
        // ran short.
        $short = [];
        $need = [];
        foreach ($comboItemRows as $it) {
            $pid = (int)$it['product_id'];
            $reqQty = (int)$it['qty'] * $assembleQty;
            $row = $pdo->query("SELECT stock FROM products WHERE id=$pid FOR UPDATE")->fetch();
            if (!$row) throw new Exception("{$it['name']} no longer exists");
            $have = $locId
                ? (int)($pdo->query("SELECT stock FROM product_locations WHERE product_id=$pid AND location_id=$locId")->fetchColumn() ?: 0)
                : (int)$row['stock'];
            if ($have < $reqQty) {
                $short[] = "{$it['name']} (need $reqQty {$it['unit']}, have $have)";
            }
            $need[$pid] = $reqQty;
        }
        if ($short) {
            throw new Exception('Not enough stock for '.$assembleQty.' box(es) — short: '.implode('; ', $short));
        }

        $note = "Assembled $assembleQty x {$combo['name']}";
        $adjIns = $pdo->prepare("INSERT INTO stock_adjustments (product_id,location_id,qty_change,reason,note,date,created_by) VALUES (?,?,?,?,?,?,?)");

        foreach ($need as $pid => $reqQty) {
            $change = -$reqQty;
            $pdo->exec("UPDATE products SET stock=GREATEST(0,stock+($change)) WHERE id=$pid");
            if ($locId) $pdo->exec("INSERT INTO product_locations (product_id,location_id,stock,min_stock) VALUES ($pid,$locId,GREATEST(0,$change),0) ON DUPLICATE KEY UPDATE stock=GREATEST(0,stock+($change))");
            $adjIns->execute([$pid, $locId, $change, 'combo_assembly', $note, $date, $u['id']]);
        }

        // Credit the finished boxes.
        $prodId = (int)$combo['product_id'];
        $pdo->exec("UPDATE products SET stock=stock+$assembleQty WHERE id=$prodId");
        if ($locId) $pdo->exec("INSERT INTO product_locations (product_id,location_id,stock,min_stock) VALUES ($prodId,$locId,$assembleQty,0) ON DUPLICATE KEY UPDATE stock=stock+$assembleQty");
        $adjIns->execute([$prodId, $locId, $assembleQty, 'combo_assembly', "Assembled from combo: {$combo['name']}", $date, $u['id']]);

        $pdo->commit();
        auditLog($pdo, 'combo_assembly', 'combo', $comboId, "Assembled $assembleQty x \"{$combo['name']}\"");
        jsonOk(['assembled' => $assembleQty], 'Assembled '.$assembleQty.' × '.$combo['name']);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonError($e->getMessage());
    }
    exit;
}

// ── POST: create ──────────────────────────────────────────
if ($method === 'POST') {
    $b = getBody();
    $name = trim($b['name'] ?? '');
    if (!$name) jsonError('Combo name is required');
    $items = is_array($b['items'] ?? null) ? $b['items'] : [];
    if (!count($items)) jsonError('Add at least one product to the combo');

    $linkedProductId = !empty($b['product_id']) ? (int)$b['product_id'] : null;
    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO combos (name, target_price, sell_price, notes, product_id) VALUES (?,?,?,?,?)")
            ->execute([
                $name,
                round((float)($b['target_price'] ?? 0), 2),
                round((float)($b['sell_price'] ?? 0), 2),
                trim($b['notes'] ?? '') ?: null,
                $linkedProductId,
            ]);
        $comboId = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare("INSERT INTO combo_items (combo_id, product_id, qty) VALUES (?,?,?)");
        foreach ($items as $it) {
            $pid = (int)($it['product_id'] ?? 0);
            $qty = max(1, (int)($it['qty'] ?? 1));
            if ($pid) $ins->execute([$comboId, $pid, $qty]);
        }
        // Flip the linked product's own "Combo" flag on, so the two
        // previously-disconnected concepts (a product tagged Combo=Yes,
        // and this recipe) actually line up once someone links them.
        if ($linkedProductId) $pdo->exec("UPDATE products SET combo=1 WHERE id=$linkedProductId");
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

    $linkedProductId = !empty($b['product_id']) ? (int)$b['product_id'] : null;
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE combos SET name=?, target_price=?, sell_price=?, notes=?, product_id=? WHERE id=?")
            ->execute([
                $name,
                round((float)($b['target_price'] ?? 0), 2),
                round((float)($b['sell_price'] ?? 0), 2),
                trim($b['notes'] ?? '') ?: null,
                $linkedProductId,
                $id,
            ]);
        $pdo->prepare("DELETE FROM combo_items WHERE combo_id=?")->execute([$id]);
        $ins = $pdo->prepare("INSERT INTO combo_items (combo_id, product_id, qty) VALUES (?,?,?)");
        foreach ($items as $it) {
            $pid = (int)($it['product_id'] ?? 0);
            $qty = max(1, (int)($it['qty'] ?? 1));
            if ($pid) $ins->execute([$id, $pid, $qty]);
        }
        if ($linkedProductId) $pdo->exec("UPDATE products SET combo=1 WHERE id=$linkedProductId");
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
