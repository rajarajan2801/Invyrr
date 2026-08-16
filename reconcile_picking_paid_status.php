<?php
// One-time cleanup: catches up any picking_sessions row that's stuck
// showing Payment Due even though its order is already fully paid.
//
// Why this was needed: syncPickingStatusForOrder() (includes/db.php)
// only runs at the moment a payment is recorded/edited/deleted, and
// until this fix it matched picking_sessions.order_no with a plain '='
// comparison. Order numbers pulled from a PDF/text estimate upload can
// carry stray \r/\n or padding whitespace that a plain match misses
// silently -- the payment goes through fine, website_orders shows Paid,
// but picking_sessions never got the memo and stayed on Payment Due.
// The comparison is now whitespace-tolerant going forward, but any
// order that already hit this before the fix landed needs a one-time
// catch-up pass, since nothing will re-trigger the sync for an order
// unless its payment is touched again.
//
// Visit once (e.g. https://your-app/reconcile_picking_paid_status.php?secret=YOUR_BACKUP_SECRET),
// confirm it reports what it fixed, then delete this file from the repo.
require __DIR__ . '/includes/db.php';

header('Content-Type: text/plain');

$secret = _env('BACKUP_SECRET', '');
if ($secret && ($_GET['secret'] ?? '') !== $secret) {
    http_response_code(403);
    die("Forbidden. Pass ?secret=YOUR_BACKUP_SECRET");
}

$pdo = getDB();

// Every picking_sessions row still sitting at Payment Due (or a blank/
// NULL status from an old row).
$rows = $pdo->query("SELECT id, order_no, status FROM picking_sessions
                      WHERE status = 'pending' OR status IS NULL OR status = ''")
             ->fetchAll(PDO::FETCH_ASSOC);

echo "Checking " . count($rows) . " order(s) currently at Payment Due...\n\n";

$fixed = 0;
$notFound = 0;
foreach ($rows as $row) {
    // Same whitespace-tolerant normalization as syncPickingStatusForOrder().
    $orderNo = trim(str_replace(["\r", "\n", "\t"], '', $row['order_no'] ?? ''));
    if ($orderNo === '') continue;

    $wo = $pdo->prepare("SELECT amount, amount_paid FROM (
            SELECT wo.amount, COALESCE(SUM(cp.amount),0) AS amount_paid
            FROM website_orders wo
            LEFT JOIN customer_payments cp ON cp.order_id = wo.id
            WHERE TRIM(REPLACE(REPLACE(REPLACE(wo.order_number,'\r',''),'\n',''),'\t','')) = ?
            GROUP BY wo.id
        ) t");
    $wo->execute([$orderNo]);
    $order = $wo->fetch(PDO::FETCH_ASSOC);

    if (!$order) { $notFound++; continue; }

    $isFullyPaid = (float)$order['amount'] > 0 && (float)$order['amount_paid'] >= (float)$order['amount'];
    if ($isFullyPaid) {
        $pdo->prepare("UPDATE picking_sessions SET status='paid' WHERE id=?")->execute([$row['id']]);
        echo "Fixed: order {$row['order_no']} -> Paid\n";
        $fixed++;
    }
}

echo "\nDone. Fixed $fixed order(s), $notFound had no matching website_orders row (never synced -- nothing to fix, not an error).\n\n";
echo "Delete reconcile_picking_paid_status.php from your repo now!\n";
