<?php
/**
 * Invyrr — CSV Export
 * GET /api/export.php?sheet=all|products|vendors|stock_in|stock_out|pnl|purchase_orders
 *
 * sheet=all  → ZIP containing one CSV per sheet
 * otherwise  → single CSV download
 */
require __DIR__ . '/../includes/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }
startSession(); requireAuth();

// Safe query — returns empty array on error or missing table
function safeQuery(PDO $pdo, string $sql, int $mode = PDO::FETCH_NUM): array {
    try {
        $stmt = $pdo->query($sql);
        return $stmt ? $stmt->fetchAll($mode) : [];
    } catch (PDOException $e) {
        return [];
    }
}

$pdo      = getDB();
$sheet    = $_GET['sheet'] ?? 'all';
$u        = currentUser();
$hideCost = ($u && $u['role'] === 'manager');
$date     = date('Y-m-d');

$allLocs = safeQuery($pdo, "SELECT id, name FROM locations ORDER BY is_default DESC, name", PDO::FETCH_ASSOC);

// ── Data builders ─────────────────────────────────────────────────────────────

function buildProductData(PDO $pdo, array $allLocs): array {
    // Ensure optional columns exist
    try { $pdo->exec("ALTER TABLE products ADD COLUMN combo TINYINT(1) DEFAULT 0"); } catch(PDOException $e){}
    try { $pdo->exec("ALTER TABLE products ADD COLUMN procurement_active TINYINT(1) DEFAULT 1"); } catch(PDOException $e){}
    try { $pdo->exec("ALTER TABLE products ADD COLUMN publish_web TINYINT(1) DEFAULT 0"); } catch(PDOException $e){}
    $locCols = '';
    foreach ($allLocs as $l) {
        $id = (int)$l['id'];
        $locCols .= ", COALESCE((SELECT pl.stock FROM product_locations pl WHERE pl.product_id=p.id AND pl.location_id=$id),0) AS loc_$id";
    }
    try {
        $stmt = $pdo->query("
            SELECT p.sku, p.item_code, p.name, p.brand, p.category, v.name AS vendor,
                   COALESCE(p.list_price,0) AS list_price,
                   COALESCE(p.cost,0) AS cost,
                   COALESCE(p.landing_cost,0) AS landing_cost,
                   COALESCE(p.sell,0) AS sell,
                   COALESCE(p.wholesale_price,0) AS wholesale_price,
                   COALESCE(p.case_content,'') AS case_content,
                   COALESCE(p.box_content,'') AS box_content,
                   COALESCE(p.min_stock,0) AS min_stock,
                   COALESCE(p.unit,'') AS unit,
                   COALESCE(p.description,'') AS description,
                   ROUND(CASE WHEN COALESCE(p.sell,0)>0 THEN ((COALESCE(p.sell,0)-COALESCE(p.cost,0))/COALESCE(p.sell,0))*100 ELSE 0 END,1) AS margin_pct,
                   IF(COALESCE(p.combo,0)=1,'Yes','No') AS combo,
                   IF(COALESCE(p.procurement_active,1)=1,'Yes','No') AS procurement_active,
                   IF(COALESCE(p.publish_web,0)=1,'Yes','No') AS publish_web,
                   COALESCE(p.stock,0) AS total_stock,
                   ROUND(COALESCE(p.stock,0)*COALESCE(p.cost,0),0) AS stock_value,
                   COALESCE((
                       SELECT SUM(poi.qty_ordered - COALESCE(poi.qty_received,0))
                       FROM purchase_order_items poi
                       JOIN purchase_orders po ON po.id=poi.po_id
                       WHERE poi.product_id=p.id
                       AND po.status IN ('draft','sent','partial')
                       AND poi.qty_ordered > COALESCE(poi.qty_received,0)
                   ),0) AS on_order
                   $locCols
            FROM products p LEFT JOIN vendors v ON v.id=p.vendor_id
            ORDER BY p.brand, p.name");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (PDOException $e) {
        $rows = [];
    }

    global $hideCost;

    // Common fields (same order as import template)
    $header = ['SKU','Item Code','Product Name','Brand','Category','Vendor Name'];
    if (!$hideCost) { $header[] = 'List Price'; $header[] = 'Cost Price'; $header[] = 'Landing Cost'; }
    $header = array_merge($header, ['Sell Price','Wholesale Price','Case Content','Box Content']);
    foreach ($allLocs as $l) $header[] = trim(preg_replace('/\s*\(Primary\)\s*/i', '', $l['name']));
    $header = array_merge($header, ['Min Stock','Unit','Description','Combo (Yes/No)','Active (Yes/No)','Push to Web (Yes/No)']);

    // Export-only fields appended after
    if (!$hideCost) $header[] = 'Margin%';
    $header = array_merge($header, ['Total Stock','Stock Value','On Order']);

    $out = [];
    foreach ($rows as $r) {
        // Common fields
        $row = [$r['sku'], $r['item_code'], $r['name'], $r['brand'], $r['category'], $r['vendor']];
        if (!$hideCost) { $row[] = $r['list_price']; $row[] = $r['cost']; $row[] = $r['landing_cost']; }
        $row = array_merge($row, [
            $r['sell'], $r['wholesale_price'],
            $r['case_content'], $r['box_content'],
        ]);
        foreach ($allLocs as $l) $row[] = $r['loc_'.$l['id']] ?? 0;
        $row = array_merge($row, [$r['min_stock'], $r['unit'], $r['description'], $r['combo'], $r['procurement_active'], $r['publish_web']]);

        // Export-only fields
        if (!$hideCost) $row[] = $r['margin_pct'];
        $row = array_merge($row, [$r['total_stock'], $r['stock_value'], $r['on_order']]);

        $out[] = $row;
    }
    return ['header' => $header, 'rows' => $out];
}

function getVendors(PDO $pdo): array {
    $header = ['Vendor Name','Type','Contact','Phone','Email','City','GST'];
    $rows   = safeQuery($pdo, "SELECT name,type,contact,phone,email,city,gst FROM vendors ORDER BY name", PDO::FETCH_NUM);
    return ['header' => $header, 'rows' => $rows];
}

function getStockIn(PDO $pdo): array {
    $header = ['Date','Product','Location','Vendor','Qty','Cost','Total','Note'];
    $rows   = safeQuery($pdo, "SELECT si.date,p.name,l.name,v.name,si.qty,si.cost,ROUND(si.qty*si.cost,0),si.note
        FROM stock_in si JOIN products p ON p.id=si.product_id
        LEFT JOIN locations l ON l.id=si.location_id LEFT JOIN vendors v ON v.id=si.vendor_id
        ORDER BY si.date DESC,si.id DESC", PDO::FETCH_NUM);
    return ['header' => $header, 'rows' => $rows];
}

function getStockOut(PDO $pdo): array {
    $header = ['Date','Product','Location','Customer','Qty','Sell Price','Cost','Profit','Note'];
    $rows   = safeQuery($pdo, "SELECT so.date,p.name,l.name,so.customer,so.qty,so.sell_price,so.cost,
               ROUND((so.sell_price-so.cost)*so.qty,0),so.note
        FROM stock_out so JOIN products p ON p.id=so.product_id
        LEFT JOIN locations l ON l.id=so.location_id
        ORDER BY so.date DESC,so.id DESC", PDO::FETCH_NUM);
    return ['header' => $header, 'rows' => $rows];
}

function getPnL(PDO $pdo): array {
    $header = ['Product','Sold Qty','Revenue','COGS','Profit','Margin%'];
    $rows   = safeQuery($pdo, "SELECT p.name,SUM(so.qty),ROUND(SUM(so.sell_price*so.qty),0),
               ROUND(SUM(so.cost*so.qty),0),ROUND(SUM((so.sell_price-so.cost)*so.qty),0),
               ROUND(CASE WHEN SUM(so.sell_price*so.qty)>0
                     THEN SUM((so.sell_price-so.cost)*so.qty)/SUM(so.sell_price*so.qty)*100
                     ELSE 0 END,1)
        FROM stock_out so JOIN products p ON p.id=so.product_id
        GROUP BY so.product_id,p.name ORDER BY 5 DESC", PDO::FETCH_NUM);
    return ['header' => $header, 'rows' => $rows];
}

function getPOSummary(PDO $pdo): array {
    global $hideCost;
    $header = $hideCost
        ? ['PO #','Vendor','Location','Status','Expected Date','Items','Notes','Created At']
        : ['PO #','Vendor','Location','Status','Expected Date','Items','Total','Notes','Created At'];
    $rows = safeQuery($pdo, "
        SELECT po.po_number, v.name, l.name, po.status, po.expected_date,
               (SELECT COUNT(*) FROM purchase_order_items poi WHERE poi.po_id=po.id) AS item_count,
               ROUND(COALESCE((SELECT SUM(poi.qty_ordered*poi.cost) FROM purchase_order_items poi WHERE poi.po_id=po.id),0)+COALESCE(po.misc_charges,0),0) AS total,
               po.notes, po.created_at
        FROM purchase_orders po
        LEFT JOIN vendors v ON v.id=po.vendor_id
        LEFT JOIN locations l ON l.id=po.location_id
        ORDER BY po.created_at DESC", PDO::FETCH_NUM);
    if ($hideCost) {
        // Remove total column (index 6) from each row
        $rows = array_map(function($r){ return array_values(array_diff_key($r, [6=>1])); }, $rows);
    }
    return ['header' => $header, 'rows' => $rows];
}

function getPOLineItems(PDO $pdo): array {
    global $hideCost;
    $costCols = $hideCost ? [] : ['Cost','Line Total'];
    $header   = array_merge(
        ['SKU','Product','Brand','Ordered Qty(Case)','Ordered Qty','Unit'],
        $costCols,
        ['Received','Pending','PO #','Vendor','Location','Status','Expected Date']
    );
    $data = safeQuery($pdo, "
        SELECT p.sku, p.name AS product, p.brand, p.unit, p.case_content,
               poi.qty_ordered, poi.cost,
               ROUND(poi.qty_ordered * poi.cost, 2) AS line_total,
               COALESCE(poi.qty_received,0) AS qty_received,
               (poi.qty_ordered - COALESCE(poi.qty_received,0)) AS pending,
               po.po_number, v.name AS vendor, l.name AS location,
               po.status, po.expected_date
        FROM purchase_order_items poi
        JOIN purchase_orders po ON po.id = poi.po_id
        JOIN products p ON p.id = poi.product_id
        LEFT JOIN vendors v ON v.id = po.vendor_id
        LEFT JOIN locations l ON l.id = po.location_id
        ORDER BY po.created_at DESC, poi.id", PDO::FETCH_ASSOC);
    $out = [];
    foreach ($data as $r) {
        $caseContent = !empty($r['case_content']) ? (float)$r['case_content'] : null;
        $qtyOrdered  = (int)$r['qty_ordered'];
        $qtyCase     = $caseContent ? round($qtyOrdered / $caseContent, 2) : '';
        $row = [$r['sku'], $r['product'], $r['brand']??'', $qtyCase, $qtyOrdered, $r['unit']??''];
        if (!$hideCost) { $row[] = $r['cost']; $row[] = $r['line_total']; }
        $row[] = $r['qty_received'];
        $row[] = $r['pending'];
        $row[] = $r['po_number'];
        $row[] = $r['vendor']??'';
        $row[] = $r['location']??'';
        $row[] = $r['status'];
        $row[] = $r['expected_date']??'';
        $out[] = $row;
    }
    return ['header' => $header, 'rows' => $out];
}

function getExpenses(PDO $pdo, int $entityId = 0): array {
    $header = ['Date','Category','Amount','Vendor','Paid Via','Payee Type','Bank Name','Account No','UPI ID','Paid To','Paid To Type','Business','Reference No','Notes'];
    $where  = $entityId ? "WHERE e.entity_id = $entityId" : "WHERE e.entity_id IS NULL";
    $rows   = safeQuery($pdo, "SELECT CONCAT(DATE_FORMAT(e.expense_date,'%d-'),UPPER(DATE_FORMAT(e.expense_date,'%b')),DATE_FORMAT(e.expense_date,'-%y')), e.category, ROUND(e.amount,0),
               COALESCE(v.name,''), COALESCE(py.name,''), COALESCE(py.type,''),
               COALESCE(py.bank_name,''), COALESCE(py.account_no,''), COALESCE(py.upi_id,''),
               COALESCE(pt.name,''), COALESCE(pt.type,''),
               COALESCE(ee.name,'RR Expenses'),
               COALESCE(e.reference_no,''), COALESCE(e.notes,'')
        FROM expenses e
        LEFT JOIN vendors v           ON v.id  = e.vendor_id
        LEFT JOIN payees py           ON py.id = e.payee_id
        LEFT JOIN payees pt           ON pt.id = e.paid_to_id
        LEFT JOIN expense_entities ee ON ee.id = e.entity_id
        $where
        ORDER BY e.expense_date DESC, e.id DESC", PDO::FETCH_NUM);
    return ['header' => $header, 'rows' => $rows];
}
function getAllExpenses(PDO $pdo): array {
    $header = ['Date','Category','Amount','Vendor','Paid Via','Payee Type','Bank Name','Account No','UPI ID','Paid To','Paid To Type','Business','Reference No','Notes'];
    $rows   = safeQuery($pdo, "SELECT CONCAT(DATE_FORMAT(e.expense_date,'%d-'),UPPER(DATE_FORMAT(e.expense_date,'%b')),DATE_FORMAT(e.expense_date,'-%y')), e.category, ROUND(e.amount,0),
               COALESCE(v.name,''), COALESCE(py.name,''), COALESCE(py.type,''),
               COALESCE(py.bank_name,''), COALESCE(py.account_no,''), COALESCE(py.upi_id,''),
               COALESCE(pt.name,''), COALESCE(pt.type,''),
               COALESCE(ee.name,'RR Expenses'),
               COALESCE(e.reference_no,''), COALESCE(e.notes,'')
        FROM expenses e
        LEFT JOIN vendors v           ON v.id  = e.vendor_id
        LEFT JOIN payees py           ON py.id = e.payee_id
        LEFT JOIN payees pt           ON pt.id = e.paid_to_id
        LEFT JOIN expense_entities ee ON ee.id = e.entity_id
        ORDER BY ee.name ASC, e.expense_date DESC, e.id DESC", PDO::FETCH_NUM);
    return ['header' => $header, 'rows' => $rows];
}
function getPayees(PDO $pdo): array {
    $header = ['Name','Type','Bank Name','Account No','IFSC','UPI ID','Phone','Notes','Status'];
    $rows   = safeQuery($pdo, "SELECT name,COALESCE(type,''),COALESCE(bank_name,''),
               COALESCE(account_no,''),COALESCE(ifsc,''),COALESCE(upi_id,''),
               COALESCE(phone,''),COALESCE(notes,''),IF(is_active=1,'Active','Inactive')
        FROM payees ORDER BY name", PDO::FETCH_NUM);
    return ['header' => $header, 'rows' => $rows];
}
function getVendorPayments(PDO $pdo): array {
    $header = ['Date','Vendor','Type','Description','Payee','Reference No','Amount'];
    $rows   = safeQuery($pdo, "SELECT vp.payment_date, COALESCE(v.name,''), vp.type,
               COALESCE(vp.notes,''),
               COALESCE(py.name,''), COALESCE(vp.reference_no,''), ROUND(vp.amount,0)
        FROM vendor_payments vp
        LEFT JOIN vendors v ON v.id=vp.vendor_id
        LEFT JOIN payees py ON py.id=vp.payee_id
        ORDER BY vp.payment_date DESC, vp.id DESC", PDO::FETCH_NUM);
    return ['header' => $header, 'rows' => $rows];
}
function getTransfers(PDO $pdo): array {
    $header = ['Date','Product','SKU','From Location','To Location','Qty','Note'];
    $rows   = safeQuery($pdo, "SELECT t.date, p.name, COALESCE(p.sku,''),
               COALESCE(lf.name,''), COALESCE(lt.name,''), t.qty, COALESCE(t.note,'')
        FROM stock_transfers t
        JOIN products p ON p.id=t.product_id
        LEFT JOIN locations lf ON lf.id=t.from_location_id
        LEFT JOIN locations lt ON lt.id=t.to_location_id
        ORDER BY t.date DESC, t.id DESC", PDO::FETCH_NUM);
    return ['header' => $header, 'rows' => $rows];
}
function getAdjustments(PDO $pdo): array {
    $header = ['Date','Product','SKU','Location','Qty Change','Reason','Note'];
    $rows   = safeQuery($pdo, "SELECT a.date, p.name, COALESCE(p.sku,''),
               COALESCE(l.name,''), a.qty_change, a.reason, COALESCE(a.note,'')
        FROM stock_adjustments a
        JOIN products p ON p.id=a.product_id
        LEFT JOIN locations l ON l.id=a.location_id
        ORDER BY a.date DESC, a.id DESC", PDO::FETCH_NUM);
    return ['header' => $header, 'rows' => $rows];
}
function getInvoices(PDO $pdo): array {
    $header = ['Invoice #','Date','Customer','Location','Items','Subtotal','Discount',
               'Packing','Misc','Total','Received','Balance','Payment Method','Status','Notes'];
    $rows   = safeQuery($pdo, "SELECT i.invoice_number, i.date, COALESCE(i.customer_name,'Walk-in'),
               COALESCE(l.name,''),
               (SELECT COUNT(*) FROM stock_out so WHERE so.invoice_id=i.id),
               ROUND(i.subtotal,0), ROUND(COALESCE(i.discount,0),0),
               ROUND(COALESCE(i.packing_charges,0),0), ROUND(COALESCE(i.misc_charges,0),0),
               ROUND(i.total,0), ROUND(COALESCE(i.amount_received,0),0),
               ROUND(i.total-COALESCE(i.amount_received,0),0),
               COALESCE(i.payment_method,''), i.status, COALESCE(i.notes,'')
        FROM invoices i
        LEFT JOIN locations l ON l.id=i.location_id
        ORDER BY i.date DESC, i.id DESC", PDO::FETCH_NUM);
    return ['header' => $header, 'rows' => $rows];
}
function getCategories(PDO $pdo): array {
    $header = ['Name','SKU Prefix','Description','Color','Product Count'];
    $rows   = safeQuery($pdo, "SELECT c.name, COALESCE(c.sku_prefix,''), COALESCE(c.description,''),
               COALESCE(c.color,''),
               (SELECT COUNT(*) FROM products p WHERE p.category=c.name)
        FROM categories c ORDER BY c.name", PDO::FETCH_NUM);
    return ['header' => $header, 'rows' => $rows];
}
function getLocations(PDO $pdo): array {
    $header = ['Name','Address','Phone','Is Default'];
    $rows   = safeQuery($pdo, "SELECT name, COALESCE(address,''), COALESCE(phone,''),
               IF(is_default=1,'Yes','No') FROM locations ORDER BY is_default DESC, name", PDO::FETCH_NUM);
    return ['header' => $header, 'rows' => $rows];
}

// ── CSV builder ───────────────────────────────────────────────────────────────

function toCsv(array $header, $rows): string {
    if (!is_array($rows)) $rows = [];
    $out = fopen('php://temp', 'r+');
    fputcsv($out, $header);
    foreach ($rows as $row) fputcsv($out, array_values((array)$row));
    rewind($out);
    $csv = stream_get_contents($out);
    fclose($out);
    return $csv;
}

// ── Sheet map ─────────────────────────────────────────────────────────────────

$prodData = buildProductData($pdo, $allLocs);

$allSheets = [
    'products'        => ['label' => 'Products',        'data' => $prodData],
    'categories'      => ['label' => 'Categories',      'data' => getCategories($pdo)],
    'vendors'         => ['label' => 'Vendors',         'data' => getVendors($pdo)],
    'locations'       => ['label' => 'Locations',       'data' => getLocations($pdo)],
    'stock_in'        => ['label' => 'Stock_In',        'data' => getStockIn($pdo)],
    'stock_out'       => ['label' => 'Stock_Out',       'data' => getStockOut($pdo)],
    'invoices'        => ['label' => 'Invoices',        'data' => getInvoices($pdo)],
    'transfers'       => ['label' => 'Transfers',       'data' => getTransfers($pdo)],
    'adjustments'     => ['label' => 'Adjustments',     'data' => getAdjustments($pdo)],
    'pnl'             => ['label' => 'PnL',             'data' => getPnL($pdo)],
    'expenses'        => ['label' => 'RR_Expenses',     'data' => getExpenses($pdo, 0)],
    'expenses_all'    => ['label' => 'All_Expenses',    'data' => getAllExpenses($pdo)],
];

// Dynamically add a sheet per business entity
try {
    $entities = $pdo->query("SELECT id, name FROM expense_entities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($entities as $ent) {
        $safeKey   = 'expenses_'.preg_replace('/[^a-z0-9]/i','_',strtolower($ent['name']));
        $safeLabel = 'Expenses_'.preg_replace('/[^a-z0-9]/i','_',$ent['name']);
        $allSheets[$safeKey] = ['label' => $safeLabel, 'data' => getExpenses($pdo, (int)$ent['id'])];
    }
} catch (Exception $e) {
    // expense_entities table may not exist yet
}

$allSheets = array_merge($allSheets, [
    'payees'          => ['label' => 'Payees',          'data' => getPayees($pdo)],
    'vendor_payments' => ['label' => 'Vendor_Payments', 'data' => getVendorPayments($pdo)],
    'po_summary'      => ['label' => 'PO_Summary',      'data' => getPOSummary($pdo)],
    'po_line_items'   => ['label' => 'PO_Line_Items',   'data' => getPOLineItems($pdo)],
]);

// ── Dispatch ──────────────────────────────────────────────────────────────────

if ($sheet === 'all' || $sheet === 'purchase_orders') {
    // ZIP containing one CSV per requested sheet
    $wantedKeys = $sheet === 'purchase_orders'
        ? ['po_summary','po_line_items']
        : array_keys($allSheets);

    $tmpZip = tempnam(sys_get_temp_dir(), 'sm_export_') . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($tmpZip, ZipArchive::CREATE) !== true) {
        http_response_code(500); echo 'Could not create zip'; exit;
    }
    foreach ($wantedKeys as $key) {
        if (!isset($allSheets[$key])) continue;
        $d   = $allSheets[$key];
        $csv = toCsv($d['data']['header'], $d['data']['rows']);
        $zip->addFromString("Invyrr_{$d['label']}_{$date}.csv", $csv);
    }
    $zip->close();

    $zipName = $sheet === 'purchase_orders'
        ? "Invyrr_PurchaseOrders_{$date}.zip"
        : "Invyrr_Export_{$date}.zip";

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.$zipName.'"');
    header('Content-Length: '.filesize($tmpZip));
    header('Cache-Control: max-age=0');
    readfile($tmpZip);
    unlink($tmpZip);
    exit;
}

// Single sheet → single CSV
$validSingle = ['products','vendors','stock_in','stock_out','pnl'];
$key = in_array($sheet, $validSingle) ? $sheet : null;

if (!$key || !isset($allSheets[$key])) {
    http_response_code(400); echo 'Unknown sheet'; exit;
}

$d   = $allSheets[$key];
$csv = toCsv($d['data']['header'], $d['data']['rows']);
$filename = "Invyrr_{$d['label']}_{$date}.csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Length: '.strlen($csv));
header('Cache-Control: max-age=0');
echo $csv;