<?php
/**
 * Invyrr API — Estimate Picking / Verification
 *
 * An isolated fulfillment workflow for Estimates, deliberately separate
 * from picking_sessions.php (which belongs to Website Orders). Stock
 * stays deducted at Estimate creation time either way -- see
 * api/invoices.php -- this endpoint never touches stock. It only tracks
 * whether what was promised on the estimate has actually been physically
 * picked and double-checked by a second person.
 *
 * GET    /api/invoice_picking.php                  → list (open/paid, not cancelled) estimates with pick progress
 * GET    /api/invoice_picking.php?id=N              → one estimate + items, for the picking modal
 * POST   /api/invoice_picking.php                   → save picked_qty per item (auto-derives pick_status)
 * POST   /api/invoice_picking.php?verify=1          → mark as verified (admin/manager/partner only, same as
 *                                                      CAN_VERIFY on the Fulfillment dashboard)
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

try { $pdo->exec("ALTER TABLE invoices ADD COLUMN pick_status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER status"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN picked_by VARCHAR(100) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN picked_at DATETIME DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN verified_by VARCHAR(100) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN verified_at DATETIME DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoice_items ADD COLUMN picked_qty INT NOT NULL DEFAULT 0"); } catch (Exception $e) {}

// pending: nothing picked yet. picking: some but not all of the total
// quantity across every line has been picked. picked: every line's
// picked_qty has reached its ordered qty -- ready for a second person to
// verify. verified is only ever set by the dedicated verify action below,
// never derived here.
function invPickDeriveStatus(array $items): string {
    $totalQty = 0; $pickedQty = 0; $anyPicked = false;
    foreach ($items as $it) {
        $totalQty += (int)$it['qty'];
        $pq = max(0, min((int)$it['qty'], (int)$it['picked_qty']));
        $pickedQty += $pq;
        if ($pq > 0) $anyPicked = true;
    }
    if ($totalQty > 0 && $pickedQty >= $totalQty) return 'picked';
    if ($anyPicked) return 'picking';
    return 'pending';
}

// ── GET list ─────────────────────────────────────────────
if ($method === 'GET' && empty($_GET['id'])) {
    $where = ["i.status != 'cancelled'"];
    $params = [];
    if (!empty($_GET['q'])) {
        $like = '%'.$_GET['q'].'%';
        $where[] = '(i.invoice_number LIKE ? OR i.customer_name LIKE ?)';
        $params[] = $like; $params[] = $like;
    }
    if (!empty($_GET['pick_status'])) { $where[] = 'i.pick_status=?'; $params[] = $_GET['pick_status']; }
    $sql = "SELECT i.id,i.invoice_number,i.date,i.customer_name,i.customer_phone,i.status,
                   i.pick_status,i.picked_by,i.picked_at,i.verified_by,i.verified_at,
                   (SELECT COUNT(*) FROM invoice_items ii WHERE ii.invoice_id=i.id) AS item_count
            FROM invoices i
            WHERE ".implode(' AND ', $where)."
            ORDER BY (i.pick_status='verified'), i.date DESC, i.id DESC LIMIT 500";
    $s = $pdo->prepare($sql); $s->execute($params);
    jsonList($s->fetchAll());
}

// ── GET single (for the picking modal) ────────────────────
if ($method === 'GET' && !empty($_GET['id'])) {
    $id  = (int)$_GET['id'];
    $inv = $pdo->query("SELECT * FROM invoices WHERE id=$id")->fetch();
    if (!$inv) jsonError('Not found', 404);
    $inv['items'] = $pdo->query(
        "SELECT ii.id, ii.product_name, ii.qty, ii.picked_qty, p.sku
         FROM invoice_items ii LEFT JOIN products p ON p.id = ii.product_id
         WHERE ii.invoice_id=$id ORDER BY ii.id"
    )->fetchAll();
    jsonOk($inv);
}

// ── POST ?verify=1: mark as verified ──────────────────────
if ($method === 'POST' && !empty($_GET['verify'])) {
    if (!in_array($u['role'] ?? '', ['admin','manager','partner'])) {
        jsonError('Only admin, manager, or partner can verify', 403);
    }
    $b  = getBody();
    $id = (int)($b['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $inv = $pdo->query("SELECT * FROM invoices WHERE id=$id")->fetch();
    if (!$inv) jsonError('Not found', 404);
    if ($inv['pick_status'] !== 'picked') jsonError('All items must be fully picked before this can be verified');
    $pdo->prepare("UPDATE invoices SET pick_status='verified', verified_by=?, verified_at=NOW() WHERE id=?")
        ->execute([$u['name'] ?? 'User', $id]);
    auditLog($pdo, 'verify_invoice_picking', 'invoice', $id, "Verified picking for {$inv['invoice_number']}");
    jsonOk(null, 'Verified');
}

// ── POST: save picked_qty per item ────────────────────────
if ($method === 'POST') {
    $b  = getBody();
    $id = (int)($b['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $inv = $pdo->query("SELECT * FROM invoices WHERE id=$id")->fetch();
    if (!$inv) jsonError('Not found', 404);
    if ($inv['status'] === 'cancelled') jsonError('This estimate is cancelled');
    if ($inv['pick_status'] === 'verified') jsonError('Already verified — nothing left to update');

    $items    = is_array($b['items'] ?? null) ? $b['items'] : [];
    $existing = $pdo->query("SELECT id, qty FROM invoice_items WHERE invoice_id=$id")->fetchAll(PDO::FETCH_KEY_PAIR);
    $upd = $pdo->prepare("UPDATE invoice_items SET picked_qty=? WHERE id=? AND invoice_id=?");
    foreach ($items as $it) {
        $itemId = (int)($it['item_id'] ?? 0);
        if (!$itemId || !array_key_exists($itemId, $existing)) continue;
        $qty = (int)$existing[$itemId];
        $pq  = max(0, min($qty, (int)($it['picked_qty'] ?? 0)));
        $upd->execute([$pq, $itemId, $id]);
    }

    $allItems  = $pdo->query("SELECT qty, picked_qty FROM invoice_items WHERE invoice_id=$id")->fetchAll();
    $newStatus = invPickDeriveStatus($allItems);
    $who       = $u['name'] ?? 'User';
    if ($newStatus === 'picked') {
        $pdo->prepare("UPDATE invoices SET pick_status=?, picked_by=?, picked_at=NOW() WHERE id=?")
            ->execute([$newStatus, $who, $id]);
    } else {
        $pdo->prepare("UPDATE invoices SET pick_status=?, picked_by=? WHERE id=?")
            ->execute([$newStatus, $who, $id]);
    }
    auditLog($pdo, 'update_invoice_picking', 'invoice', $id, "Picking progress for {$inv['invoice_number']} -> $newStatus");
    jsonOk(['pick_status' => $newStatus], 'Progress saved');
}
