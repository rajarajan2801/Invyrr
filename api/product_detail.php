<?php
/**
 * Product Detail / Ledger API
 * GET  /api/product_detail.php?id=N&from=YYYY-MM-DD&to=YYYY-MM-DD
 * POST /api/product_detail.php  {action:'recalculate', id:N}  -- admin only,
 *      resets products.stock/product_locations.stock to match the ledger
 *      history (stock_in/stock_out/stock_adjustments/stock_transfers) when
 *      the live column has drifted from it -- see the 'Recalculate' button
 *      next to the Current Stock stat on this product's ledger page.
 */
require __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
startSession(); requireAuth();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = requireRole('admin');
    $b = json_decode(file_get_contents('php://input'), true) ?: [];
    if (($b['action'] ?? '') !== 'recalculate') jsonError('Unknown action');
    $pid = (int)($b['id'] ?? 0);
    if (!$pid) jsonError('Product ID required');
    $prod = $pdo->query("SELECT id,stock FROM products WHERE id=$pid")->fetch();
    if (!$prod) jsonError('Product not found', 404);

    $pdo->beginTransaction();
    try {
        // Company-wide total: lifetime stock_in minus stock_out plus
        // adjustments. Transfers are deliberately excluded here, same as
        // the summary totals below -- a transfer only moves stock between
        // locations, it never changes how much of this product the
        // company holds in total.
        $totalIn  = (int)$pdo->query("SELECT COALESCE(SUM(qty),0) FROM stock_in WHERE product_id=$pid")->fetchColumn();
        $totalOut = (int)$pdo->query("SELECT COALESCE(SUM(qty),0) FROM stock_out WHERE product_id=$pid")->fetchColumn();
        $totalAdj = (int)$pdo->query("SELECT COALESCE(SUM(qty_change),0) FROM stock_adjustments WHERE product_id=$pid")->fetchColumn();
        $oldStock = (int)$prod['stock'];
        $newStock = $totalIn - $totalOut + $totalAdj;
        $pdo->exec("UPDATE products SET stock=$newStock WHERE id=$pid");

        // Per-location: the same ledger math, scoped to this product's
        // movements at that one location, plus net transfers in/out of it
        // (unlike the company-wide total above, a single location's own
        // balance does depend on transfers -- that's the whole point of a
        // transfer). Floored at 0 like every other write to this column
        // already is (see api/invoices.php's GREATEST(0,...) deductions).
        $locRows = $pdo->query("SELECT location_id,stock FROM product_locations WHERE product_id=$pid")->fetchAll();
        $locResults = [];
        foreach ($locRows as $lr) {
            $locId = (int)$lr['location_id'];
            $lin  = (int)$pdo->query("SELECT COALESCE(SUM(qty),0) FROM stock_in WHERE product_id=$pid AND location_id=$locId")->fetchColumn();
            $lout = (int)$pdo->query("SELECT COALESCE(SUM(qty),0) FROM stock_out WHERE product_id=$pid AND location_id=$locId")->fetchColumn();
            $ladj = (int)$pdo->query("SELECT COALESCE(SUM(qty_change),0) FROM stock_adjustments WHERE product_id=$pid AND location_id=$locId")->fetchColumn();
            $tIn  = (int)$pdo->query("SELECT COALESCE(SUM(qty),0) FROM stock_transfers WHERE product_id=$pid AND to_location=$locId")->fetchColumn();
            $tOut = (int)$pdo->query("SELECT COALESCE(SUM(qty),0) FROM stock_transfers WHERE product_id=$pid AND from_location=$locId")->fetchColumn();
            $newLocStock = max(0, $lin - $lout + $ladj + $tIn - $tOut);
            $pdo->exec("UPDATE product_locations SET stock=$newLocStock WHERE product_id=$pid AND location_id=$locId");
            $locResults[] = ['location_id' => $locId, 'old_stock' => (int)$lr['stock'], 'new_stock' => $newLocStock];
        }

        auditLog($pdo, 'recalculate_stock', 'product', $pid, "Stock recalculated from ledger: $oldStock -> $newStock");
        $pdo->commit();
        jsonOk(['old_stock' => $oldStock, 'new_stock' => $newStock, 'locations' => $locResults], 'Stock recalculated from ledger history');
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonError($e->getMessage(), 500);
    }
    exit;
}

$id   = (int)($_GET['id'] ?? 0);
if (!$id) jsonError('Product ID required');

$from = $_GET['from'] ?? null;
$to   = $_GET['to']   ?? null;

// ── Product base info ─────────────────────────────────────
$stmt = $pdo->prepare("SELECT p.*,v.name AS vendor_name FROM products p LEFT JOIN vendors v ON v.id=p.vendor_id WHERE p.id=?");
$stmt->execute([$id]);
$prod = $stmt->fetch();
if (!$prod) jsonError('Product not found', 404);

// ── Stock by location ──────────────────────────────────────
$sl = $pdo->prepare("SELECT l.name AS location_name,l.is_default,pl.stock,pl.min_stock FROM product_locations pl JOIN locations l ON l.id=pl.location_id WHERE pl.product_id=? ORDER BY l.is_default DESC,l.name");
$sl->execute([$id]);
$locations = $sl->fetchAll();

// ── Build unified ledger ────────────────────────────────────
$txns = [];

