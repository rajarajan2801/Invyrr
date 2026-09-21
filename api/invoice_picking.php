<?php
/**
 * Invyrr API — Estimate Fulfillment (Picking / Verification / Packing / Dispatch)
 *
 * A full pick -> verify -> pack -> dispatch pipeline for Estimates,
 * modeled on the Website Orders Fulfillment system (see
 * api/picking_sessions.php + the picking screens in index.php) but
 * built on Estimates' own normalized invoices/invoice_items tables
 * instead of a JSON-blob/localStorage session -- there's no PDF-import
 * or offline use case here, so the server is simply the source of
 * truth throughout.
 *
 * Stock stays deducted at Estimate creation time (unchanged from
 * before) for every item as originally listed. The one place this
 * endpoint DOES move stock is the unavailable/substitute flow below --
 * see toggle_unavailable / add_substitute for why.
 *
 * GET    ?                          -> list (paid, or flagged, not cancelled) estimates with pick progress
 * GET    ?id=N                      -> one estimate + items + substitutes, for the picking modal
 * POST   (default)                  -> save picked_qty per available (non-unavailable) item
 * POST   ?toggle_unavailable=1      -> mark/unmark one item unavailable (moves stock, see below)
 * POST   ?add_substitute=1          -> add/increment a substitute product for an unavailable item
 * POST   ?remove_substitute=1       -> remove a substitute (restores its stock)
 * POST   ?substitute_qty=1          -> change a substitute's picked quantity
 * POST   ?complete_picking=1        -> picking -> verification (every item picked or unavailable)
 * POST   ?verify=1                  -> verification -> packing (admin/manager/partner only)
 * POST   ?pack=1                    -> packing -> packed
 * POST   ?dispatch=1                -> packed -> dispatched (ship date/transport/LR/boxes)
 * POST   ?resolve_flagged=1         -> flagged -> picking, once payment is restored (admin/manager/partner only)
 */
require __DIR__.'/../includes/db.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}
startSession();
$u      = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

