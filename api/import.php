<?php
/**
 * Invyrr API — Excel / CSV Import
 *
 * POST /api/import.php
 *   Multipart form-data:
 *     file   → .xlsx or .csv file
 *     type   → products | vendors | stock_in | stock_out
 *     mode   → insert (skip duplicates) | upsert (update by name/sku) | replace (truncate first)
 *
 * GET /api/import.php?template=products|vendors|stock_in|stock_out
 *   → Download a blank CSV template with correct headers + example row
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../includes/db.php';
startSession();
requireAuth();

// ── Template download ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['template'])) {

    // For products template, fetch locations dynamically
    $locationNames = [];
    if ($_GET['template'] === 'products') {
        try {
            startSession();
            $pdo = getDB();
            $rawNames = $pdo->query("SELECT name FROM locations ORDER BY is_default DESC, name ASC")
                            ->fetchAll(PDO::FETCH_COLUMN);
            // Strip "(Primary)" suffix for cleaner column headers
            foreach ($rawNames as $n) {
                $locationNames[] = trim(preg_replace('/\s*\(Primary\)\s*/i', '', $n));
            }
        } catch (Throwable $e) {
            $locationNames = [];
        }
    }

    // Build product headers/example dynamically with location columns
    $locHeaders = count($locationNames) ? $locationNames : ['Location'];
    $locExample = [];
    foreach (array_keys($locHeaders) as $i) {
        $locExample[] = $i === 0 ? '100' : '0';
    }
    $locNote = count($locationNames)
        ? '# Location columns: enter the opening stock qty for each location. Leave 0 or blank if none.'
        : '# Location column: enter the location name from your system.';

    $templates = [
        'products' => [
            'filename' => 'import_products_template.csv',
            'headers'  => array_merge(
                ['SKU','Product Name*','Brand','Category','Vendor Name','List Price','Cost Price','Landing Cost','Sell Price','Wholesale Price','Case Content','Box Content'],
                $locHeaders,
                ['Min Stock','Unit','Description','Combo (Yes/No)','Active (Yes/No)','Push to Web (Yes/No)']
            ),
            'example'  => array_merge(
                ['SPK-001','Sparklers 10cm','Star Brand','Sparklers','Raj Crackers Co.','16.00','12.00','13.50','25.00','20.00','12','6'],
                $locExample,
                ['20','Box','Standard sparklers','No','Yes','No']
            ),
            'notes'    => [
                '# NOTES: Fields marked * are required.',
                $locNote,
                '# Combo: Yes or No (default No)',
                '# Active: Yes or No (default Yes) — inactive products hidden from procurement',
                '# Push to Web: Yes or No (default No)',
            ],
        ],
        'vendors' => [
            'filename' => 'import_vendors_template.csv',
            'headers'  => ['Vendor Name*','Type','Contact Person','Phone','Email','City','GST Number','Address'],
            'example'  => ['Raj Crackers Co.','Rajan Kumar','9876543210','rajan@rajcrackers.com','Sivakasi','33AABCR1234A1Z5','123 Market Road'],
            'notes'    => ['# NOTES: Fields marked * are required.'],
        ],
        'stock_in' => [
            'filename' => 'import_stock_in_template.csv',
            'headers'  => ['Date* (YYYY-MM-DD)','Product Name*','Vendor Name','Quantity*','Cost Price','Note'],
            'example'  => [date('Y-m-d'),'Sparklers 10cm','Raj Crackers Co.','50','12.00','Invoice #1001'],
            'notes'    => ['# NOTES: Product Name must match an existing product exactly.'],
        ],
        'stock_out' => [
            'filename' => 'import_stock_out_template.csv',
            'headers'  => ['Date* (YYYY-MM-DD)','Product Name*','Customer','Quantity*','Sell Price','Note'],
            'example'  => [date('Y-m-d'),'Sparklers 10cm','Walk-in Customer','10','25.00','Cash sale'],
            'notes'    => ['# NOTES: Product Name must match an existing product exactly.'],
        ],
        'payees' => [
                'filename' => 'import_payees_template.csv',
                'headers'  => ['Name*','Type','Bank Name','Account No','IFSC','UPI ID','Phone','Notes','Status'],
                'example'  => ['Raj','UPI','','','','raj@upi','9876543210','','Active'],
                'notes'    => [
                    '# NOTES: Fields marked * are required.',
                    '# Type: Person, Bank Account, UPI, Cash, Cheque, Other (defaults to Cash if blank)',
                    '# Status: Active or Inactive (defaults to Active)',
                    '# Existing payees in Invyrr are NEVER overwritten — only new payees are created',
                ],
            ],
        'expenses' => [
                'filename' => 'import_expenses_template.csv',
                'headers'  => ['Date*','Category*','Amount*','Vendor','Paid Via','Payee Type','Paid To','Business','Ref No.','Notes'],
                'example'  => ['2026-06-01','Salaries','5000','','Rajarajan','PhonePe','Murugan','SVT','INV-001','Monthly salary'],
                'notes'    => [
                    '# NOTES: Fields marked * are required.',
                    '# Date format: YYYY-MM-DD or DD-MM-YYYY or DD/MM/YYYY',
                    '# Paid Via: who/what funded the payment (e.g. Rajarajan via PhonePe). Matched against existing Payees by name.',
                    '# Payee Type: Cash, Bank Account, UPI, Person, Cheque, Other, or any custom type you have created (optional)',
                    '# Paid To: optional — who actually received the money (e.g. an employee). Leave blank if same as Paid Via.',
                    '# Business: optional — must match an existing Business name in Invyrr exactly (e.g. SVT, RRA). This is separate from stock Locations. Leave blank for unassigned.',
                    '# Vendor, Paid Via and Paid To must match existing names in Invyrr — new Paid Via names are created as Cash by default',
                ],
            ],
        'purchase_orders' => [
            'filename' => 'import_purchase_orders_template.csv',
            'headers'  => ['Product SKU','Product Name*','Qty Ordered*','Cost Price','Notes (Item)','Vendor Name*','Location','Expected Date (YYYY-MM-DD)','Status','Notes'],
            'example'  => ['SPK-001','Sparklers 10cm','100','12.00','','Raj Crackers Co.','RR Crackers','2025-12-31','draft','Festive season order'],
            'notes'    => [
                '# NOTES: Fields marked * are required.',
                '# One row per line item. Repeat Vendor/Location/Expected Date/Status/Notes for each item in the same PO.',
                '# Multiple rows with the same Vendor+Expected Date will be grouped into one PO.',
                '# Status: draft, sent (default: draft)',
            ],
        ],
        'website_orders' => [
            'filename' => 'import_website_orders_template.csv',
            'headers'  => ['Order Type','Order Number*','Order Date*','Customer Name*','City','Mobile Number','Amount*','Order Status','Amount Paid','Paid Date','Account','Cash','Cash Person','Gift','Dispatch Status','Dispatch Date','Transport','# of Boxes','Comments'],
            'example'  => ['Frontend Order','2025RR1130','08-10-2025','Arumugamsumathi','Ariyalur','9943519083','5929','Paid','5929','08-10-2025','Baby ka','','','','Dispatched','09-10-2025','ABC Transport','2','Handle with care'],
            'notes'    => [
                '# NOTES: Fields marked * are required.',
                '# Order Number must be unique — re-importing the same Order Number updates that order (upsert) and refreshes its payments.',
                '# Date format: YYYY-MM-DD or DD-MM-YYYY or DD/MM/YYYY',
                '# Order Status: Paid, Partial, Pending, or Cancelled (auto-adjusted based on Amount Paid if left blank)',
                '# Account: the account/person the payment was received into (e.g. a bank/UPI or staff name) — matched or created as a Payee automatically.',
                '# Cash / Cash Person: if part or all was collected as cash, put the cash amount in Cash and who collected it in Cash Person. Amount Paid should be the combined total.',
                '# Gift, Dispatch Status, Transport, Comments are free text. # of Boxes is a whole number.',
            ],
        ],
    ];

    $type = $_GET['template'];
    if (!isset($templates[$type])) { http_response_code(404); exit('Unknown template type'); }
    $tpl = $templates[$type];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $tpl['filename'] . '"');
    header('Cache-Control: no-cache');

    $out = fopen('php://output', 'w');
    foreach (($tpl['notes'] ?? []) as $note) fputcsv($out, [$note]);
    fputcsv($out, $tpl['headers']);
    fputcsv($out, $tpl['example']);
    fclose($out);
    exit;
}

// ── Import POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$type = trim($_POST['type'] ?? '');
$mode = trim($_POST['mode'] ?? 'insert'); // insert | upsert

if (!in_array($type, ['products','vendors','stock_in','stock_out','expenses','purchase_orders','payees','website_orders'])) {
    jsonError('Invalid type. Must be: products, vendors, stock_in, stock_out, expenses, purchase_orders, payees, website_orders');
}

