<?php
/**
 * Product Detail / Ledger API
 * GET /api/product_detail.php?id=N&from=YYYY-MM-DD&to=YYYY-MM-DD
 */
require __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
startSession(); requireAuth();

$id   = (int)($_GET['id'] ?? 0);
if (!$id) jsonError('Product ID required');

$pdo  = getDB();
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
$adjRows = $pdo->query("SELECT 'adjustment' AS type, sa.id, sa.date AS txn_date,
    ABS(sa.qty_change) AS qty, 0 AS cost, 0 AS amount,
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