// ── Schema ─────────────────────────────────────────────────
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN pick_status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER status"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN picked_by VARCHAR(100) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN picked_at DATETIME DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN verified TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN verified_by VARCHAR(100) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN verified_at DATETIME DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN packed_by VARCHAR(100) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN packed_at DATETIME DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN ship_date DATE DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN transport_name VARCHAR(150) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN transport_phone VARCHAR(30) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN box_count INT DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN lr_number VARCHAR(64) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN dispatched_at DATETIME DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoice_items ADD COLUMN picked_qty INT NOT NULL DEFAULT 0"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoice_items ADD COLUMN unavailable TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
// Idempotency guard for the restore-stock-on-unavailable step below --
// without this, toggling unavailable off/on/off would restore the same
// stock twice.
try { $pdo->exec("ALTER TABLE invoice_items ADD COLUMN stock_restored TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
// customer_phone is normally added by api/invoices.php, but this endpoint
// queries it directly (GET list) -- guard it here too so this page works
// even if invoice_picking.php's patch is applied before that one.
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN customer_phone VARCHAR(20) DEFAULT '' AFTER customer_name"); } catch (Exception $e) {}

try { $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_item_substitutes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_item_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(200) DEFAULT '',
    sku VARCHAR(64) DEFAULT '',
    sell DECIMAL(10,2) NOT NULL DEFAULT 0,
    picked_qty INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_item (invoice_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch (Exception $e) {}

const PICK_SHORTFALL_TOLERANCE = 50.0; // ₹ — same tolerance Website Orders uses at verification

function invCanVerify(array $u): bool {
    return in_array($u['role'] ?? '', ['admin','manager','partner']);
}

// Only ever 'pending' or 'picking' -- the picking->verification jump is
// a deliberate action (?complete_picking=1 below), not something that
// happens automatically the instant the last item is ticked off, same
// as Website Orders' completePicking() being its own button/step.
function invPickDeriveStatus(array $items): string {
    foreach ($items as $it) {
        if (!empty($it['unavailable'])) return 'picking';
        if ((int)($it['picked_qty'] ?? 0) > 0) return 'picking';
    }
    return 'pending';
}

// Moves real inventory. $delta > 0 puts stock back (e.g. an item turned
// out to be unavailable so it was never actually handed over); $delta <
// 0 takes stock out (e.g. a substitute product is being given instead).
// Decrementing below 0 is guarded (GREATEST(0,...)) the same way every
// other stock-adjusting query in this app already guards it.
function invPickAdjustStock(PDO $pdo, int $productId, ?int $locationId, int $delta): void {
    if ($delta === 0) return;
    if ($delta > 0) {
        $pdo->exec("UPDATE products SET stock=stock+$delta WHERE id=$productId");
        if ($locationId) $pdo->exec("UPDATE product_locations SET stock=stock+$delta WHERE product_id=$productId AND location_id=$locationId");
    } else {
        $dec = -$delta;
        $pdo->exec("UPDATE products SET stock=GREATEST(0,stock-$dec) WHERE id=$productId");
        if ($locationId) $pdo->exec("UPDATE product_locations SET stock=GREATEST(0,stock-$dec) WHERE product_id=$productId AND location_id=$locationId");
    }
}
function invPickAvailableStock(PDO $pdo, int $productId, ?int $locationId): int {
    if ($locationId) {
        $s = $pdo->query("SELECT stock FROM product_locations WHERE product_id=$productId AND location_id=$locationId")->fetch();
        if ($s) return (int)$s['stock'];
    }
    return (int)$pdo->query("SELECT stock FROM products WHERE id=$productId")->fetchColumn();
}

function invPickLoadInvoice(PDO $pdo, int $id): ?array {
    $inv = $pdo->query("SELECT * FROM invoices WHERE id=$id")->fetch();
    return $inv ?: null;
}
function invPickLoadItems(PDO $pdo, int $id): array {
    $items = $pdo->query(
        "SELECT ii.id, ii.product_id, ii.product_name, ii.qty, ii.unit_price, ii.total, ii.picked_qty, ii.unavailable, ii.stock_restored, p.sku
         FROM invoice_items ii LEFT JOIN products p ON p.id = ii.product_id
         WHERE ii.invoice_id=$id ORDER BY ii.id"
    )->fetchAll();
    if (!$items) return [];
    $ids = implode(',', array_map(fn($it) => (int)$it['id'], $items));
    $subs = $pdo->query("SELECT * FROM invoice_item_substitutes WHERE invoice_item_id IN ($ids) ORDER BY id")->fetchAll();
    $byItem = [];
    foreach ($subs as $s) { $byItem[$s['invoice_item_id']][] = $s; }
    foreach ($items as &$it) { $it['substitutes'] = $byItem[$it['id']] ?? []; }
    return $items;
}

// ── GET list ─────────────────────────────────────────────
if ($method === 'GET' && empty($_GET['id'])) {
    $where = ["i.status != 'cancelled'", "(i.status = 'paid' OR i.pick_status = 'flagged')"];
    $params = [];
    if (!empty($_GET['q'])) {
        $like = '%'.$_GET['q'].'%';
        $where[] = '(i.invoice_number LIKE ? OR i.customer_name LIKE ?)';
        $params[] = $like; $params[] = $like;
    }
    $sql = "SELECT i.id,i.invoice_number,i.date,i.customer_name,i.customer_phone,i.status,i.total,
                   i.pick_status,i.picked_by,i.picked_at,i.verified,i.verified_by,i.verified_at,
                   i.packed_by,i.packed_at,i.ship_date,i.transport_name,i.lr_number,i.box_count,i.dispatched_at,
                   (SELECT COUNT(*) FROM invoice_items ii WHERE ii.invoice_id=i.id) AS item_count,
                   (SELECT COUNT(*) FROM invoice_items ii WHERE ii.invoice_id=i.id AND (ii.unavailable=1 OR ii.picked_qty>=ii.qty)) AS item_done_count
            FROM invoices i
            WHERE ".implode(' AND ', $where)."
            ORDER BY (i.pick_status='dispatched'), i.date DESC, i.id DESC LIMIT 500";
    $s = $pdo->prepare($sql); $s->execute($params);
    jsonList($s->fetchAll());
}

// ── GET single (for the picking modal) ────────────────────
if ($method === 'GET' && !empty($_GET['id'])) {
    $id  = (int)$_GET['id'];
    $inv = invPickLoadInvoice($pdo, $id);
    if (!$inv) jsonError('Not found', 404);
    $inv['items'] = invPickLoadItems($pdo, $id);
    jsonOk($inv);
}

// ── POST ?toggle_unavailable=1 ────────────────────────────
if ($method === 'POST' && !empty($_GET['toggle_unavailable'])) {
    $b = getBody();
    $id = (int)($b['id'] ?? 0); $itemId = (int)($b['item_id'] ?? 0);
    if (!$id || !$itemId) jsonError('ID required');
    $inv = invPickLoadInvoice($pdo, $id);
    if (!$inv) jsonError('Not found', 404);
    if (in_array($inv['pick_status'], ['packing','packed','dispatched'])) jsonError('This estimate has already moved past picking');
    $item = $pdo->query("SELECT * FROM invoice_items WHERE id=$itemId AND invoice_id=$id")->fetch();
    if (!$item) jsonError('Item not found', 404);
    $pdo->beginTransaction();
    try {
        if (empty($item['unavailable'])) {
            // Turning ON -- the whole line is being replaced by
            // substitute(s), so restore all of its stock (once only,
            // guarded by stock_restored) and reset any partial pick.
            if (empty($item['stock_restored'])) {
                invPickAdjustStock($pdo, (int)$item['product_id'], $inv['location_id'] ? (int)$inv['location_id'] : null, (int)$item['qty']);
            }
            $pdo->prepare("UPDATE invoice_items SET unavailable=1, stock_restored=1, picked_qty=0 WHERE id=?")->execute([$itemId]);
        } else {
            // Turning OFF -- only allowed once every substitute for this
            // item has been removed (each removal already restores its
            // own stock -- see remove_substitute below), then re-deduct
            // the stock this item's own product needs.
            $subCount = (int)$pdo->query("SELECT COUNT(*) FROM invoice_item_substitutes WHERE invoice_item_id=$itemId")->fetchColumn();
            if ($subCount > 0) throw new Exception('Remove its substitute(s) first');
            $avail = invPickAvailableStock($pdo, (int)$item['product_id'], $inv['location_id'] ? (int)$inv['location_id'] : null);
            if ($avail < (int)$item['qty']) throw new Exception("Only $avail in stock -- not enough to un-mark this as unavailable");
            invPickAdjustStock($pdo, (int)$item['product_id'], $inv['location_id'] ? (int)$inv['location_id'] : null, -(int)$item['qty']);
            $pdo->prepare("UPDATE invoice_items SET unavailable=0, stock_restored=0 WHERE id=?")->execute([$itemId]);
        }
        $items = invPickLoadItems($pdo, $id);
        $newStatus = invPickDeriveStatus($items);
        $pdo->prepare("UPDATE invoices SET pick_status=? WHERE id=?")->execute([$newStatus, $id]);
        $pdo->commit();
        jsonOk(['items' => $items, 'pick_status' => $newStatus]);
    } catch (Exception $e) { $pdo->rollBack(); jsonError($e->getMessage(), 500); }
}

// ── POST ?add_substitute=1 ────────────────────────────────
if ($method === 'POST' && !empty($_GET['add_substitute'])) {
    $b = getBody();
    $id = (int)($b['id'] ?? 0); $itemId = (int)($b['item_id'] ?? 0);
    $productId = (int)($b['product_id'] ?? 0); $qty = max(1, (int)($b['qty'] ?? 1));
    if (!$id || !$itemId || !$productId) jsonError('ID required');
    $inv = invPickLoadInvoice($pdo, $id);
    if (!$inv) jsonError('Not found', 404);
    if (in_array($inv['pick_status'], ['packing','packed','dispatched'])) jsonError('This estimate has already moved past picking/verification');
    $item = $pdo->query("SELECT * FROM invoice_items WHERE id=$itemId AND invoice_id=$id")->fetch();
    if (!$item) jsonError('Item not found', 404);
    if (empty($item['unavailable'])) jsonError('Mark the item unavailable first');
    $p = $pdo->query("SELECT * FROM products WHERE id=$productId")->fetch();
    if (!$p) jsonError('Product not found', 404);
    $pdo->beginTransaction();
    try {
        $locId = $inv['location_id'] ? (int)$inv['location_id'] : null;
        $avail = invPickAvailableStock($pdo, $productId, $locId);
        if ($avail < $qty) throw new Exception("Only $avail {$p['unit']} of '{$p['name']}' in stock");
        invPickAdjustStock($pdo, $productId, $locId, -$qty);
        $existing = $pdo->query("SELECT * FROM invoice_item_substitutes WHERE invoice_item_id=$itemId AND product_id=$productId")->fetch();
        if ($existing) {
            $pdo->prepare("UPDATE invoice_item_substitutes SET picked_qty=picked_qty+? WHERE id=?")->execute([$qty, $existing['id']]);
        } else {
            $pdo->prepare("INSERT INTO invoice_item_substitutes (invoice_item_id,product_id,product_name,sku,sell,picked_qty) VALUES (?,?,?,?,?,?)")
                ->execute([$itemId, $productId, $p['name'], $p['sku'] ?? '', (float)$p['sell'], $qty]);
        }
        auditLog($pdo, 'add_invoice_substitute', 'invoice', $id, "Substituted {$p['name']} x$qty on {$inv['invoice_number']}");
        $pdo->commit();
        jsonOk(['items' => invPickLoadItems($pdo, $id)]);
    } catch (Exception $e) { $pdo->rollBack(); jsonError($e->getMessage(), 500); }
}

// ── POST ?remove_substitute=1 ─────────────────────────────
if ($method === 'POST' && !empty($_GET['remove_substitute'])) {
    $b = getBody();
    $id = (int)($b['id'] ?? 0); $subId = (int)($b['sub_id'] ?? 0);
    if (!$id || !$subId) jsonError('ID required');
    $inv = invPickLoadInvoice($pdo, $id);
    if (!$inv) jsonError('Not found', 404);
    if (in_array($inv['pick_status'], ['packing','packed','dispatched'])) jsonError('This estimate has already moved past picking/verification');
    $sub = $pdo->query("SELECT s.* FROM invoice_item_substitutes s JOIN invoice_items ii ON ii.id=s.invoice_item_id WHERE s.id=$subId AND ii.invoice_id=$id")->fetch();
    if (!$sub) jsonError('Substitute not found', 404);
    $pdo->beginTransaction();
    try {
        invPickAdjustStock($pdo, (int)$sub['product_id'], $inv['location_id'] ? (int)$inv['location_id'] : null, (int)$sub['picked_qty']);
        $pdo->exec("DELETE FROM invoice_item_substitutes WHERE id=$subId");
        $pdo->commit();
        jsonOk(['items' => invPickLoadItems($pdo, $id)]);
    } catch (Exception $e) { $pdo->rollBack(); jsonError($e->getMessage(), 500); }
}

// ── POST ?substitute_qty=1 ────────────────────────────────
if ($method === 'POST' && !empty($_GET['substitute_qty'])) {
    $b = getBody();
    $id = (int)($b['id'] ?? 0); $subId = (int)($b['sub_id'] ?? 0); $qty = max(0, (int)($b['qty'] ?? 0));
    if (!$id || !$subId) jsonError('ID required');
    $inv = invPickLoadInvoice($pdo, $id);
    if (!$inv) jsonError('Not found', 404);
    if (in_array($inv['pick_status'], ['packing','packed','dispatched'])) jsonError('This estimate has already moved past picking/verification');
    $sub = $pdo->query("SELECT s.* FROM invoice_item_substitutes s JOIN invoice_items ii ON ii.id=s.invoice_item_id WHERE s.id=$subId AND ii.invoice_id=$id")->fetch();
    if (!$sub) jsonError('Substitute not found', 404);
    $delta = $qty - (int)$sub['picked_qty'];
    $pdo->beginTransaction();
    try {
        if ($delta > 0) {
            $avail = invPickAvailableStock($pdo, (int)$sub['product_id'], $inv['location_id'] ? (int)$inv['location_id'] : null);
            if ($avail < $delta) throw new Exception("Only $avail more in stock");
        }
        invPickAdjustStock($pdo, (int)$sub['product_id'], $inv['location_id'] ? (int)$inv['location_id'] : null, -$delta);
        $pdo->prepare("UPDATE invoice_item_substitutes SET picked_qty=? WHERE id=?")->execute([$qty, $subId]);
        $pdo->commit();
        jsonOk(['items' => invPickLoadItems($pdo, $id)]);
    } catch (Exception $e) { $pdo->rollBack(); jsonError($e->getMessage(), 500); }
}

// ── POST ?complete_picking=1 ──────────────────────────────
if ($method === 'POST' && !empty($_GET['complete_picking'])) {
    $b = getBody();
    $id = (int)($b['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $inv = invPickLoadInvoice($pdo, $id);
    if (!$inv) jsonError('Not found', 404);
    if ($inv['pick_status'] === 'flagged') jsonError('This estimate is flagged for a payment issue — resolve it before continuing');
    $items = invPickLoadItems($pdo, $id);
    foreach ($items as $it) {
        if (empty($it['unavailable']) && (int)$it['picked_qty'] < (int)$it['qty']) {
            jsonError("'{$it['product_name']}' isn't fully picked yet — pick it or mark it unavailable");
        }
    }
    $pdo->prepare("UPDATE invoices SET pick_status='verification', picked_by=?, picked_at=NOW() WHERE id=?")
        ->execute([$u['name'] ?? 'User', $id]);
    auditLog($pdo, 'complete_invoice_picking', 'invoice', $id, "Picking complete for {$inv['invoice_number']}");
    jsonOk(null, 'Picking complete — ready for verification');
}

// ── POST ?verify=1 ─────────────────────────────────────────
if ($method === 'POST' && !empty($_GET['verify'])) {
    if (!invCanVerify($u)) jsonError('Only admin, manager, or partner can verify', 403);
    $b = getBody();
    $id = (int)($b['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $inv = invPickLoadInvoice($pdo, $id);
    if (!$inv) jsonError('Not found', 404);
    if ($inv['pick_status'] !== 'verification') jsonError('This estimate is not ready for verification');
    $items = invPickLoadItems($pdo, $id);
    foreach ($items as $it) {
        if (empty($it['unavailable'])) continue;
        $target = (float)$it['qty'] * (float)$it['unit_price'];
        $subValue = array_reduce($it['substitutes'], fn($sum, $s) => $sum + (float)$s['sell'] * (float)$s['picked_qty'], 0.0);
        $diff = abs($target - $subValue);
        if ($diff > PICK_SHORTFALL_TOLERANCE) {
            jsonError("'{$it['product_name']}' substitutes are ₹".number_format($diff, 2)." off the original value — add/adjust a substitute before verifying");
        }
    }
    $pdo->prepare("UPDATE invoices SET verified=1, verified_by=?, verified_at=NOW(), pick_status='packing' WHERE id=?")
        ->execute([$u['name'] ?? 'User', $id]);
    auditLog($pdo, 'verify_invoice_picking', 'invoice', $id, "Verified picking for {$inv['invoice_number']}");
    jsonOk(null, 'Verified — ready for packing');
}

// ── POST ?pack=1 ───────────────────────────────────────────
if ($method === 'POST' && !empty($_GET['pack'])) {
    $b = getBody();
    $id = (int)($b['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $inv = invPickLoadInvoice($pdo, $id);
    if (!$inv) jsonError('Not found', 404);
    if ($inv['pick_status'] !== 'packing') jsonError('This estimate must be in Packing before it can be marked Packed');
    $pdo->prepare("UPDATE invoices SET pick_status='packed', packed_by=?, packed_at=NOW() WHERE id=?")
        ->execute([$u['name'] ?? 'User', $id]);
    auditLog($pdo, 'pack_invoice', 'invoice', $id, "Marked Packed: {$inv['invoice_number']}");
    jsonOk(null, 'Marked Packed');
}

// ── POST ?dispatch=1 ───────────────────────────────────────
if ($method === 'POST' && !empty($_GET['dispatch'])) {
    $b = getBody();
    $id = (int)($b['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $inv = invPickLoadInvoice($pdo, $id);
    if (!$inv) jsonError('Not found', 404);
    if ($inv['pick_status'] !== 'packed') jsonError('This estimate must be marked Packed before it can be dispatched');
    $shipDate  = $b['ship_date'] ?? '';
    $transport = trim($b['transport_name'] ?? '');
    $lr        = trim($b['lr_number'] ?? '');
    $boxCount  = (int)($b['box_count'] ?? 0);
    if (!$shipDate) jsonError('Ship date is required');
    if (!$transport) jsonError('Transport name is required');
    if ($boxCount <= 0) jsonError('Number of boxes is required');
    $transportPhone = '';
    $t = $pdo->prepare("SELECT phone FROM transports WHERE name=? LIMIT 1"); $t->execute([$transport]);
    if ($row = $t->fetch()) $transportPhone = $row['phone'] ?? '';
    $pdo->prepare("UPDATE invoices SET pick_status='dispatched', dispatched_at=NOW(), ship_date=?, transport_name=?, transport_phone=?, lr_number=?, box_count=? WHERE id=?")
        ->execute([$shipDate, $transport, $transportPhone, $lr, $boxCount, $id]);
    auditLog($pdo, 'dispatch_invoice', 'invoice', $id, "Dispatched {$inv['invoice_number']} via $transport");
    jsonOk(null, 'Dispatched');
}

// ── POST ?resolve_flagged=1 ───────────────────────────────
if ($method === 'POST' && !empty($_GET['resolve_flagged'])) {
    if (!invCanVerify($u)) jsonError('Only admin, manager, or partner can resolve a flagged estimate', 403);
    $b = getBody();
    $id = (int)($b['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $inv = invPickLoadInvoice($pdo, $id);
    if (!$inv) jsonError('Not found', 404);
    if ($inv['pick_status'] !== 'flagged') jsonError('This estimate is not flagged');
    if ($inv['status'] !== 'paid') jsonError('Payment is still short of the total — this can\'t be resolved yet');
    $pdo->prepare("UPDATE invoices SET pick_status='picking' WHERE id=?")->execute([$id]);
    auditLog($pdo, 'resolve_flagged_invoice', 'invoice', $id, "Un-flagged {$inv['invoice_number']} — payment restored");
    jsonOk(null, 'Resolved — back to Picking');
}

// ── POST: save picked_qty per (available) item ────────────
if ($method === 'POST') {
    $b  = getBody();
    $id = (int)($b['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $inv = invPickLoadInvoice($pdo, $id);
    if (!$inv) jsonError('Not found', 404);
    if ($inv['status'] === 'cancelled') jsonError('This estimate is cancelled');
    if ($inv['pick_status'] === 'flagged') jsonError('This estimate is flagged for a payment issue — resolve it before continuing');
    if (!in_array($inv['pick_status'], ['pending','picking'])) jsonError('Picking is already complete for this estimate');

    $items    = is_array($b['items'] ?? null) ? $b['items'] : [];
    $existing = $pdo->query("SELECT id, qty, unavailable FROM invoice_items WHERE invoice_id=$id")->fetchAll(PDO::FETCH_ASSOC);
    $existingById = [];
    foreach ($existing as $row) $existingById[(int)$row['id']] = $row;
    $upd = $pdo->prepare("UPDATE invoice_items SET picked_qty=? WHERE id=? AND invoice_id=?");
    foreach ($items as $it) {
        $itemId = (int)($it['item_id'] ?? 0);
        if (!$itemId || !isset($existingById[$itemId])) continue;
        if (!empty($existingById[$itemId]['unavailable'])) continue; // unavailable items are fulfilled via substitutes, not picked_qty
        $qty = (int)$existingById[$itemId]['qty'];
        $pq  = max(0, min($qty, (int)($it['picked_qty'] ?? 0)));
        $upd->execute([$pq, $itemId, $id]);
    }

    $allItems  = invPickLoadItems($pdo, $id);
    $newStatus = invPickDeriveStatus($allItems);
    $who       = $u['name'] ?? 'User';
    $pdo->prepare("UPDATE invoices SET pick_status=?, picked_by=? WHERE id=?")->execute([$newStatus, $who, $id]);
    auditLog($pdo, 'update_invoice_picking', 'invoice', $id, "Picking progress for {$inv['invoice_number']} -> $newStatus");
    jsonOk(['pick_status' => $newStatus, 'items' => $allItems], 'Progress saved');
}