// Stock In
$dateCond = '';
if ($from) $dateCond .= " AND si.date>='$from'";
if ($to)   $dateCond .= " AND si.date<='$to'";
$siRows = $pdo->query("SELECT 'stock_in' AS type, si.id, si.date AS txn_date, si.qty, si.cost,
    ROUND(si.qty*si.cost,0) AS amount,
    v.name AS vendor_name, l.name AS location_name,
    si.note AS description, po.po_number, NULL AS customer
    FROM stock_in si
    LEFT JOIN vendors v ON v.id=si.vendor_id
    LEFT JOIN locations l ON l.id=si.location_id
    LEFT JOIN purchase_orders po ON po.id=si.po_id
    WHERE si.product_id=$id $dateCond ORDER BY si.date,si.id")->fetchAll();
$txns = array_merge($txns, $siRows);

// Stock Out
$dateCond2 = '';
if ($from) $dateCond2 .= " AND so.date>='$from'";
if ($to)   $dateCond2 .= " AND so.date<='$to'";
$soRows = $pdo->query("SELECT 'stock_out' AS type, so.id, so.date AS txn_date, so.qty, so.sell_price AS cost,
    ROUND(so.sell_price*so.qty,0) AS amount,
    NULL AS vendor_name, l.name AS location_name,
    CONCAT('Sold to: ',COALESCE(so.customer,'Walk-in')) AS description,
    i.invoice_number AS po_number, so.customer
    FROM stock_out so
    LEFT JOIN locations l ON l.id=so.location_id
    LEFT JOIN invoices i ON i.id=so.invoice_id
    WHERE so.product_id=$id $dateCond2 ORDER BY so.date,so.id")->fetchAll();
$txns = array_merge($txns, $soRows);

// Stock Adjustments
$dateCond3 = '';
if ($from) $dateCond3 .= " AND sa.date>='$from'";
if ($to)   $dateCond3 .= " AND sa.date<='$to'";
// qty is the SIGNED qty_change (not ABS()) -- the frontend ledger
// (loadProductLedger() in index.php) relies on the sign to tell a real
// addition (recount upward, manual correction) apart from a deduction
// (recount downward, and every 'fulfillment'-reason row: an order loaded
// into Fulfillment, a substitute/gift added, or one of those reversed) --
// it puts a positive value in the In column and a negative one in Out,
// and folds it into the running Balance either way. ABS() here used to
// erase that sign entirely, so EVERY adjustment -- deductions included --
// showed up as a positive addition in both the In column and the running
// Balance, even though the real product.stock (and product_locations.stock)
// were always being adjusted correctly by api/adjustments.php; only this
// ledger's own display/reconstruction of history was wrong.
$adjRows = $pdo->query("SELECT 'adjustment' AS type, sa.id, sa.date AS txn_date,
    sa.qty_change AS qty, 0 AS cost, 0 AS amount,
    NULL AS vendor_name, l.name AS location_name,
    CONCAT(sa.reason, CASE WHEN sa.note IS NOT NULL AND sa.note!='' THEN CONCAT(': ',sa.note) ELSE '' END) AS description,
    NULL AS po_number, NULL AS customer
    FROM stock_adjustments sa
    LEFT JOIN locations l ON l.id=sa.location_id
    WHERE sa.product_id=$id $dateCond3 ORDER BY sa.date,sa.id")->fetchAll();
$txns = array_merge($txns, $adjRows);

// Sort all by date then id
usort($txns, function($a,$b){
    $d = strcmp($a['txn_date'], $b['txn_date']);
    return $d !== 0 ? $d : ((int)$a['id'] - (int)$b['id']);
});

// ── Open Purchase Orders ───────────────────────────────────
$openPOs = $pdo->prepare("
    SELECT po.po_number, po.status, po.expected_date, v.name AS vendor_name,
           poi.qty_ordered, poi.qty_received, poi.cost,
           (poi.qty_ordered - COALESCE(poi.qty_received,0)) AS pending_qty
    FROM purchase_order_items poi
    JOIN purchase_orders po ON po.id=poi.po_id
    LEFT JOIN vendors v ON v.id=po.vendor_id
    WHERE poi.product_id=? AND po.status IN ('draft','sent','partial')
    ORDER BY po.created_at DESC LIMIT 20");
$openPOs->execute([$id]);

// ── Summary ────────────────────────────────────────────────
$totalIn     = (int)$pdo->query("SELECT COALESCE(SUM(qty),0) FROM stock_in WHERE product_id=$id")->fetchColumn();
$totalOut    = (int)$pdo->query("SELECT COALESCE(SUM(qty),0) FROM stock_out WHERE product_id=$id")->fetchColumn();
$totalRev    = (float)$pdo->query("SELECT COALESCE(SUM(sell_price*qty),0) FROM stock_out WHERE product_id=$id")->fetchColumn();
$totalProfit = (float)$pdo->query("SELECT COALESCE(SUM((sell_price-cost)*qty),0) FROM stock_out WHERE product_id=$id")->fetchColumn();
$totalAdj    = (int)$pdo->query("SELECT COALESCE(SUM(qty_change),0) FROM stock_adjustments WHERE product_id=$id")->fetchColumn();

jsonOk([
    'product'      => $prod,
    'locations'    => $locations,
    'transactions' => $txns,
    'open_orders'  => $openPOs->fetchAll(),
    'summary'      => [
        'total_in'      => $totalIn,
        'total_out'     => $totalOut,
        'total_revenue' => round($totalRev, 2),
        'total_profit'  => round($totalProfit, 2),
        'total_adj'     => $totalAdj,
        'current_stock' => (int)$prod['stock'],
    ],
]);
