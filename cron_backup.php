<?php
// ── Invyrr Automated Full Backup → Google Drive ───────────
// Backs up SQL dump + all CSV exports as a single ZIP.
// Triggered by cron-job.org every 3 hours.
//
// Required Railway env vars:
//   GOOGLE_CLIENT_ID       → OAuth Client ID
//   GOOGLE_CLIENT_SECRET   → OAuth Client Secret
//   GOOGLE_REFRESH_TOKEN   → Refresh token from OAuth Playground
//   GOOGLE_DRIVE_FOLDER_ID → Folder ID of Invyrr_db_backup in Drive
//   BACKUP_SECRET          → Secret to protect the URL endpoint
//
// cron-job.org URL: https://invyrr.up.railway.app/cron_backup.php?secret=YOUR_SECRET
// Schedule: every 3 hours → set to: 0 */3 * * *

require_once __DIR__ . '/includes/db.php';

// ── Security ──────────────────────────────────────────────
$isCLI = (php_sapi_name() === 'cli');
if (!$isCLI) {
    $secret   = _env('BACKUP_SECRET', '');
    $provided = $_GET['secret'] ?? '';
    if ($secret === '' || $provided !== $secret) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Forbidden']));
    }
    header('Content-Type: text/plain');
}

$log = function(string $msg) {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    flush();
};

$log('Starting Invyrr full backup...');

$pdo    = getDB();
$date   = date('Y-m-d_H-i-s');
$dbname = _env('MYSQLDATABASE', _env('MYSQL_DATABASE', _env('DB_NAME', 'invyrr')));

// ── Step 1: SQL dump ──────────────────────────────────────
$log('Generating SQL dump...');
$sql  = "-- Invyrr SQL Backup\n";
$sql .= "-- Generated: " . date('Y-m-d H:i:s') . " UTC\n";
$sql .= "-- Database: {$dbname}\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        $sql   .= "DROP TABLE IF EXISTS `{$table}`;\n" . $create[1] . ";\n\n";
        $rows   = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_NUM);
        if ($rows) {
            $cols    = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
            $sql    .= "INSERT INTO `{$table}` (`" . implode('`, `', $cols) . "`) VALUES\n";
            $vals    = [];
            foreach ($rows as $row) {
                $escaped = array_map(fn($v) => $v === null ? 'NULL' : "'" . addslashes($v) . "'", $row);
                $vals[]  = '(' . implode(', ', $escaped) . ')';
            }
            $sql .= implode(",\n", $vals) . ";\n\n";
        }
    }
} catch (Exception $e) {
    $log('ERROR in SQL dump: ' . $e->getMessage()); exit(1);
}
$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
$log('SQL dump: ' . round(strlen($sql)/1024, 1) . ' KB');

// ── Step 2: CSV sheets ────────────────────────────────────
function safeExport(PDO $pdo, string $sql): array {
    try { $s=$pdo->query($sql); return $s?$s->fetchAll(PDO::FETCH_NUM):[]; }
    catch(Exception $e){ return []; }
}
function toCsvStr(array $header, array $rows): string {
    $tmp = fopen('php://temp','r+');
    fputcsv($tmp, $header);
    foreach ($rows as $row) fputcsv($tmp, array_values($row));
    rewind($tmp); $out=stream_get_contents($tmp); fclose($tmp);
    return $out;
}

