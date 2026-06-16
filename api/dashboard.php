<?php
/**
 * Invyrr API — Dashboard & Reports
 * All endpoints accept optional ?location_id=N to filter to a single store.
 *
 * GET /api/dashboard.php                      → summary stats + alerts + recent
 * GET /api/dashboard.php?report=pnl           → P&L per product
 * GET /api/dashboard.php?report=stock_value   → current stock value
 * GET /api/dashboard.php?report=vendor_summary→ vendor purchase totals
 * GET /api/dashboard.php?report=top_margin    → top products by margin
 * GET /api/dashboard.php?report=location_summary → stock across all locations
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonError('Method not allowed', 405);

$pdo        = getDB();
$report     = $_GET['report'] ?? null;
$locationId = !empty($_GET['location_id']) ? (int)$_GET['location_id'] : null;

// Build location WHERE fragments
$locJoinSI  = $locationId ? "AND si.location_id = $locationId" : '';
$locJoinSO  = $locationId ? "AND so.location_id = $locationId" : '';
$locWherePL = $locationId ? "AND pl.location_id = $locationId" : '';

// ── P&L per product ──────────────────────────────────────
if ($report === 'pnl') {
    $rows = $pdo->query("
        SELECT p.name AS product, p.brand,
               SUM(so.qty)                             AS sold_qty,
               SUM(so.sell_price * so.qty)             AS revenue,
               SUM(so.cost * so.qty)                   AS cogs,
               SUM((so.sell_price - so.cost) * so.qty) AS profit,
               ROUND(CASE WHEN SUM(so.sell_price * so.qty) > 0
                     THEN SUM((so.sell_price-so.cost)*so.qty) / SUM(so.sell_price*so.qty) * 100
                     ELSE 0 END, 1) AS margin_pct
        FROM stock_out so
        JOIN products p ON p.id = so.product_id
        WHERE 1=1 $locJoinSO
        GROUP BY so.product_id, p.name, p.brand
        ORDER BY profit DESC")->fetchAll();
    jsonList($rows);
}

// ── Stock value ──────────────────────────────────────────
if ($report === 'stock_value') {
    if ($locationId) {
        $rows = $pdo->query("
            SELECT p.name, p.brand, pl.stock, p.unit, p.cost, p.sell,
                   ROUND(pl.stock * p.cost, 2) AS cost_value,
                   ROUND(pl.stock * p.sell, 2) AS sell_value
            FROM product_locations pl
            JOIN products p ON p.id = pl.product_id
            WHERE pl.location_id = $locationId
            ORDER BY (pl.stock * p.cost) DESC")->fetchAll();
    } else {
        $rows = $pdo->query("
            SELECT p.name, p.brand, p.stock, p.unit, p.cost, p.sell,
                   ROUND(p.stock * p.cost, 2) AS cost_value,
                   ROUND(p.stock * p.sell, 2) AS sell_value
            FROM products p
            ORDER BY (p.stock * p.cost) DESC")->fetchAll();
    }
    jsonList($rows);
}

// ── Vendor summary ───────────────────────────────────────
if ($report === 'vendor_summary') {
    $rows = $pdo->query("
        SELECT v.id AS vendor_id, v.name AS vendor,
               COUNT(si.id)                        AS purchases,
               SUM(si.qty)                         AS total_qty,
               ROUND(SUM(si.cost * si.qty), 2)     AS total_amount,
               MAX(si.date)                        AS last_purchase
        FROM stock_in si
        JOIN vendors v ON v.id = si.vendor_id
        WHERE 1=1 $locJoinSI
        GROUP BY si.vendor_id, v.name
        ORDER BY total_amount DESC")->fetchAll();
    jsonList($rows);
}

// ── Top by margin ────────────────────────────────────────
if ($report === 'top_margin') {
    if ($locationId) {
        $rows = $pdo->query("
            SELECT p.name, p.brand, p.cost, p.sell, pl.stock, p.unit,
                   ROUND(CASE WHEN p.sell > 0 THEN ((p.sell-p.cost)/p.sell)*100 ELSE 0 END, 1) AS margin,
                   ROUND(pl.stock * p.cost, 2) AS stock_value,
                   v.name AS vendor_name
            FROM product_locations pl
            JOIN products p ON p.id = pl.product_id
            LEFT JOIN vendors v ON v.id = p.vendor_id
            WHERE pl.location_id = $locationId
            ORDER BY margin DESC LIMIT 10")->fetchAll();
    } else {
        $rows = $pdo->query("
            SELECT p.name, p.brand, p.cost, p.sell, p.stock, p.unit,
                   ROUND(CASE WHEN p.sell > 0 THEN ((p.sell-p.cost)/p.sell)*100 ELSE 0 END, 1) AS margin,
                   ROUND(p.stock * p.cost, 2) AS stock_value,
                   v.name AS vendor_name
            FROM products p LEFT JOIN vendors v ON v.id = p.vendor_id
            ORDER BY margin DESC LIMIT 10")->fetchAll();
    }
    jsonList($rows);
}

// ── Location summary (new) ───────────────────────────────
if ($report === 'location_summary') {
    $rows = $pdo->query("
        SELECT l.id, l.name AS location, l.is_default,
               COUNT(DISTINCT pl.product_id)          AS product_count,
               COALESCE(SUM(pl.stock), 0)             AS total_units,
               ROUND(COALESCE(SUM(pl.stock * p.cost), 0), 2) AS stock_value,
               (SELECT COUNT(*) FROM product_locations pl2
                WHERE pl2.location_id = l.id AND pl2.stock <= pl2.min_stock AND pl2.min_stock > 0) AS low_stock_count
        FROM locations l
        LEFT JOIN product_locations pl ON pl.location_id = l.id
        LEFT JOIN products p ON p.id = pl.product_id
        GROUP BY l.id, l.name, l.is_default
        ORDER BY l.is_default DESC, l.name")->fetchAll();
    jsonList($rows);
}

// ── Dashboard summary (default) ──────────────────────────
if ($locationId) {
    $stats = $pdo->query("
        SELECT
            (SELECT COUNT(DISTINCT product_id) FROM product_locations WHERE location_id=$locationId) AS total_products,
            (SELECT COALESCE(SUM(pl.stock * p.cost), 0) FROM product_locations pl JOIN products p ON p.id=pl.product_id WHERE pl.location_id=$locationId) AS stock_value,
            (SELECT COALESCE(SUM(sell_price * qty), 0) FROM stock_out WHERE location_id=$locationId) AS total_revenue,
            (SELECT COALESCE(SUM(cost * qty), 0) FROM stock_out WHERE location_id=$locationId)       AS total_cogs,
            (SELECT COUNT(*) FROM product_locations WHERE location_id=$locationId AND stock <= min_stock AND min_stock > 0) AS low_stock_count,
            (SELECT COUNT(*) FROM vendors) AS total_vendors
    ")->fetch();
} else {
    $stats = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM products)                               AS total_products,
            (SELECT COALESCE(SUM(stock * cost), 0) FROM products)         AS stock_value,
            (SELECT COALESCE(SUM(sell_price * qty), 0) FROM stock_out)    AS total_revenue,
            (SELECT COALESCE(SUM(cost * qty), 0) FROM stock_out)          AS total_cogs,
            (SELECT COUNT(*) FROM products WHERE stock <= min_stock AND min_stock > 0) AS low_stock_count,
            (SELECT COUNT(*) FROM vendors)                                AS total_vendors
    ")->fetch();
}

$stats['total_profit']  = round($stats['total_revenue'] - $stats['total_cogs'], 2);
$stats['stock_value']   = round($stats['stock_value'], 2);
$stats['total_revenue'] = round($stats['total_revenue'], 2);
$stats['total_cogs']    = round($stats['total_cogs'], 2);

// Low-stock alerts
$alertSql = $locationId
    ? "SELECT p.id, p.name, p.category, pl.stock, pl.min_stock, p.unit, v.name AS vendor_name, l.name AS location_name
       FROM product_locations pl
       JOIN products p ON p.id = pl.product_id
       JOIN locations l ON l.id = pl.location_id
       LEFT JOIN vendors v ON v.id = p.vendor_id
       WHERE pl.location_id = $locationId AND pl.stock <= pl.min_stock AND pl.min_stock > 0
       ORDER BY pl.stock ASC LIMIT 5"
    : "SELECT p.id, p.name, p.category, p.stock, p.min_stock, p.unit, v.name AS vendor_name, NULL AS location_name
       FROM products p LEFT JOIN vendors v ON v.id = p.vendor_id
       WHERE p.stock <= p.min_stock AND p.min_stock > 0
       ORDER BY p.stock ASC LIMIT 5";
$alerts = $pdo->query($alertSql)->fetchAll();

// Recent transactions
$recentSql = $locationId
    ? "SELECT 'in' AS type, si.id, si.date, si.qty, p.name AS product_name, l.name AS location_name, si.created_at
       FROM stock_in si JOIN products p ON p.id=si.product_id LEFT JOIN locations l ON l.id=si.location_id
       WHERE si.location_id=$locationId
       UNION ALL
       SELECT 'out', so.id, so.date, so.qty, p.name, l.name, so.created_at
       FROM stock_out so JOIN products p ON p.id=so.product_id LEFT JOIN locations l ON l.id=so.location_id
       WHERE so.location_id=$locationId
       ORDER BY created_at DESC LIMIT 8"
    : "SELECT 'in' AS type, si.id, si.date, si.qty, p.name AS product_name, l.name AS location_name, si.created_at
       FROM stock_in si JOIN products p ON p.id=si.product_id LEFT JOIN locations l ON l.id=si.location_id
       UNION ALL
       SELECT 'out', so.id, so.date, so.qty, p.name, l.name, so.created_at
       FROM stock_out so JOIN products p ON p.id=so.product_id LEFT JOIN locations l ON l.id=so.location_id
       ORDER BY created_at DESC LIMIT 8";
$recent = $pdo->query($recentSql)->fetchAll();

jsonOk(['stats' => $stats, 'alerts' => $alerts, 'recent' => $recent]);
