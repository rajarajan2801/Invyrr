<?php
/**
 * On Order Report API
 * Returns pivot data: Item Code rows × Location columns + On Order
 * with vendor/PO breakdown per row
 */
require __DIR__ . '/../includes/db.php';
startSession(); requireAuth();

$pdo      = getDB();
$category = $_GET['category'] ?? '';
$vendor   = $_GET['vendor']   ?? '';
$status   = $_GET['status']   ?? '';
$group    = $_GET['group']    ?? 'item_code'; // item_code | category | vendor

// ── Locations ─────────────────────────────────────────────
$locations = $pdo->query("SELECT id, name FROM locations ORDER BY is_default DESC, name")
                 ->fetchAll(PDO::FETCH_ASSOC);

// ── Filters ───────────────────────────────────────────────
$where  = ["po.status IN ('draft','sent','partial')"];
$params = [];

if ($category) { $where[] = "p.category = ?"; $params[] = $category; }
if ($vendor)   { $where[] = "v.name = ?";      $params[] = $vendor; }
if ($status)   { $where[] = "po.status = ?";   $params[] = $status; }

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// ── Main query ────────────────────────────────────────────
$sql = "
    SELECT
        p.id            AS product_id,
        p.item_code,
        p.name          AS product_name,
        p.brand,
        p.category,
        p.unit,
        p.stock         AS total_stock,
        v.name          AS po_vendor,
        po.po_number,
        po.status       AS po_status,
        po.location_id  AS po_location_id,
        poi.qty_ordered,
        COALESCE(poi.qty_received, 0) AS qty_received,
        (poi.qty_ordered - COALESCE(poi.qty_received, 0)) AS pending_qty,
        ROUND(poi.cost, 0) AS unit_cost,
        ROUND(poi.cost * (poi.qty_ordered - COALESCE(poi.qty_received, 0)), 0) AS pending_value
    FROM purchase_order_items poi
    JOIN purchase_orders po ON po.id = poi.po_id
    JOIN products p ON p.id = poi.product_id
    LEFT JOIN vendors v ON v.id = po.vendor_id
    $whereSQL
    AND poi.qty_ordered > COALESCE(poi.qty_received, 0)
    ORDER BY p.item_code, p.brand, p.name, po.created_at
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Categories & Vendors for filters ──────────────────────
$categories = $pdo->query("SELECT DISTINCT p.category FROM products p
    JOIN purchase_order_items poi ON poi.product_id=p.id
    JOIN purchase_orders po ON po.id=poi.po_id
    WHERE po.status IN ('draft','sent','partial') AND poi.qty_ordered>COALESCE(poi.qty_received,0)
    ORDER BY p.category")->fetchAll(PDO::FETCH_COLUMN);

$vendors = $pdo->query("SELECT DISTINCT v.name FROM vendors v
    JOIN purchase_orders po ON po.vendor_id=v.id
    WHERE po.status IN ('draft','sent','partial')
    ORDER BY v.name")->fetchAll(PDO::FETCH_COLUMN);

// ── Build pivot rows ──────────────────────────────────────
// Group by: item_code (Item Code+Brand) | category | vendor
$pivot = [];

foreach ($rows as $r) {
    // Determine group key
    if ($group === 'category') {
        $groupKey   = $r['category'];
        $groupLabel = $r['category'];
        $subKey     = $r['item_code'] . '||' . $r['brand'];
        $subLabel   = $r['item_code'] . ' – ' . $r['product_name'];
    } elseif ($group === 'vendor') {
        $groupKey   = $r['po_vendor'] ?? 'Unknown';
        $groupLabel = $r['po_vendor'] ?? 'Unknown';
        $subKey     = $r['item_code'] . '||' . $r['brand'];
        $subLabel   = $r['item_code'] . ' – ' . $r['product_name'];
    } else {
        // item_code (default — matches the screenshot)
        $groupKey   = $r['item_code'];
        $groupLabel = $r['item_code'];
        $subKey     = $r['brand'];
        $subLabel   = $r['brand'] . ($r['product_name'] !== $r['brand'] ? ' – ' . $r['product_name'] : '');
    }

    // Init group
    if (!isset($pivot[$groupKey])) {
        $pivot[$groupKey] = [
            'label'       => $groupLabel,
            'total_order' => 0,
            'total_value' => 0,
            'loc_totals'  => [],
            'subs'        => [],
        ];
    }

    // Init sub-row
    if (!isset($pivot[$groupKey]['subs'][$subKey])) {
        $pivot[$groupKey]['subs'][$subKey] = [
            'label'       => $subLabel,
            'brand'       => $r['brand'],
            'category'    => $r['category'],
            'unit'        => $r['unit'],
            'stock'       => $r['total_stock'],
            'total_order' => 0,
            'total_value' => 0,
            'loc_qty'     => [],
            'vendors'     => [],  // vendor breakdown
            'pos'         => [],  // PO list
        ];
    }

    $sub = &$pivot[$groupKey]['subs'][$subKey];
    $locId = $r['po_location_id'] ?? 0;
    $locKey = 'loc_' . $locId;

    // Accumulate
    $sub['total_order']            += $r['pending_qty'];
    $sub['total_value']            += $r['pending_value'];
    $sub['loc_qty'][$locKey]        = ($sub['loc_qty'][$locKey] ?? 0) + $r['pending_qty'];

    // Vendor breakdown
    $vk = $r['po_vendor'] ?? 'Unknown';
    if (!isset($sub['vendors'][$vk])) $sub['vendors'][$vk] = 0;
    $sub['vendors'][$vk] += $r['pending_qty'];

    // PO list
    $sub['pos'][] = [
        'po_number'  => $r['po_number'],
        'status'     => $r['po_status'],
        'vendor'     => $r['po_vendor'],
        'pending'    => $r['pending_qty'],
        'ordered'    => $r['qty_ordered'],
        'received'   => $r['qty_received'],
        'unit_cost'  => $r['unit_cost'],
        'value'      => $r['pending_value'],
    ];

    // Group totals
    $pivot[$groupKey]['total_order'] += $r['pending_qty'];
    $pivot[$groupKey]['total_value'] += $r['pending_value'];
    $pivot[$groupKey]['loc_totals'][$locKey] = ($pivot[$groupKey]['loc_totals'][$locKey] ?? 0) + $r['pending_qty'];

    unset($sub);
}

// Summary stats
$totalPending = array_sum(array_column($pivot, 'total_order'));
$totalValue   = array_sum(array_column($pivot, 'total_value'));
$totalPOs     = count(array_unique(array_column($rows, 'po_number')));
$totalSKUs    = count(array_unique(array_column($rows, 'product_id')));

jsonOk([
    'pivot'      => $pivot,
    'locations'  => $locations,
    'categories' => $categories,
    'vendors'    => $vendors,
    'summary'    => [
        'total_pending' => $totalPending,
        'total_value'   => $totalValue,
        'total_pos'     => $totalPOs,
        'total_skus'    => $totalSKUs,
    ],
]);