$csvSheets = [
    'Products'        => [
        ['SKU','Item Code','Name','Brand','Category','Vendor','List Price','Cost','Landing Cost','Sell','Wholesale','Case Content','Box Content','Unit','Min Stock','Stock','On Order','Description'],
        safeExport($pdo, "SELECT p.sku,p.item_code,p.name,p.brand,p.category,COALESCE(v.name,''),
            ROUND(COALESCE(p.list_price,0),0),ROUND(p.cost,0),ROUND(COALESCE(p.landing_cost,0),0),
            ROUND(COALESCE(p.sell,0),0),ROUND(COALESCE(p.wholesale_price,0),0),
            COALESCE(p.case_content,''),COALESCE(p.box_content,''),p.unit,p.min_stock,p.stock,
            COALESCE((SELECT SUM(poi.qty_ordered-COALESCE(poi.qty_received,0))
                FROM purchase_order_items poi JOIN purchase_orders po ON po.id=poi.po_id
                WHERE poi.product_id=p.id AND po.status IN ('draft','sent','partial')
                AND poi.qty_ordered>COALESCE(poi.qty_received,0)),0),
            COALESCE(p.description,'')
            FROM products p LEFT JOIN vendors v ON v.id=p.vendor_id ORDER BY p.name"),
    ],
    'Categories'      => [
        ['Name','SKU Prefix','Description','Color'],
        safeExport($pdo, "SELECT name,COALESCE(sku_prefix,''),COALESCE(description,''),COALESCE(color,'') FROM categories ORDER BY name"),
    ],
    'Vendors'         => [
        ['Name','Type','Contact','Phone','Email','City','GST','Address'],
        safeExport($pdo, "SELECT name,COALESCE(type,''),COALESCE(contact,''),COALESCE(phone,''),COALESCE(email,''),COALESCE(city,''),COALESCE(gst,''),COALESCE(address,'') FROM vendors ORDER BY name"),
    ],
    'Locations'       => [
        ['Name','Address','Phone','Default'],
        safeExport($pdo, "SELECT name,COALESCE(address,''),COALESCE(phone,''),IF(is_default=1,'Yes','No') FROM locations ORDER BY name"),
    ],
    'Stock_In'        => [
        ['Date','Product','Location','Vendor','Qty','Cost','Total','Note'],
        safeExport($pdo, "SELECT si.date,p.name,COALESCE(l.name,''),COALESCE(v.name,''),si.qty,ROUND(si.cost,0),ROUND(si.qty*si.cost,0),COALESCE(si.note,'')
            FROM stock_in si JOIN products p ON p.id=si.product_id
            LEFT JOIN locations l ON l.id=si.location_id LEFT JOIN vendors v ON v.id=si.vendor_id
            ORDER BY si.date DESC,si.id DESC"),
    ],
    'Stock_Out'       => [
        ['Date','Product','Location','Customer','Qty','Sell Price','Cost','Profit','Note'],
        safeExport($pdo, "SELECT so.date,p.name,COALESCE(l.name,''),COALESCE(so.customer,''),so.qty,
            ROUND(so.sell_price,0),ROUND(so.cost,0),ROUND((so.sell_price-so.cost)*so.qty,0),COALESCE(so.note,'')
            FROM stock_out so JOIN products p ON p.id=so.product_id
            LEFT JOIN locations l ON l.id=so.location_id ORDER BY so.date DESC,so.id DESC"),
    ],
    'Invoices'        => [
        ['Invoice #','Date','Customer','Location','Total','Received','Balance','Payment','Status'],
        safeExport($pdo, "SELECT i.invoice_number,i.date,COALESCE(i.customer_name,'Walk-in'),COALESCE(l.name,''),
            ROUND(i.total,0),ROUND(COALESCE(i.amount_received,0),0),
            ROUND(i.total-COALESCE(i.amount_received,0),0),
            COALESCE(i.payment_method,''),i.status
            FROM invoices i LEFT JOIN locations l ON l.id=i.location_id ORDER BY i.date DESC,i.id DESC"),
    ],
    'Expenses'        => [
        ['Date','Category','Amount','Vendor','Paid Via','Payee Type','Bank Name','Account No','UPI ID','Paid To','Paid To Type','Business','Reference No','Notes'],
        safeExport($pdo, "SELECT e.expense_date,e.category,ROUND(e.amount,0),
            COALESCE(v.name,''),COALESCE(py.name,''),COALESCE(py.type,''),
            COALESCE(py.bank_name,''),COALESCE(py.account_no,''),COALESCE(py.upi_id,''),
            COALESCE(pt.name,''),COALESCE(pt.type,''),
            COALESCE(ee.name,''),
            COALESCE(e.reference_no,''),COALESCE(e.notes,'')
            FROM expenses e
            LEFT JOIN vendors v           ON v.id=e.vendor_id
            LEFT JOIN payees py           ON py.id=e.payee_id
            LEFT JOIN payees pt           ON pt.id=e.paid_to_id
            LEFT JOIN expense_entities ee ON ee.id=e.entity_id
            ORDER BY e.expense_date DESC,e.id DESC"),
    ],
    'Payees'          => [
        ['Name','Type','Bank','Account No','IFSC','UPI ID','Phone','Status'],
        safeExport($pdo, "SELECT name,COALESCE(type,''),COALESCE(bank_name,''),COALESCE(account_no,''),
            COALESCE(ifsc,''),COALESCE(upi_id,''),COALESCE(phone,''),IF(is_active=1,'Active','Inactive')
            FROM payees ORDER BY name"),
    ],
    'Vendor_Payments' => [
        ['Date','Vendor','Type','Description','Payee','Reference No','Amount'],
        safeExport($pdo, "SELECT vp.payment_date,COALESCE(v.name,''),vp.type,
            COALESCE(vp.notes,''),
            COALESCE(py.name,''),COALESCE(vp.reference_no,''),ROUND(vp.amount,0)
            FROM vendor_payments vp
            LEFT JOIN vendors v ON v.id=vp.vendor_id LEFT JOIN payees py ON py.id=vp.payee_id
            ORDER BY vp.payment_date DESC,vp.id DESC"),
    ],
    'Transfers'       => [
        ['Date','Product','SKU','From','To','Qty','Note'],
        safeExport($pdo, "SELECT t.date,p.name,COALESCE(p.sku,''),COALESCE(lf.name,''),COALESCE(lt.name,''),t.qty,COALESCE(t.note,'')
            FROM stock_transfers t JOIN products p ON p.id=t.product_id
            LEFT JOIN locations lf ON lf.id=t.from_location_id LEFT JOIN locations lt ON lt.id=t.to_location_id
            ORDER BY t.date DESC,t.id DESC"),
    ],
    'Adjustments'     => [
        ['Date','Product','SKU','Location','Qty Change','Reason','Note'],
        safeExport($pdo, "SELECT a.date,p.name,COALESCE(p.sku,''),COALESCE(l.name,''),a.qty_change,a.reason,COALESCE(a.note,'')
            FROM stock_adjustments a JOIN products p ON p.id=a.product_id
            LEFT JOIN locations l ON l.id=a.location_id ORDER BY a.date DESC,a.id DESC"),
    ],
    'PnL'             => [
        ['Product','Sold Qty','Revenue','COGS','Profit','Margin%'],
        safeExport($pdo, "SELECT p.name,SUM(so.qty),ROUND(SUM(so.sell_price*so.qty),0),
            ROUND(SUM(so.cost*so.qty),0),ROUND(SUM((so.sell_price-so.cost)*so.qty),0),
            ROUND(CASE WHEN SUM(so.sell_price*so.qty)>0 THEN SUM((so.sell_price-so.cost)*so.qty)/SUM(so.sell_price*so.qty)*100 ELSE 0 END,1)
            FROM stock_out so JOIN products p ON p.id=so.product_id GROUP BY so.product_id,p.name ORDER BY 5 DESC"),
    ],
    'PO_Summary'      => [
        ['PO #','Vendor','Location','Status','Expected Date','Total','Notes'],
        safeExport($pdo, "SELECT po.po_number,COALESCE(v.name,''),COALESCE(l.name,''),po.status,
            COALESCE(po.expected_date,''),ROUND(COALESCE(po.total,0),0),COALESCE(po.notes,'')
            FROM purchase_orders po LEFT JOIN vendors v ON v.id=po.vendor_id LEFT JOIN locations l ON l.id=po.location_id
            ORDER BY po.created_at DESC"),
    ],
    'PO_Line_Items'   => [
        ['PO #','SKU','Product','Brand','Ordered Qty','Cost','Line Total','Received','Pending','Status'],
        safeExport($pdo, "SELECT po.po_number,COALESCE(p.sku,''),p.name,COALESCE(p.brand,''),
            poi.qty_ordered,ROUND(poi.cost,0),ROUND(poi.qty_ordered*poi.cost,0),
            COALESCE(poi.qty_received,0),(poi.qty_ordered-COALESCE(poi.qty_received,0)),po.status
            FROM purchase_order_items poi JOIN purchase_orders po ON po.id=poi.po_id JOIN products p ON p.id=poi.product_id
            ORDER BY po.created_at DESC,poi.id"),
    ],
];

// ── Step 3: Build ZIP ─────────────────────────────────────
$log('Building ZIP (' . count($csvSheets) . ' CSV sheets + SQL)...');
$tmpZip = tempnam(sys_get_temp_dir(), 'invyrr_backup_') . '.zip';
$zip    = new ZipArchive();
if ($zip->open($tmpZip, ZipArchive::CREATE) !== true) {
    $log('ERROR: Could not create ZIP'); exit(1);
}

$zip->addFromString("Invyrr_DB_{$date}.sql", $sql);
$log("  Added SQL dump: " . round(strlen($sql)/1024,1) . " KB");
foreach ($csvSheets as $name => [$header, $rows]) {
    $zip->addFromString("Invyrr_{$name}_{$date}.csv", toCsvStr($header, $rows));
    $log("  Added {$name}: " . count($rows) . " rows");
}
$zip->close();

$zipSize = round(filesize($tmpZip)/1024, 1);
$log("ZIP built: {$zipSize} KB");

// ── Step 4: Google auth ───────────────────────────────────
$clientId     = _env('GOOGLE_CLIENT_ID', '');
$clientSecret = _env('GOOGLE_CLIENT_SECRET', '');
$refreshToken = _env('GOOGLE_REFRESH_TOKEN', '');
$folderId     = _env('GOOGLE_DRIVE_FOLDER_ID', '');

if (!$clientId || !$clientSecret || !$refreshToken || !$folderId) {
    $log('ERROR: Missing env vars (GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REFRESH_TOKEN, GOOGLE_DRIVE_FOLDER_ID)');
    unlink($tmpZip); exit(1);
}

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type'    => 'refresh_token',
    ]),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_TIMEOUT        => 30,
]);
$tokenResp = curl_exec($ch); curl_close($ch);
$tokenData = json_decode($tokenResp, true);

if (empty($tokenData['access_token'])) {
    $log('ERROR: Auth failed: ' . $tokenResp);
    unlink($tmpZip); exit(1);
}
$accessToken = $tokenData['access_token'];
$log('Google auth OK');

// ── Step 5: Upload ZIP to Drive ───────────────────────────
$zipContents = file_get_contents($tmpZip);
unlink($tmpZip);
$filename = "Invyrr_FullBackup_{$date}.zip";
$meta     = json_encode(['name' => $filename, 'parents' => [$folderId]]);
$boundary = '----InvyrrBackup' . uniqid();
$body     = "--{$boundary}\r\n"
          . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
          . $meta . "\r\n"
          . "--{$boundary}\r\n"
          . "Content-Type: application/zip\r\n\r\n"
          . $zipContents . "\r\n"
          . "--{$boundary}--";

$ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer {$accessToken}",
        "Content-Type: multipart/related; boundary={$boundary}",
        "Content-Length: " . strlen($body),
    ],
    CURLOPT_TIMEOUT        => 120,
]);
$uploadResp = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$uploadData = json_decode($uploadResp, true);
if ($httpCode === 200 && !empty($uploadData['id'])) {
    $log("✅ Backup uploaded: {$uploadData['name']}");
    $log("   https://drive.google.com/file/d/{$uploadData['id']}/view");
} else {
    $log("ERROR uploading (HTTP {$httpCode}): " . $uploadResp);
    exit(1);
}