// ── File validation ──────────────────────────────────────
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errCodes = [1=>'File too large',2=>'File too large',3=>'Partial upload',4=>'No file',6=>'No temp dir',7=>'Write failed'];
    $errCode  = $_FILES['file']['error'] ?? 4;
    jsonError('Upload failed: ' . ($errCodes[$errCode] ?? "Error $errCode"));
}

$tmpPath  = $_FILES['file']['tmp_name'];
$origName = strtolower($_FILES['file']['name']);
$ext      = pathinfo($origName, PATHINFO_EXTENSION);

if (!in_array($ext, ['csv','xlsx','xls'])) {
    jsonError('Unsupported file type. Please upload .csv or .xlsx');
}

// ── Parse file into rows ─────────────────────────────────
$rows = [];

if ($ext === 'csv') {
    $rows = parseCSV($tmpPath);
} else {
    // Try PhpSpreadsheet first, fall back to ZIP/XML parsing
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require __DIR__ . '/../vendor/autoload.php';
        $rows = parseXLSX_Spreadsheet($tmpPath);
    } else {
        $rows = parseXLSX_Native($tmpPath);
    }
}

if (empty($rows)) {
    jsonError('No data rows found in file. Make sure you have at least one data row below the header.');
}

// ── Dispatch to importer ─────────────────────────────────
$pdo = getDB();

if($type==='products')           $result=importProducts($pdo,$rows,$mode);
elseif($type==='vendors')        $result=importVendors($pdo,$rows,$mode);
elseif($type==='stock_in')       $result=importStockIn($pdo,$rows);
elseif($type==='stock_out')      $result=importStockOut($pdo,$rows);
elseif($type==='purchase_orders')$result=importPurchaseOrders($pdo,$rows,$mode);
elseif($type==='expenses')       $result=importExpenses($pdo,$rows);
elseif($type==='payees')         $result=importPayees($pdo,$rows);
elseif($type==='website_orders') $result=importWebsiteOrders($pdo,$rows,$mode);
else jsonError('Unknown import type');

// ── Write audit log entries ──────────────────────────────────────────────────
startSession();
$pdo = getDB();

// 1. Summary entry — one line for the whole import
$summary = sprintf(
    'Import %s (%s mode): %d inserted, %d updated, %d skipped, %d errors',
    $type, $mode, $result['inserted'], $result['updated'], $result['skipped'], count($result['errors'])
);
auditLog($pdo, 'import', $type, 0, $summary);

// 2. One audit log entry per skipped product (products import only)
if (!empty($result['skipped_details'])) {
    foreach ($result['skipped_details'] as $sd) {
        $detail = sprintf(
            'SKIPPED Row %d | SKU: %s | Name: %s | Brand: %s | Vendor: %s | Cost: %s | Reason: %s',
            $sd['row'],
            $sd['sku']    ?: '(none)',
            $sd['name']   ?: '(blank)',
            $sd['brand']  ?: '—',
            $sd['vendor'] ?: '—',
            $sd['cost']   ?: '—',
            $sd['reason']
        );
        auditLog($pdo, 'import_skipped', $type, 0, $detail);
    }
}

jsonOk($result, sprintf(
    'Import complete: %d inserted, %d updated, %d skipped, %d errors',
    $result['inserted'], $result['updated'], $result['skipped'], count($result['errors'])
));


// ════════════════════════════════════════════════════════
// FILE PARSERS
// ════════════════════════════════════════════════════════

function parseCSV(string $path): array {
    $rows   = [];
    $header = null;
    $handle = fopen($path, 'r');

    while (($line = fgetcsv($handle, 4096)) !== false) {
        // Skip comment lines and blank lines
        if (empty($line) || (count($line) === 1 && (trim($line[0]) === '' || strncmp(trim($line[0]),'#',1)===0))) {
            continue;
        }
        if ($header === null) {
            // Normalize header keys: lowercase, strip *, spaces→underscores, strip parentheticals
            $header = array_map(function($h){ return preg_replace('/\s*\(.*?\)/', '', strtolower(str_replace(['*',' '], ['','_'], trim($h)))); }, $line);
            continue;
        }
        // Pad row to match header length
        while (count($line) < count($header)) $line[] = '';
        $rows[] = array_combine($header, array_map('trim', $line));
    }
    fclose($handle);
    return $rows;
}

/** Parse XLSX using PhpSpreadsheet (best compatibility) */
function parseXLSX_Spreadsheet(string $path): array {
    $reader      = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($path);
    $sheet       = $spreadsheet->getActiveSheet();
    $data        = $sheet->toArray(null, true, true, false);

    $rows   = [];
    $header = null;

    foreach ($data as $row) {
        $row = array_map(function($v){ return trim((string)($v ?? '')); }, $row);
        if (array_filter($row) === []) continue; // skip empty rows

        if ($header === null) {
            if (isset($row[0]) && strncmp((string)$row[0], '#', 1) === 0) continue; // skip comment rows
            $header = array_map(function($h){ return preg_replace('/\s*\(.*?\)/', '', strtolower(str_replace(['*',' '], ['','_'], $h))); }, $row);
            continue;
        }
        while (count($row) < count($header)) $row[] = '';
        $rows[] = array_combine($header, $row);
    }
    return $rows;
}

/** Minimal native XLSX parser (no dependencies) — reads first sheet via ZIP/XML */
function parseXLSX_Native(string $path): array {
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return [];

    // Read shared strings
    $strings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml) {
        $ss = simplexml_load_string($ssXml);
        if ($ss) {
            foreach ($ss->si as $si) {
                // Collect all <t> text nodes (handles rich text)
                $text = '';
                foreach ($si->xpath('.//t') as $t) $text .= (string)$t;
                $strings[] = $text;
            }
        }
    }

    // Find first sheet name
    $wbXml = $zip->getFromName('xl/workbook.xml');
    $sheetFile = 'xl/worksheets/sheet1.xml';
    if ($wbXml) {
        $wb = simplexml_load_string($wbXml);
        if ($wb && isset($wb->sheets->sheet[0])) {
            $rId = (string)$wb->sheets->sheet[0]->attributes('r', true)['id'];
            $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($relsXml) {
                $rels = simplexml_load_string($relsXml);
                foreach ($rels->Relationship as $rel) {
                    if ((string)$rel['Id'] === $rId) {
                        $sheetFile = 'xl/' . ltrim((string)$rel['Target'], '/');
                        break;
                    }
                }
            }
        }
    }

    $sheetXml = $zip->getFromName($sheetFile);
    $zip->close();
    if (!$sheetXml) return [];

    $sheet = simplexml_load_string($sheetXml);
    if (!$sheet) return [];

    $data   = [];
    foreach ($sheet->sheetData->row as $xmlRow) {
        $rowData = [];
        foreach ($xmlRow->c as $cell) {
            // Determine column index from cell ref (e.g. "B3" → col 1)
            preg_match('/^([A-Z]+)(\d+)$/', (string)$cell['r'], $m);
            $colIdx = colLetterToIndex($m[1]);
            $val    = isset($cell->v) ? (string)$cell->v : '';

            // 's' type = shared string index
            if ((string)$cell['t'] === 's') {
                $val = $strings[(int)$val] ?? '';
            }
            // Pad gaps with empty strings
            while (count($rowData) < $colIdx) $rowData[] = '';
            $rowData[$colIdx] = trim($val);
        }
        $data[] = $rowData;
    }

    // Now normalize to associative array same as parseCSV
    $rows   = [];
    $header = null;
    foreach ($data as $row) {
        if (array_filter($row) === []) continue;
        if ($header === null) {
            if (isset($row[0]) && strncmp((string)$row[0], '#', 1) === 0) continue;
            $header = array_map(function($h){ return preg_replace('/\s*\(.*?\)/', '', strtolower(str_replace(['*',' '], ['','_'], $h))); }, $row);
            continue;
        }
        while (count($row) < count($header)) $row[] = '';
        $rows[] = array_combine($header, $row);
    }
    return $rows;
}

function colLetterToIndex(string $col): int {
    $idx = 0;
    foreach (str_split($col) as $char) {
        $idx = $idx * 26 + (ord($char) - ord('A') + 1);
    }
    return $idx - 1;
}


// ════════════════════════════════════════════════════════
// IMPORTERS
// ════════════════════════════════════════════════════════

