<?php
/**
 * Procurement Dashboard API
 * ALL products × location stock columns + on-order from active POs
 * Used to decide what to reorder
 */
require __DIR__ . '/../includes/db.php';
startSession(); requireAuth();

$pdo      = getDB();
$category = $_GET['category'] ?? '';
$vendor   = $_GET['vendor']   ?? '';
$filter   = $_GET['filter']   ?? '';   // all | low | out | on_order | no_order
$search   = $_GET['search']   ?? '';

// ── Locations ─────────────────────────────────────────────
$locations = $pdo->query("SELECT id, name FROM locations ORDER BY is_default DESC, name")
                 ->fetchAll(PDO::FETCH_ASSOC);

// ── Build location stock subqueries ───────────────────────
$locCols = '';
foreach ($locations as $loc) {
    $lid = (int)$loc['id'];
    $locCols .= ", COALESCE((SELECT pl.stock FROM product_locations pl
                    WHERE pl.product_id=p.id AND pl.location_id={$lid}),0) AS loc_{$lid}";
}

// ── On Order subquery per product ─────────────────────────
$onOrderCol = "(
    SELECT COALESCE(SUM(poi.qty_ordered - COALESCE(poi.qty_received,0)),0)
    FROM purchase_order_items poi
    JOIN purchase_orders po ON po.id=poi.po_id
    WHERE poi.product_id=p.id
    AND po.status IN ('draft','sent','partial')
    AND poi.qty_ordered > COALESCE(poi.qty_received,0)
) AS on_order";

// ── Filters ───────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($category) { $where[] = 'p.category = ?'; $params[] = $category; }
if ($vendor)   { $where[] = 'v.name = ?';      $params[] = $vendor; }
if ($search)   { $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ? OR p.item_code LIKE ?)';
                 $s = '%'.$search.'%'; $params = array_merge($params, [$s,$s,$s,$s]); }

// Stock filter applied after main query (uses computed values)
$whereSQL = 'WHERE ' . implode(' AND ', $where);

// ── Main query ────────────────────────────────────────────
$sql = "
    SELECT
        p.id, p.sku, p.item_code, p.name, p.brand, p.category,
        p.case_content,
        p.unit, p.min_stock, p.stock AS total_stock,
        ROUND(p.cost,0) AS cost,
        ROUND(COALESCE(p.sell,0),0) AS sell,
        COALESCE(v.name,'') AS vendor_name,
        {$onOrderCol}
        {$locCols}
    FROM products p
    LEFT JOIN vendors v ON v.id=p.vendor_id
    {$whereSQL}
    ORDER BY p.item_code, p.brand, p.name
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Apply stock filter ────────────────────────────────────
if ($filter === 'low') {
    $rows = array_filter($rows, fn($r) => $r['total_stock'] > 0 && $r['total_stock'] <= $r['min_stock'] && $r['min_stock'] > 0);
} elseif ($filter === 'out') {
    $rows = array_filter($rows, fn($r) => $r['total_stock'] <= 0);
} elseif ($filter === 'on_order') {
    $rows = array_filter($rows, fn($r) => $r['on_order'] > 0);
} elseif ($filter === 'no_order') {
    $rows = array_filter($rows, fn($r) => $r['on_order'] <= 0 && $r['total_stock'] <= $r['min_stock']);
}
$rows = array_values($rows);

// ── PO breakdown per product ──────────────────────────────
// Fetch open POs for all products in result
$productIds = array_column($rows, 'id');
$poMap = [];
if ($productIds) {
    $in = implode(',', array_map('intval', $productIds));
    $poRows = $pdo->query("
        SELECT poi.product_id, po.po_number, po.status,
               COALESCE(v.name,'Unknown') AS vendor,
               po.location_id,
               l.name AS location_name,
               poi.qty_ordered,
               COALESCE(poi.qty_received,0) AS qty_received,
               (poi.qty_ordered - COALESCE(poi.qty_received,0)) AS pending_qty,
               ROUND(poi.cost,0) AS unit_cost
        FROM purchase_order_items poi
        JOIN purchase_orders po ON po.id=poi.po_id
        LEFT JOIN vendors v ON v.id=po.vendor_id
        LEFT JOIN locations l ON l.id=po.location_id
        WHERE poi.product_id IN ($in)
        AND po.status IN ('draft','sent','partial')
        AND poi.qty_ordered > COALESCE(poi.qty_received,0)
        ORDER BY po.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($poRows as $pr) {
        $poMap[$pr['product_id']][] = $pr;
    }
}

// Attach PO data to rows
foreach ($rows as &$r) {
    $r['pos'] = $poMap[$r['id']] ?? [];
}
unset($r);

// ── Filter dropdowns ──────────────────────────────────────
$categories = $pdo->query("SELECT DISTINCT category FROM products WHERE category!='' ORDER BY category")
                  ->fetchAll(PDO::FETCH_COLUMN);
$vendors    = $pdo->query("SELECT name FROM vendors ORDER BY name")
                  ->fetchAll(PDO::FETCH_COLUMN);

// ── Summary ───────────────────────────────────────────────
$totalProducts  = count($rows);
$totalOnOrder   = array_sum(array_column($rows, 'on_order'));
$outOfStock     = count(array_filter($rows, fn($r) => $r['total_stock'] <= 0));
$lowStock       = count(array_filter($rows, fn($r) => $r['total_stock'] > 0 && $r['total_stock'] <= $r['min_stock'] && $r['min_stock'] > 0));
$needsReorder   = count(array_filter($rows, fn($r) => $r['on_order'] <= 0 && $r['total_stock'] <= $r['min_stock']));

jsonOk([
    'rows'       => $rows,
    'locations'  => $locations,
    'categories' => $categories,
    'vendors'    => $vendors,
    'summary'    => [
        'total'        => $totalProducts,
        'on_order'     => $totalOnOrder,
        'out_of_stock' => $outOfStock,
        'low_stock'    => $lowStock,
        'needs_reorder'=> $needsReorder,
    ],
]);
