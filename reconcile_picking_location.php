<?php
// One-time cleanup: backfills picking_sessions.location_id to 'RR Crackers'
// for every existing order that has no location set.
//
// Why this was needed: picking_sessions.location_id already defaults to
// 'RR Crackers' server-side (api/picking_sessions.php), but only on
// INSERT for a brand-new row -- a routine UPDATE never overwrites an
// existing NULL location_id, by design (that's what stops a plain status
// save from silently resetting an order's location). Any order saved
// before the location feature existed, or created client-side while the
// #pick-location dropdown's location list hadn't finished loading yet
// (see getPickLocationChoiceAsync() in index.php), was left with a NULL
// location_id and stayed that way.
//
// A blank location_id matters beyond just the label shown on screen: the
// Fulfillment picking screen looks up each item's stock at that specific
// location (api/products.php's display_stock). With no location_id, it
// falls back to the product's single global `stock` column instead --
// which can disagree with the real per-location figure -- and an item
// that's actually in stock at RR Crackers gets wrongly flagged
// Unavailable.
//
// Visit once (e.g. https://your-app/reconcile_picking_location.php?secret=YOUR_BACKUP_SECRET),
// confirm it reports what it fixed, then delete this file from the repo.
require __DIR__ . '/includes/db.php';

header('Content-Type: text/plain');

$secret = _env('BACKUP_SECRET', '');
if ($secret && ($_GET['secret'] ?? '') !== $secret) {
    http_response_code(403);
    die("Forbidden. Pass ?secret=YOUR_BACKUP_SECRET");
}

$pdo = getDB();

// Same fallback order the server already uses for a brand-new session:
// RR Crackers by name, then whichever location is flagged default, then
// just the first location on record.
$loc = $pdo->query("SELECT id, name FROM locations WHERE name = 'RR Crackers' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$loc) $loc = $pdo->query("SELECT id, name FROM locations WHERE is_default = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$loc) $loc = $pdo->query("SELECT id, name FROM locations ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$loc) {
    die("No locations exist in the locations table -- nothing to default to. Create a location first.\n");
}

echo "Defaulting blank-location orders to: {$loc['name']} (id {$loc['id']})\n\n";

$rows = $pdo->query("SELECT id, order_no FROM picking_sessions WHERE location_id IS NULL")
             ->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($rows) . " order(s) with no location set.\n\n";

$fixed = 0;
foreach ($rows as $row) {
    $pdo->prepare("UPDATE picking_sessions SET location_id = ? WHERE id = ?")
        ->execute([$loc['id'], $row['id']]);
    echo "Fixed: order {$row['order_no']} -> {$loc['name']}\n";
    $fixed++;
}

echo "\nDone. Set location on $fixed order(s).\n";
echo "Note: this only fixes which location an order is picked from -- it does not\n";
echo "re-check stock or clear any stale 'Unavailable' flag on individual items.\n";
echo "Those will self-correct the next time each order's picking screen is opened\n";
echo "(loadPickItemStock() now auto-clears an auto-flagged item once its live\n";
echo "stock at the correct location covers the ordered quantity).\n\n";
echo "Delete reconcile_picking_location.php from your repo now!\n";