function importProducts(PDO $pdo, array $rows, string $mode): array {
    $result = ['inserted'=>0,'updated'=>0,'skipped'=>0,'errors'=>[],'created'=>[],'skipped_details'=>[]];

    // ── Auto-create missing categories ────────────────────
    $catMap = [];
    foreach ($pdo->query("SELECT id, LOWER(name) AS lname FROM categories")->fetchAll() as $c) {
        $catMap[$c['lname']] = $c['id'];
    }
    foreach ($rows as $row) {
        $cat = trim($row['category'] ?? '');
        if ($cat === '') continue;
        $key = strtolower($cat);
        if (!isset($catMap[$key])) {
            $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (?)")->execute([$cat]);
            $id = (int)$pdo->lastInsertId();
            if ($id) {
                $catMap[$key] = $id;
                $result['created'][] = "Category: $cat";
            }
        }
    }

    // ── Auto-create missing vendors ────────────────────────
    $vendorMap = [];
    foreach ($pdo->query("SELECT id, LOWER(name) AS lname FROM vendors")->fetchAll() as $v) {
        $vendorMap[$v['lname']] = $v['id'];
    }
    foreach ($rows as $row) {
        $vn = trim($row['vendor_name'] ?? $row['vendor'] ?? '');
        if ($vn === '') continue;
        $key = strtolower($vn);
        if (!isset($vendorMap[$key])) {
            $pdo->prepare("INSERT INTO vendors (name) VALUES (?)")->execute([$vn]);
            $id = (int)$pdo->lastInsertId();
            if ($id) {
                $vendorMap[$key] = $id;
                $result['created'][] = "Vendor: $vn";
                // Link any products already waiting for this vendor
                $pdo->prepare("UPDATE products SET vendor_id=?, pending_vendor_name=NULL WHERE pending_vendor_name=? AND vendor_id IS NULL")
                    ->execute([$id, $vn]);
            }
        }
    }

    // Pre-load existing products keyed by SKU+vendor_id (SKU alone is not unique)
    $existBySkuVendor = [];  // 'sku|vendor_id' => product_id
    $existBySkuOnly   = [];  // 'sku' => [id, ...]  (all products with that SKU regardless of vendor)
    foreach ($pdo->query("SELECT id, sku, COALESCE(vendor_id,0) AS vendor_id FROM products WHERE sku IS NOT NULL AND sku != ''")->fetchAll() as $p) {
        $svKey = strtolower($p['sku']).'|'.(int)$p['vendor_id'];
        $existBySkuVendor[$svKey] = (int)$p['id'];
        $existBySkuOnly[strtolower($p['sku'])][] = (int)$p['id'];
    }

    // Pre-load locations: key = stripped lowercase name (no "(Primary)", spaces kept)
    // Parsers normalise headers the same way so "RR Crackers" in the sheet matches "rr crackers" here
    $locMap = [];
    foreach ($pdo->query("SELECT id, name FROM locations")->fetchAll() as $l) {
        $stripped = strtolower(trim(preg_replace('/\s*\(Primary\)\s*/i', '', $l['name'])));
        $locMap[$stripped] = (int)$l['id'];
    }

    // Default location for stock seeding
    $defLoc = $pdo->query("SELECT id FROM locations WHERE is_default=1 LIMIT 1")->fetch();
    if (!$defLoc) $defLoc = $pdo->query("SELECT id FROM locations ORDER BY id LIMIT 1")->fetch();
    $defaultLocationId = $defLoc ? (int)$defLoc['id'] : null;

    // Ensure list_price column exists (added after cost)
    try { $pdo->exec("ALTER TABLE products ADD COLUMN list_price DECIMAL(12,2) DEFAULT NULL AFTER cost"); } catch (Exception $e) {}
    // Ensure combo, procurement_active, publish_web exist
    try { $pdo->exec("ALTER TABLE products ADD COLUMN combo TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE products ADD COLUMN procurement_active TINYINT(1) NOT NULL DEFAULT 1"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE products ADD COLUMN publish_web TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}

    $insertStmt = $pdo->prepare('
        INSERT INTO products (name, sku, item_code, brand, category, vendor_id, pending_vendor_name, cost, list_price, sell, wholesale_price, stock, min_stock, unit, description, case_content, box_content, landing_cost, combo, procurement_active, publish_web)
        VALUES (:name,:sku,:item_code,:brand,:category,:vendor_id,:pending_vendor_name,:cost,:list_price,:sell,:wholesale_price,:stock,:min_stock,:unit,:description,:case_content,:box_content,:landing_cost,:combo,:procurement_active,:publish_web)');

    // Upsert also updates stock and min_stock on the product row
    $updateStmt = $pdo->prepare('
        UPDATE products SET name=:name, sku=:sku, item_code=:item_code, brand=:brand, category=:category, vendor_id=:vendor_id, pending_vendor_name=:pending_vendor_name,
            cost=:cost, list_price=:list_price, sell=:sell, wholesale_price=:wholesale_price, stock=:stock, min_stock=:min_stock, unit=:unit, description=:description, case_content=:case_content, box_content=:box_content, landing_cost=:landing_cost,
            combo=:combo, procurement_active=:procurement_active, publish_web=:publish_web
        WHERE id=:id');

    // Upsert per-location stock
    $upsertLocStmt = $pdo->prepare('
        INSERT INTO product_locations (product_id, location_id, stock, min_stock)
        VALUES (:pid, :lid, :stock, :min_stock)
        ON DUPLICATE KEY UPDATE stock=VALUES(stock), min_stock=VALUES(min_stock)');

    // Pre-scan file: flag SKU+vendor combinations that appear more than once
    // Same SKU from different vendors in the same file is perfectly valid
    $inFileSVKeys = [];  // 'sku|vendor' => true/false (true = seen multiple times)
    foreach ($rows as $i => $row) {
        $s = strtolower(trim($row['sku'] ?? $row['sku_/_item_code'] ?? ''));
        $v = strtolower(trim($row['vendor_name'] ?? $row['vendor'] ?? ''));
        if ($s) {
            $k = $s.'|'.$v;
            $inFileSVKeys[$k] = isset($inFileSVKeys[$k]) ? true : false;
        }
    }

    foreach ($rows as $i => $row) {
        $lineNum = $i + 2;

        // Flexible column name aliases
        $name = trim($row['product_name'] ?? $row['name'] ?? '');
        $cost = trim($row['cost_price'] ?? $row['cost'] ?? '');
        $landCost = trim($row['landing_cost'] ?? $row['land_cost'] ?? '');
        $landCost = $landCost !== '' && is_numeric($landCost) ? (float)$landCost : null;
        $wholeSale = trim($row['wholesale_price'] ?? $row['wholesale'] ?? '');
        $wholeSale = $wholeSale !== '' && is_numeric($wholeSale) ? (float)$wholeSale : null;
        $listPrice = trim($row['list_price'] ?? $row['list price'] ?? '');
        $listPrice = $listPrice !== '' && is_numeric($listPrice) ? (float)$listPrice : null;
        $sell = trim($row['sell_price'] ?? $row['sell'] ?? '');

        if ($name === '') {
            $result['errors'][] = "Row $lineNum: Product Name is required";
            $result['skipped_details'][] = ['row'=>$lineNum,'sku'=>trim($row['sku']??''),'name'=>'(blank)','brand'=>trim($row['brand']??''),'vendor'=>trim($row['vendor_name']??$row['vendor']??''),'cost'=>trim($row['cost_price']??$row['cost']??''),'reason'=>'Product Name is required'];
            $result['skipped']++; continue;
        }
        if (!is_numeric($cost)) $cost = 0; // cost price is optional, defaults to 0
        if (!is_numeric($sell)) $sell = 0; // sell price is optional, defaults to 0

        $sku      = trim($row['sku'] ?? $row['sku_/_item_code'] ?? '');
        // Extract leading digits only — e.g. '2405V2' → 2405, not 24052
        $itemCode = preg_match('/^(\d+)/', $sku, $m) ? (int)$m[1] : null;
        $brand    = trim($row['brand'] ?? '');
        $category = trim($row['category'] ?? '');
        $vendorId = null;
        $pendingVendorName = null;
        $vName    = strtolower(trim($row['vendor_name'] ?? $row['vendor'] ?? ''));
        if ($vName !== '') {
            $vendorId = $vendorMap[$vName] ?? null;
            if ($vendorId === null) $pendingVendorName = trim($row['vendor_name'] ?? $row['vendor'] ?? '');
        }

        // Resolve per-location stock from location-named columns
        // locMap keys are stripped lowercase with spaces: "rr crackers"
        // Parser keys may have underscores: "rr_crackers" — normalise back to spaces for matching
        $locationStocks = [];

        foreach ($row as $colKey => $colVal) {
            // Normalise: lowercase, replace underscores back to spaces, strip parentheticals
            $normKey = strtolower(trim(preg_replace('/\s*\(.*?\)\s*/', '', str_replace('_', ' ', $colKey))));
            if (isset($locMap[$normKey]) && is_numeric($colVal) && (int)$colVal > 0) {
                $lid = $locMap[$normKey];
                $locationStocks[$lid] = max($locationStocks[$lid] ?? 0, (int)$colVal);
            }
        }

        // Legacy fallback: single "Opening Stock" + optional "Location" column
        if (empty($locationStocks)) {
            $legacyQty = max(0, (int)($row['opening_stock'] ?? $row['stock'] ?? 0));
            if ($legacyQty > 0) {
                $rowLocName = strtolower(trim($row['location'] ?? $row['location_name'] ?? ''));
                $rowLocId   = $rowLocName !== '' ? ($locMap[$rowLocName] ?? $defaultLocationId) : $defaultLocationId;
                if ($rowLocId) {
                    $locationStocks[$rowLocId] = $legacyQty;
                }
            }
        }

        $stock    = array_sum($locationStocks);
        $minStock = max(0, (int)($row['min_stock'] ?? $row['min_stock_(alert_level)'] ?? 0));
        $unit     = trim($row['unit'] ?? 'Box') ?: 'Box';
        $desc     = trim($row['description'] ?? $row['description_/_notes'] ?? '');
        $caseQty  = trim($row['case_qty'] ?? $row['case_content'] ?? '');
        $caseQty  = $caseQty !== '' && is_numeric($caseQty) ? (int)$caseQty : null;
        $boxQty   = trim($row['box_content'] ?? $row['box_qty'] ?? '');
        $boxQty   = $boxQty !== '' ? $boxQty : null;

        $combo      = isset($row['combo_(yes/no)']) || isset($row['combo'])
            ? (in_array(strtolower(trim((string)($row['combo_(yes/no)'] ?? $row['combo'] ?? ''))), ['yes','1','true']) ? 1 : 0) : 0;
        $procActive = isset($row['active_(yes/no)']) || isset($row['active']) || isset($row['procurement_active'])
            ? (in_array(strtolower(trim((string)($row['active_(yes/no)'] ?? $row['active'] ?? $row['procurement_active'] ?? ''))), ['no','0','false']) ? 0 : 1) : 1;
        $publishWeb = isset($row['push_to_web_(yes/no)']) || isset($row['push_to_web']) || isset($row['publish_web'])
            ? (in_array(strtolower(trim((string)($row['push_to_web_(yes/no)'] ?? $row['push_to_web'] ?? $row['publish_web'] ?? ''))), ['yes','1','true']) ? 1 : 0) : 0;

        $params = [
            ':name'=>$name, ':sku'=>$sku, ':item_code'=>$itemCode, ':brand'=>$brand, ':category'=>$category,
            ':vendor_id'=>$vendorId, ':pending_vendor_name'=>$pendingVendorName,
            ':cost'=>(float)$cost, ':sell'=>(float)$sell, ':stock'=>$stock, ':min_stock'=>$minStock,
            ':unit'=>$unit, ':description'=>$desc, ':case_content'=>$caseQty, ':box_content'=>$boxQty,
            ':landing_cost'=>$landCost, ':wholesale_price'=>$wholeSale, ':list_price'=>$listPrice,
            ':combo'=>$combo, ':procurement_active'=>$procActive, ':publish_web'=>$publishWeb
        ];

        // Determine if exists — match on SKU + vendor_id (SKU alone is not unique)
        $existingId = null;
        if ($sku) {
            $skuLower = strtolower($sku);
            if ($vendorId) {
                // Preferred: exact SKU + vendor match
                $svLookup = $skuLower.'|'.(int)$vendorId;
                if (isset($existBySkuVendor[$svLookup])) $existingId = $existBySkuVendor[$svLookup];
            }
            // Fallback: if only one product has this SKU total, match it
            if (!$existingId && isset($existBySkuOnly[$skuLower]) && count($existBySkuOnly[$skuLower]) === 1) {
                $existingId = $existBySkuOnly[$skuLower][0];
            }
            // Multiple products share this SKU across vendors and vendor not resolved → insert as new
        }

        try {
            if ($existingId && $mode === 'upsert') {
                $ifSvKey = strtolower($sku).'|'.strtolower(trim($row['vendor_name']??$row['vendor']??''));
                if (isset($inFileSVKeys[$ifSvKey]) && $inFileSVKeys[$ifSvKey] === true) {
                    $reason = "SKU '" . $sku . "' from the same vendor appears multiple times in this file";
                    $result['errors'][] = "Row $lineNum ($name): Skipped — " . $reason;
                    $result['skipped_details'][] = ['row'=>$lineNum,'sku'=>$sku,'name'=>$name,'brand'=>$brand??'','vendor'=>trim($row['vendor_name']??$row['vendor']??''),'cost'=>$cost,'reason'=>$reason];
                    $result['skipped']++;
                    continue;
                }
                $p2 = $params; $p2[':id'] = $existingId; $updateStmt->execute($p2);
                // Sync per-location stock for each location found in the import row
                if (!empty($locationStocks)) {
                    foreach ($locationStocks as $lid => $qty) {
                        $upsertLocStmt->execute([':pid'=>$existingId, ':lid'=>$lid, ':stock'=>$qty, ':min_stock'=>$minStock]);
                    }
                    $total = $pdo->query("SELECT COALESCE(SUM(stock),0) FROM product_locations WHERE product_id=$existingId")->fetchColumn();
                    $pdo->prepare("UPDATE products SET stock=? WHERE id=?")->execute([(int)$total, $existingId]);
                }
                $result['updated']++;

            } elseif ($existingId && $mode === 'insert') {
                $ifSvKey2 = strtolower($sku).'|'.strtolower(trim($row['vendor_name']??$row['vendor']??''));
                $inFileConflict = isset($inFileSVKeys[$ifSvKey2]);
                $reason = $inFileConflict
                    ? "SKU '" . $sku . "' from vendor '" . trim($row['vendor_name']??$row['vendor']??'') . "' already imported earlier in this file"
                    : "SKU '" . $sku . "' with this vendor already exists in the database (use 'Update existing' to overwrite)";
                $result['errors'][] = "Row $lineNum ($name): Skipped — " . $reason;
                $result['skipped_details'][] = ['row'=>$lineNum,'sku'=>$sku,'name'=>$name,'brand'=>$brand??'','vendor'=>trim($row['vendor_name']??$row['vendor']??''),'cost'=>$cost,'reason'=>$reason];
                $result['skipped']++;

            } else {
                // Insert new product
                $insertStmt->execute($params);
                $newId = (int)$pdo->lastInsertId();

                // Seed product_locations for ALL locations
                $allLocIds = $pdo->query("SELECT id FROM locations")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($allLocIds as $lid) {
                    $locStock = $locationStocks[(int)$lid] ?? 0;
                    $upsertLocStmt->execute([':pid'=>$newId, ':lid'=>(int)$lid, ':stock'=>$locStock, ':min_stock'=>$minStock]);
                }

                if ($sku) {
                    $newSvKey = strtolower($sku).'|'.(int)$vendorId;
                    $existBySkuVendor[$newSvKey] = $newId;
                    $existBySkuOnly[strtolower($sku)][] = $newId;
                }
                $result['inserted']++;
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (preg_match("/Duplicate entry '(.+?)' for key/", $msg, $m)) {
                $msg = "Duplicate value '" . $m[1] . "' — already exists in DB";
            } elseif (preg_match("/Column '(\w+)' cannot be null/", $msg, $m)) {
                $msg = "Column '" . $m[1] . "' cannot be empty";
            }
            $result['errors'][] = "Row $lineNum ($name): " . $msg;
            $result['skipped_details'][] = ['row'=>$lineNum,'sku'=>$sku,'name'=>$name,'brand'=>$brand??'','vendor'=>trim($row['vendor_name']??$row['vendor']??''),'cost'=>$cost,'reason'=>'DB error: '.$msg];
            $result['skipped']++;
        }
    }
    // Report how many products have unresolved vendor names
    $pending = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE pending_vendor_name IS NOT NULL AND vendor_id IS NULL")->fetchColumn();
    if ($pending > 0) {
        $result['warnings'][] = "$pending product(s) have a vendor name that doesn't match any existing vendor. They will be automatically linked when you add those vendors.";
    }
    return $result;
}

function importVendors(PDO $pdo, array $rows, string $mode): array {
    $result = ['inserted'=>0,'updated'=>0,'skipped'=>0,'errors'=>[]];

    $existByName = [];
    foreach ($pdo->query("SELECT id, LOWER(name) AS lname FROM vendors")->fetchAll() as $v) {
        $existByName[$v['lname']] = $v['id'];
    }

    $allowedTypes = ['Fireworks','Agent','Both','Other'];

    $insertStmt = $pdo->prepare('
        INSERT INTO vendors (name, type, contact, phone, email, city, gst, address)
        VALUES (:name,:type,:contact,:phone,:email,:city,:gst,:address)');

    $updateStmt = $pdo->prepare('
        UPDATE vendors SET type=:type, contact=:contact, phone=:phone, email=:email, city=:city, gst=:gst, address=:address
        WHERE id=:id');

    foreach ($rows as $i => $row) {
        $lineNum = $i + 2;
        $name    = trim($row['vendor_name'] ?? $row['vendor_/_company_name'] ?? $row['name'] ?? '');
        if ($name === '') { $result['errors'][] = "Row $lineNum: Vendor Name is required"; $result['skipped']++; continue; }

        $typeVal = trim($row['type'] ?? '');
        $typeVal = in_array($typeVal, $allowedTypes) ? $typeVal : '';
        $params = [
            ':type'    => $typeVal,
            ':contact' => trim($row['contact_person'] ?? $row['contact'] ?? ''),
            ':phone'   => trim($row['phone'] ?? ''),
            ':email'   => trim($row['email'] ?? ''),
            ':city'    => trim($row['city'] ?? ''),
            ':gst'     => trim($row['gst_number'] ?? $row['gst'] ?? ''),
            ':address' => trim($row['address'] ?? $row['address_/_notes'] ?? ''),
        ];

        $existingId = $existByName[strtolower($name)] ?? null;

        try {
            if ($existingId && $mode === 'upsert') {
                $ifSvKey = strtolower($sku).'|'.strtolower(trim($row['vendor_name']??$row['vendor']??''));
                if (isset($inFileSVKeys[$ifSvKey]) && $inFileSVKeys[$ifSvKey] === true) {
                    $reason = "SKU '" . $sku . "' from the same vendor appears multiple times in this file";
                    $result['errors'][] = "Row $lineNum ($name): Skipped — " . $reason;
                    $result['skipped_details'][] = ['row'=>$lineNum,'sku'=>$sku,'name'=>$name,'brand'=>$brand??'','vendor'=>trim($row['vendor_name']??$row['vendor']??''),'cost'=>$cost,'reason'=>$reason];
                    $result['skipped']++;
                    continue;
                }
                $p2 = $params; $p2[':id'] = $existingId; $updateStmt->execute($p2);
                $result['updated']++;
            } elseif ($existingId) {
                $result['skipped']++;
            } else {
                $vp = array_merge([':name'=>$name], $params); $insertStmt->execute($vp);
                $existByName[strtolower($name)] = (int)$pdo->lastInsertId();
                $result['inserted']++;
            }
        } catch (PDOException $e) {
            $result['errors'][] = "Row $lineNum ($name): " . $e->getMessage();
            $result['skipped']++;
        }
    }
    return $result;
}

function importStockIn(PDO $pdo, array $rows): array {
    $result = ['inserted'=>0,'updated'=>0,'skipped'=>0,'errors'=>[]];

    // Product name→id map
    $productMap = [];
    foreach ($pdo->query("SELECT id, LOWER(name) AS lname, cost FROM products")->fetchAll() as $p) {
        $productMap[$p['lname']] = ['id'=>$p['id'], 'cost'=>$p['cost']];
    }

    $vendorMap = [];
    foreach ($pdo->query("SELECT id, LOWER(name) AS lname FROM vendors")->fetchAll() as $v) {
        $vendorMap[$v['lname']] = $v['id'];
    }

    $insTxn  = $pdo->prepare('INSERT INTO stock_in (product_id,vendor_id,qty,cost,date,note) VALUES (:pid,:vid,:qty,:cost,:date,:note)');
    $updProd = $pdo->prepare('UPDATE products SET stock=stock+:qty, cost=:cost WHERE id=:id');

    $pdo->beginTransaction();
    try {
        foreach ($rows as $i => $row) {
            $lineNum = $i + 2;
            $pName   = trim($row['product_name'] ?? $row['product'] ?? '');
            $qtyRaw  = trim($row['quantity'] ?? $row['qty'] ?? '');
            $dateRaw = trim($row['date'] ?? $row['date*_(yyyy-mm-dd)'] ?? date('Y-m-d'));

            if ($pName === '') { $result['errors'][] = "Row $lineNum: Product Name required"; $result['skipped']++; continue; }
            if (!is_numeric($qtyRaw) || (int)$qtyRaw < 1) { $result['errors'][] = "Row $lineNum: Invalid quantity '$qtyRaw'"; $result['skipped']++; continue; }

            $product = $productMap[strtolower($pName)] ?? null;
            if (!$product) { $result['errors'][] = "Row $lineNum: Product '$pName' not found"; $result['skipped']++; continue; }

            // Validate date
            $date = DateTime::createFromFormat('Y-m-d', $dateRaw) ? $dateRaw : date('Y-m-d');

            $cost    = is_numeric($row['cost_price'] ?? $row['cost'] ?? '') ? (float)($row['cost_price'] ?? $row['cost']) : (float)$product['cost'];
            $vName   = strtolower(trim($row['vendor_name'] ?? $row['vendor'] ?? ''));
            $vendorId = $vName ? ($vendorMap[$vName] ?? null) : null;
            $qty      = (int)$qtyRaw;

            $insTxn->execute([':pid'=>$product['id'], ':vid'=>$vendorId, ':qty'=>$qty, ':cost'=>$cost, ':date'=>$date, ':note'=>trim($row['note'] ?? '')]);
            $updProd->execute([':qty'=>$qty, ':cost'=>$cost, ':id'=>$product['id']]);
            $result['inserted']++;
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $result['errors'][] = 'Transaction failed: ' . $e->getMessage();
    }
    return $result;
}

function importStockOut(PDO $pdo, array $rows): array {
    $result = ['inserted'=>0,'updated'=>0,'skipped'=>0,'errors'=>[]];

    $productMap = [];
    foreach ($pdo->query("SELECT id, LOWER(name) AS lname, stock, cost, sell, unit FROM products")->fetchAll() as $p) {
        $productMap[$p['lname']] = $p;
    }

    $insTxn  = $pdo->prepare('INSERT INTO stock_out (product_id,qty,sell_price,cost,customer,date,note) VALUES (:pid,:qty,:sell,:cost,:customer,:date,:note)');
    $updProd = $pdo->prepare('UPDATE products SET stock=stock-:qty WHERE id=:id');

    $pdo->beginTransaction();
    try {
        foreach ($rows as $i => $row) {
            $lineNum = $i + 2;
            $pName   = trim($row['product_name'] ?? $row['product'] ?? '');
            $qtyRaw  = trim($row['quantity'] ?? $row['qty'] ?? '');
            $dateRaw = trim($row['date'] ?? $row['date*_(yyyy-mm-dd)'] ?? date('Y-m-d'));

            if ($pName === '') { $result['errors'][] = "Row $lineNum: Product Name required"; $result['skipped']++; continue; }
            if (!is_numeric($qtyRaw) || (int)$qtyRaw < 1) { $result['errors'][] = "Row $lineNum: Invalid quantity"; $result['skipped']++; continue; }

            $product = $productMap[strtolower($pName)] ?? null;
            if (!$product) { $result['errors'][] = "Row $lineNum: Product '$pName' not found"; $result['skipped']++; continue; }

            $qty = (int)$qtyRaw;
            if ((int)$product['stock'] < $qty) {
                $result['errors'][] = "Row $lineNum ($pName): Insufficient stock ({$product['stock']} {$product['unit']} available, $qty requested)";
                $result['skipped']++;
                continue;
            }

            $date      = DateTime::createFromFormat('Y-m-d', $dateRaw) ? $dateRaw : date('Y-m-d');
            $sellPrice = is_numeric($row['sell_price'] ?? $row['sell'] ?? '') ? (float)($row['sell_price'] ?? $row['sell']) : (float)$product['sell'];

            $insTxn->execute([':pid'=>$product['id'], ':qty'=>$qty, ':sell'=>$sellPrice, ':cost'=>(float)$product['cost'], ':customer'=>trim($row['customer'] ?? ''), ':date'=>$date, ':note'=>trim($row['note'] ?? '')]);
            $updProd->execute([':qty'=>$qty, ':id'=>$product['id']]);

            // Update local stock to catch multi-row oversell within same import
            $productMap[strtolower($pName)]['stock'] -= $qty;
            $result['inserted']++;
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $result['errors'][] = 'Transaction failed: ' . $e->getMessage();
    }
    return $result;
}

function importPurchaseOrders(PDO $pdo, array $rows, string $mode): array {
    $result = ['inserted'=>0,'updated'=>0,'skipped'=>0,'errors'=>[],'created'=>[]];

    // Pre-load maps
    $vendorMap = [];
    foreach ($pdo->query("SELECT id, LOWER(name) AS lname FROM vendors")->fetchAll() as $v)
        $vendorMap[$v['lname']] = (int)$v['id'];

    $locMap = [];
    foreach ($pdo->query("SELECT id, name FROM locations")->fetchAll() as $l) {
        $stripped = strtolower(trim(preg_replace('/\s*\(Primary\)\s*/i', '', $l['name'])));
        $locMap[$stripped] = (int)$l['id'];
    }
    $defLocId = (int)($pdo->query("SELECT id FROM locations WHERE is_default=1 LIMIT 1")->fetchColumn() ?: 1);

    $productMap = [];
    foreach ($pdo->query("SELECT id, LOWER(name) AS lname, sku, cost FROM products")->fetchAll() as $p) {
        $productMap[$p['lname']] = $p;
        if ($p['sku']) $productMap[strtolower($p['sku'])] = $p;
    }

    // Group rows into POs: key = vendor+location+expected_date+notes
    $poGroups = [];
    foreach ($rows as $lineNum => $row) {
        $vName   = strtolower(trim($row['vendor_name'] ?? $row['vendor'] ?? ''));
        $locName = strtolower(trim(preg_replace('/\s*\(Primary\)\s*/i', '', $row['location'] ?? $row['location_name'] ?? '')));
        $expDate = trim($row['expected_date'] ?? $row['expected_date_(yyyy-mm-dd)'] ?? '');
        $notes   = trim($row['notes'] ?? $row['po_notes'] ?? '');
        $status  = trim($row['status'] ?? 'draft');
        if (!in_array($status, ['draft','sent'])) $status = 'draft';

        $groupKey = $vName.'|'.$locName.'|'.$expDate.'|'.$status;
        if (!isset($poGroups[$groupKey])) {
            $poGroups[$groupKey] = [
                'vendor_name' => $vName,
                'location_name' => $locName,
                'expected_date' => $expDate,
                'status' => $status,
                'notes' => $notes,
                'items' => [],
            ];
        }
        $poGroups[$groupKey]['items'][] = [
            'sku'      => strtolower(trim($row['product_sku'] ?? $row['sku'] ?? '')),
            'name'     => strtolower(trim($row['product_name'] ?? '')),
            'qty'      => max(1, (int)($row['qty_ordered'] ?? $row['qty'] ?? $row['quantity'] ?? 1)),
            'cost'     => is_numeric($row['cost_price'] ?? '') ? (float)$row['cost_price'] : 0,
            'item_note'=> trim($row['notes_(item)'] ?? $row['item_note'] ?? ''),
            'lineNum'  => $lineNum + 2,
        ];
    }

    $poPrefix = $pdo->query("SELECT v FROM settings WHERE k='po_prefix'")->fetchColumn() ?: 'PO';
    $poCount  = (int)($pdo->query("SELECT COUNT(*) FROM purchase_orders")->fetchColumn());

    foreach ($poGroups as $group) {
        $vendorId = $vendorMap[$group['vendor_name']] ?? null;
        if (!$vendorId) {
            // Auto-create vendor
            $vDisplay = ucwords($group['vendor_name']);
            $pdo->prepare("INSERT INTO vendors (name) VALUES (?)")->execute([$vDisplay]);
            $vendorId = (int)$pdo->lastInsertId();
            $vendorMap[$group['vendor_name']] = $vendorId;
            $result['created'][] = "Vendor: $vDisplay";
        }

        $locId = $locMap[$group['location_name']] ?? $defLocId;
        $poNum = $poPrefix . '-' . str_pad(++$poCount, 4, '0', STR_PAD_LEFT);
        $expDate = $group['expected_date'] ?: null;

        $pdo->prepare("INSERT INTO purchase_orders (po_number, vendor_id, location_id, status, expected_date, notes, created_at)
                       VALUES (?,?,?,?,?,?,NOW())")
            ->execute([$poNum, $vendorId, $locId, $group['status'], $expDate, $group['notes']]);
        $poId = (int)$pdo->lastInsertId();

        $itemsAdded = 0;
        foreach ($group['items'] as $item) {
            // Find product by SKU first, then by name
            $product = null;
            if ($item['sku']) $product = $productMap[$item['sku']] ?? null;
            if (!$product && $item['name']) $product = $productMap[$item['name']] ?? null;
            if (!$product) {
                $result['errors'][] = "Row {$item['lineNum']}: Product '{$item['name']}' not found — skipped";
                continue;
            }
            $cost = $item['cost'] > 0 ? $item['cost'] : (float)$product['cost'];
            $pdo->prepare("INSERT INTO purchase_order_items (po_id, product_id, qty_ordered, qty_received, cost)
                           VALUES (?,?,?,0,?)")
                ->execute([$poId, (int)$product['id'], $item['qty'], $cost]);
            $itemsAdded++;
        }

        if ($itemsAdded === 0) {
            // No valid items — delete the PO
            $pdo->prepare("DELETE FROM purchase_orders WHERE id=?")->execute([$poId]);
            $result['skipped']++;
        } else {
            $result['inserted']++;
        }
    }
    return $result;
}


// ── Import Expenses ──────────────────────────────────────────────────────────

// Normalize payee type to match the Payees form dropdown values
function normalizePayeeType(string $raw): string {
    $raw = trim($raw);
    if ($raw === '') return 'Cash';

    // Known canonical types — exact match (case-insensitive) snaps to standard casing
    $canonical = ['Person','Bank Account','UPI','Cash','Cheque','Other'];
    foreach ($canonical as $c) {
        if (strcasecmp($raw, $c) === 0) return $c;
    }

    // Common aliases for the canonical types only — NOT for custom types
    // (GPAY, PHONEPE, PAYTM etc. are left as-is since the Payee Types
    // manager lets users create exactly these as their own distinct types)
    $lower = strtolower($raw);
    $aliases = [
        'individual'    => 'Person',
        'bank'          => 'Bank Account',
        'bank_account'  => 'Bank Account',
        'check'         => 'Cheque',
    ];
    if (isset($aliases[$lower])) return $aliases[$lower];

    // Anything else (GPAY, PHONEPE, custom names, etc.) — preserve exactly as typed
    return $raw;
}

function importExpenses(PDO $pdo, array $rows): array {
    $inserted = 0; $skipped = 0; $errors = [];

    // Pre-load payees — key by "name|type" so the same name with different
    // payment types (e.g. Rajarajan/Cash vs Rajarajan/GPAY) resolve to the
    // correct distinct payee record, not always the first one found.
    $payeeMap = [];        // "name|type" -> id  (exact type match)
    $payeeNameOnly = [];   // name -> first id found (fallback when type unknown)
    foreach ($pdo->query("SELECT id, name, type FROM payees")->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $nameKey = strtolower(trim($p['name']));
        $typeKey = strtolower(trim($p['type'] ?? ''));
        $payeeMap[$nameKey.'|'.$typeKey] = $p['id'];
        if (!isset($payeeNameOnly[$nameKey])) $payeeNameOnly[$nameKey] = $p['id'];
    }
    // Pre-load vendors (name → id)
    $vendorMap = [];
    foreach ($pdo->query("SELECT id, name FROM vendors")->fetchAll(PDO::FETCH_ASSOC) as $v) {
        $vendorMap[strtolower(trim($v['name']))] = $v['id'];
    }
    // Pre-load expense entities/businesses (name → id) — strict match only,
    // never auto-created. Independent of the Locations table.
    $entityMap = [];
    try {
        foreach ($pdo->query("SELECT id, name FROM expense_entities")->fetchAll(PDO::FETCH_ASSOC) as $ent) {
            $entityMap[strtolower(trim($ent['name']))] = $ent['id'];
        }
    } catch (PDOException $e) {
        // expense_entities table may not exist yet on first run
    }
    // Load existing expense categories from both sources
    $catSet = [];
    foreach ($pdo->query("SELECT DISTINCT category FROM expenses WHERE category IS NOT NULL AND category != ''")->fetchAll(PDO::FETCH_COLUMN) as $c) {
        $catSet[strtolower(trim($c))] = trim($c);
    }

    $stmt = $pdo->prepare("INSERT INTO expenses
        (expense_date, category, amount, vendor_id, payee_id, paid_to_id, entity_id, reference_no, notes, created_by)
        VALUES (:date, :category, :amount, :vendor_id, :payee_id, :paid_to_id, :entity_id, :ref, :notes, :created_by)");

    foreach ($rows as $i => $row) {
        $rowNum = $i + 2;
        // Normalise keys
        $r = [];
        foreach ($row as $k => $v) {
            $r[strtolower(preg_replace('/[^a-z0-9_]/i','_', trim($k)))] = trim((string)$v);
        }

        $date      = $r['date']         ?? $r['date_']       ?? '';
        $category  = $r['category']     ?? $r['category_']   ?? '';
        $amount    = $r['amount']       ?? $r['amount_']     ?? '';
        // "Paid Via" is the new header name; "Paid By" / "Payee" are legacy fallbacks
        $payeeName = $r['paid_via']      ?? $r['paid_by'] ?? $r['paid_by__payee_name_'] ?? $r['payee'] ?? $r['payee_name'] ?? '';
        // "Payee Type" from CSV — blank defaults to Cash
        $payeeTypeRaw = $r['payee_type'] ?? $r['payee_typ'] ?? $r['type'] ?? '';
        $payeeTypeProvided = trim($payeeTypeRaw) !== '';
        $payeeType = $payeeTypeProvided ? normalizePayeeType($payeeTypeRaw) : 'Cash';
        // "Paid To" — optional recipient (e.g. employee), matched against existing Payees by name only
        $paidToName = $r['paid_to'] ?? '';
        // "Business" — optional, matched against existing Expense Entities by name only.
        // Independent of stock Locations.
        $entityName = $r['business'] ?? $r['location'] ?? '';
        // "Vendor" matches both old ("vendor_name") and new ("vendor")
        $vendorName= $r['vendor']       ?? $r['vendor_name'] ?? '';
        // "Ref No." normalises to ref_no_
        $ref       = $r['ref_no_']      ?? $r['reference_no'] ?? $r['ref'] ?? '';
        $notes     = $r['notes']        ?? '';

        if (!$date || !$category || !$amount) {
            $errors[] = "Row {$rowNum}: Date, Category and Amount are required";
            $skipped++; continue;
        }
        // Validate date
        $dateObj = DateTime::createFromFormat('Y-m-d', $date)
                ?: DateTime::createFromFormat('d/m/Y', $date)
                ?: DateTime::createFromFormat('d-m-Y', $date);
        if (!$dateObj) { $errors[] = "Row {$rowNum}: Invalid date '{$date}'"; $skipped++; continue; }
        $date = $dateObj->format('Y-m-d');

        $amount = floatval(str_replace(',','',$amount));
        if ($amount <= 0) { $errors[] = "Row {$rowNum}: Amount must be > 0"; $skipped++; continue; }

        // Resolve payee — STRICT MATCH ONLY against existing payees in Invyrr.
        // Never auto-create a new payee for a name+type combo that doesn't
        // already exist. If the exact name+type isn't found, fall back to
        // any existing payee with that name; if the name doesn't exist at
        // all, create ONE new payee as Cash (the safe default).
        $payeeId = null;
        if ($payeeName !== '') {
            $nameKey = strtolower($payeeName);
            $typeKey = strtolower($payeeType);

            if ($payeeTypeProvided && isset($payeeMap[$nameKey.'|'.$typeKey])) {
                // Exact name+type match found in Invyrr — use it
                $payeeId = $payeeMap[$nameKey.'|'.$typeKey];
            } elseif (isset($payeeNameOnly[$nameKey])) {
                // Name exists in Invyrr but not with this exact type —
                // use the existing payee record for that name rather than
                // creating a new one. Existing payee data is never altered.
                $payeeId = $payeeNameOnly[$nameKey];
            } else {
                // Name does not exist in Invyrr at all — create as Cash
                try {
                    $pdo->prepare("INSERT INTO payees (name, type) VALUES (?, 'Cash')")->execute([$payeeName]);
                    $payeeId = (int)$pdo->lastInsertId();
                    $payeeMap[$nameKey.'|cash'] = $payeeId;
                    $payeeNameOnly[$nameKey] = $payeeId;
                } catch (PDOException $e) {
                    // Payee creation failed - skip payee
                }
            }
        }
        // Resolve vendor
        $vendorId = null;
        if ($vendorName !== '') {
            $vendorId = $vendorMap[strtolower($vendorName)] ?? null;
        }
        // Resolve "Paid To" — strict match by name only against existing
        // Payees. Never auto-creates a new payee; if the name isn't found,
        // the field is simply left blank (falls back to "same as Paid Via"
        // in the UI).
        $paidToId = null;
        if (trim($paidToName) !== '') {
            $paidToId = $payeeNameOnly[strtolower(trim($paidToName))] ?? null;
        }
        // Resolve "Business" — strict match by name only against existing
        // Expense Entities. Never auto-created; unmatched names are left blank.
        $entityId = null;
        if (trim($entityName) !== '') {
            $entityId = $entityMap[strtolower(trim($entityName))] ?? null;
        }
        // Auto-create category if new
        if (!isset($catSet[strtolower($category)])) {
            $catSet[strtolower($category)] = $category;
        }

        $u = currentUser();
        try {
            $stmt->execute([
                ':date'        => $date,
                ':category'    => $category,
                ':amount'      => $amount,
                ':vendor_id'   => $vendorId,
                ':payee_id'    => $payeeId,
                ':paid_to_id'  => $paidToId,
                ':entity_id'   => $entityId,
                ':ref'         => $ref ?: null,
                ':notes'       => $notes ?: null,
                ':created_by'  => $u['id'] ?? null,
            ]);
            $inserted++;
        } catch (PDOException $e) {
            $errors[] = "Row {$rowNum}: " . $e->getMessage();
            $skipped++;
        }
    }
    return ['inserted'=>$inserted,'updated'=>0,'skipped'=>$skipped,'errors'=>$errors];
}

// ── Import Payees ─────────────────────────────────────────────────────────────
function importPayees(PDO $pdo, array $rows): array {
    $inserted = 0; $updated = 0; $skipped = 0; $errors = [];
    // Allowed types match the Payees form dropdown

    // Pre-load existing payees (name → id, case-insensitive)
    $existingMap = [];
    foreach ($pdo->query("SELECT id, name FROM payees")->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $existingMap[strtolower(trim($p['name']))] = $p['id'];
    }

    $insertStmt = $pdo->prepare("INSERT INTO payees
        (name, type, bank_name, account_no, ifsc, upi_id, phone, notes, is_active)
        VALUES (:name, :type, :bank_name, :account_no, :ifsc, :upi_id, :phone, :notes, :is_active)");

    foreach ($rows as $i => $row) {
        $rowNum = $i + 2;
        $r = [];
        foreach ($row as $k => $v) {
            $r[strtolower(preg_replace('/[^a-z0-9_]/i','_', trim($k)))] = trim((string)$v);
        }

        $name      = $r['name'] ?? $r['name_'] ?? '';
        $type      = $r['type'] ?? '';
        $bankName  = $r['bank_name'] ?? '';
        $accountNo = $r['account_no'] ?? '';
        $ifsc      = $r['ifsc'] ?? '';
        $upiId     = $r['upi_id'] ?? '';
        $phone     = $r['phone'] ?? '';
        $notes     = $r['notes'] ?? '';
        $status    = $r['status'] ?? 'Active';

        if (!$name) {
            $errors[] = "Row {$rowNum}: Name is required";
            $skipped++; continue;
        }

        $type = trim($type) === '' ? 'Cash' : normalizePayeeType($type);

        $isActive = (strtolower(trim($status)) === 'inactive') ? 0 : 1;

        $existingId = $existingMap[strtolower($name)] ?? null;

        try {
            if ($existingId) {
                // Payee already exists in Invyrr — never overwrite, skip silently
                $skipped++;
                continue;
            }
            $insertStmt->execute([
                ':name'       => $name,
                ':type'       => $type,
                ':bank_name'  => $bankName ?: null,
                ':account_no' => $accountNo ?: null,
                ':ifsc'       => $ifsc ?: null,
                ':upi_id'     => $upiId ?: null,
                ':phone'      => $phone ?: null,
                ':notes'      => $notes ?: null,
                ':is_active'  => $isActive,
            ]);
            $existingMap[strtolower($name)] = (int)$pdo->lastInsertId();
            $inserted++;
        } catch (PDOException $e) {
            $errors[] = "Row {$rowNum}: " . $e->getMessage();
            $skipped++;
        }
    }
    return ['inserted'=>$inserted,'updated'=>$updated,'skipped'=>$skipped,'errors'=>$errors];
}


function importWebsiteOrders(PDO $pdo, array $rows, string $mode = 'upsert'): array {
    $inserted = 0; $updated = 0; $skipped = 0; $errors = [];

    // Ensure tables exist (this importer can run before the page has ever loaded)
    $pdo->exec("CREATE TABLE IF NOT EXISTS website_orders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(50) NOT NULL,
        order_type VARCHAR(50) NOT NULL DEFAULT 'Frontend Order',
        order_date DATE NOT NULL,
        customer_name VARCHAR(200) NOT NULL DEFAULT '',
        city VARCHAR(100) DEFAULT '',
        mobile VARCHAR(30) DEFAULT '',
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        status VARCHAR(30) NOT NULL DEFAULT 'Pending',
        dispatch_status VARCHAR(50) DEFAULT '',
        dispatch_date DATE NULL,
        transport VARCHAR(150) DEFAULT '',
        num_boxes INT DEFAULT 0,
        gift VARCHAR(150) DEFAULT '',
        comments TEXT NULL,
        created_by INT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_order_number (order_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS payees (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        type VARCHAR(50) DEFAULT '',
        account_no VARCHAR(100) DEFAULT '',
        bank_name VARCHAR(150) DEFAULT '',
        ifsc VARCHAR(20) DEFAULT '',
        upi_id VARCHAR(150) DEFAULT '',
        phone VARCHAR(30) DEFAULT '',
        notes VARCHAR(500) DEFAULT '',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_payments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT UNSIGNED DEFAULT NULL,
        customer_name VARCHAR(200) DEFAULT '',
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        payment_date DATE NOT NULL,
        payee_id INT UNSIGNED DEFAULT NULL,
        mode VARCHAR(20) NOT NULL DEFAULT 'account',
        reference_no VARCHAR(100) DEFAULT '',
        note VARCHAR(500) DEFAULT '',
        created_by INT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Pre-load existing orders (order_number -> id) and payees (name -> id)
    $orderMap = [];
    foreach ($pdo->query("SELECT id, order_number FROM website_orders")->fetchAll(PDO::FETCH_ASSOC) as $o) {
        $orderMap[strtolower(trim($o['order_number']))] = $o['id'];
    }
    $payeeMap = [];
    foreach ($pdo->query("SELECT id, name FROM payees")->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $payeeMap[strtolower(trim($p['name']))] = $p['id'];
    }
    $insertPayeeStmt = $pdo->prepare("INSERT INTO payees (name, type, is_active) VALUES (?, 'Person', 1)");

    $insertOrderStmt = $pdo->prepare("INSERT INTO website_orders
        (order_number, order_type, order_date, customer_name, city, mobile, amount, status,
         dispatch_status, dispatch_date, transport, num_boxes, gift, comments, created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $updateOrderStmt = $pdo->prepare("UPDATE website_orders SET
        order_type=?, order_date=?, customer_name=?, city=?, mobile=?, amount=?, status=?,
        dispatch_status=?, dispatch_date=?, transport=?, num_boxes=?, gift=?, comments=? WHERE id=?");
    $insertPaymentStmt = $pdo->prepare("INSERT INTO customer_payments
        (order_id, customer_name, amount, payment_date, payee_id, mode, note, created_by)
        VALUES (?,?,?,?,?,?,?,?)");

    $currentUserId = null;
    try { $currentUserId = currentUser()['id'] ?? null; } catch (Throwable $e) {}

    foreach ($rows as $i => $row) {
        $rowNum = $i + 2;
        $r = [];
        foreach ($row as $k => $v) {
            $r[strtolower(preg_replace('/[^a-z0-9_]/i', '_', trim($k)))] = trim((string)$v);
        }

        $orderNumber  = $r['order_number'] ?? $r['order_number_'] ?? '';
        $orderTypeVal = $r['order_type']   ?: 'Frontend Order';
        $orderDateRaw = $r['order_date']   ?? $r['order_date_']   ?? '';
        $customerName = $r['customer_name'] ?? $r['customer_name_'] ?? '';
        $city         = $r['city']    ?? '';
        $mobile       = $r['mobile_number'] ?? $r['mobile'] ?? '';
        $amountRaw    = $r['amount']  ?? $r['amount_'] ?? '';
        $statusVal    = $r['order_status'] ?? $r['status'] ?? '';
        $amountPaidRaw= $r['amount_paid'] ?? '';
        $paidDateRaw  = $r['paid_date'] ?? '';
        $accountName  = $r['account'] ?? '';
        $cashRaw      = $r['cash'] ?? '';
        $cashPerson   = $r['cash_person'] ?? '';
        $gift         = $r['gift'] ?? '';
        $dispStatus   = $r['dispatch_status'] ?? '';
        $dispDateRaw  = $r['dispatch_date'] ?? '';
        $transport    = $r['transport'] ?? '';
        $numBoxesRaw  = $r['number_of_boxes'] ?? $r['_of_boxes'] ?? $r['boxes'] ?? '';
        $comments     = $r['comments'] ?? '';

        if (!$orderNumber || !$orderDateRaw || $amountRaw === '') {
            $errors[] = "Row {$rowNum}: Order Number, Order Date and Amount are required";
            $skipped++; continue;
        }

        $orderDate = normalizeImportDate($orderDateRaw);
        if (!$orderDate) { $errors[] = "Row {$rowNum}: Invalid Order Date '{$orderDateRaw}'"; $skipped++; continue; }

        $amount = (float)str_replace(',', '', $amountRaw);
        $amountPaid = $amountPaidRaw !== '' ? (float)str_replace(',', '', $amountPaidRaw) : 0.0;
        $cashAmount = $cashRaw !== '' ? (float)str_replace(',', '', $cashRaw) : 0.0;
        $numBoxes = $numBoxesRaw !== '' ? (int)$numBoxesRaw : 0;

        if (!in_array(strtolower($statusVal), ['paid','partial','pending','cancelled'])) {
            // Derive from payment amounts when the sheet's status text doesn't map cleanly
            if ($amountPaid <= 0) $statusVal = 'Pending';
            elseif ($amountPaid >= $amount) $statusVal = 'Paid';
            else $statusVal = 'Partial';
        } else {
            $statusVal = ucfirst(strtolower($statusVal));
        }

        $dispatchDate = $dispDateRaw !== '' ? normalizeImportDate($dispDateRaw) : null;

        $existingId = $orderMap[strtolower(trim($orderNumber))] ?? null;

        try {
            if ($existingId) {
                if ($mode === 'insert') { $skipped++; continue; }
                $updateOrderStmt->execute([
                    $orderTypeVal, $orderDate, $customerName, $city, $mobile, $amount, $statusVal,
                    $dispStatus, $dispatchDate, $transport, $numBoxes, $gift, $comments, $existingId,
                ]);
                $orderId = $existingId;
                // Re-importing an order is treated as a refresh — clear previously
                // imported payments for this order before re-adding from this row,
                // so re-running the same file never duplicates payment history.
                $pdo->prepare("DELETE FROM customer_payments WHERE order_id=? AND note LIKE 'Imported%'")->execute([$orderId]);
                $updated++;
            } else {
                $insertOrderStmt->execute([
                    $orderNumber, $orderTypeVal, $orderDate, $customerName, $city, $mobile, $amount, $statusVal,
                    $dispStatus, $dispatchDate, $transport, $numBoxes, $gift, $comments, $currentUserId,
                ]);
                $orderId = (int)$pdo->lastInsertId();
                $orderMap[strtolower(trim($orderNumber))] = $orderId;
                $inserted++;
            }

            // Resolve/auto-create payees and record payment rows
            $paidDate = $paidDateRaw !== '' ? normalizeImportDate($paidDateRaw, $orderDate) : $orderDate;

            // Account portion
            $accountAmount = $amountPaid - $cashAmount;
            if ($accountAmount > 0 && ($accountName !== '' || $amountPaid > 0)) {
                $payeeId = null;
                if ($accountName !== '') {
                    $key = strtolower($accountName);
                    if (isset($payeeMap[$key])) { $payeeId = $payeeMap[$key]; }
                    else {
                        $insertPayeeStmt->execute([$accountName]);
                        $payeeId = (int)$pdo->lastInsertId();
                        $payeeMap[$key] = $payeeId;
                    }
                }
                $insertPaymentStmt->execute([
                    $orderId, $customerName, round($accountAmount, 2), $paidDate, $payeeId, 'account',
                    'Imported from order sheet', $currentUserId,
                ]);
            }

            // Cash portion
            if ($cashAmount > 0) {
                $payeeId = null;
                if ($cashPerson !== '') {
                    $key = strtolower($cashPerson);
                    if (isset($payeeMap[$key])) { $payeeId = $payeeMap[$key]; }
                    else {
                        $insertPayeeStmt->execute([$cashPerson]);
                        $payeeId = (int)$pdo->lastInsertId();
                        $payeeMap[$key] = $payeeId;
                    }
                }
                $insertPaymentStmt->execute([
                    $orderId, $customerName, round($cashAmount, 2), $paidDate, $payeeId, 'cash',
                    'Imported from order sheet' . ($cashPerson !== '' ? " (collected by {$cashPerson})" : ''), $currentUserId,
                ]);
            }
        } catch (PDOException $e) {
            $errors[] = "Row {$rowNum}: " . $e->getMessage();
            $skipped++;
        }
    }

    return ['inserted'=>$inserted,'updated'=>$updated,'skipped'=>$skipped,'errors'=>$errors];
}

// Flexible date parser shared by importers that accept multiple date formats.
// If $yearHint (Y-m-d) is given and the raw date lacks a 4-digit year, the
// hint's year is borrowed (handles sheets like "Paid Date: 08-10" with no year).
function normalizeImportDate(string $raw, ?string $yearHint = null): ?string {
    $raw = trim($raw);
    if ($raw === '') return null;

    if (!preg_match('/\d{4}/', $raw) && $yearHint) {
        $year = substr($yearHint, 0, 4);
        $raw = rtrim($raw, '-/ ') . '-' . $year;
    }

    $formats = ['Y-m-d','d-m-Y','d/m/Y','m-d-Y','m/d/Y','d-m','d/m'];
    foreach ($formats as $fmt) {
        $d = DateTime::createFromFormat($fmt, $raw);
        if ($d && $d->format($fmt) === $raw) return $d->format('Y-m-d');
    }
    // Last resort — let PHP's parser take a shot at it
    $ts = strtotime($raw);
    if ($ts !== false) return date('Y-m-d', $ts);
    return null;
}
