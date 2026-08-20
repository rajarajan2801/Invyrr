<?php
// One-time cleanup: backfills reference_no on every existing
// customer_payments row that doesn't have one, using its order's
// order_number.
//
// Why this was needed: recordCustomerPayment() (index.php) never sent a
// reference_no in the first place -- there's no Reference field in the
// payment modal -- so api/customer_payments.php always saved it blank.
// The API now defaults it to the order number for every NEW payment,
// but that only takes effect going forward; every payment recorded
// before that fix landed still has a blank reference_no and won't be
// touched again unless it's edited. This script does that catch-up pass
// once so the Payee Ledger's Reference column and any export show the
// estimate # for historical payments too.
//
// Visit once (e.g. https://your-app/reconcile_customer_payment_references.php?secret=YOUR_BACKUP_SECRET),
// confirm it reports what it fixed, then delete this file from the repo.
require __DIR__ . '/includes/db.php';

header('Content-Type: text/plain');

$secret = _env('BACKUP_SECRET', '');
if ($secret && ($_GET['secret'] ?? '') !== $secret) {
    http_response_code(403);
    die("Forbidden. Pass ?secret=YOUR_BACKUP_SECRET");
}

$pdo = getDB();

$rows = $pdo->query("
    SELECT cp.id, cp.order_id, wo.order_number
    FROM customer_payments cp
    JOIN website_orders wo ON wo.id = cp.order_id
    WHERE (cp.reference_no IS NULL OR cp.reference_no = '')
      AND wo.order_number IS NOT NULL AND wo.order_number != ''
")->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($rows) . " customer payment(s) with a blank reference_no and a resolvable order number...\n\n";

$fixed = 0;
$upd = $pdo->prepare("UPDATE customer_payments SET reference_no = ? WHERE id = ?");
foreach ($rows as $r) {
    $upd->execute([$r['order_number'], $r['id']]);
    echo "  Payment #{$r['id']} -> reference_no set to '{$r['order_number']}'\n";
    $fixed++;
}

echo "\nDone. Backfilled {$fixed} payment(s).\n";
