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
                ['Min Stock','Unit','Description']
            ),
            'example'  => array_merge(
                ['SPK-001','Sparklers 10cm','Star Brand','Sparklers','Raj Crackers Co.','16.00','12.00','13.50','25.00','20.00','12','6'],
                $locExample,
                ['20','Box','Standard sparklers']
            ),
            'notes'    => [
                '# NOTES: Fields marked * are required.',
                $locNote,
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
        'expenses' => [
                'filename' => 'import_expenses_template.csv',
                'headers'  => ['Date*','Category*','Amount*','Vendor Name','Paid By (Payee Name)*','Reference No','Notes'],
                'example'  => ['2026-06-01','Transport','500','Raj Crackers','Cash','INV-001','Loading charges'],
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
    ];

    $type = $_GET['template'];
    if (!isset($templates[$type])) { http_response_code(404); exit('Unknown template type'); }
    $tpl = $templates[$type];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $tpl['filename'] . '"');
    header('Cache-Control: no-cache');

    $out = fopen('php://output', 'w');
    foreach ($tpl['notes'] as $note) fputcsv($out, [$note]);
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

if (!in_array($type, ['products','vendors','stock_in','stock_out','expenses'])) {
    jsonError('Invalid type. Must be: products, vendors, stock_in, stock_out');
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

    $insertStmt = $pdo->prepare('
        INSERT INTO products (name, sku, item_code, brand, category, vendor_id, pending_vendor_name, cost, list_price, sell, wholesale_price, stock, min_stock, unit, description, case_content, box_content, landing_cost)
        VALUES (:name,:sku,:item_code,:brand,:category,:vendor_id,:pending_vendor_name,:cost,:list_price,:sell,:wholesale_price,:stock,:min_stock,:unit,:description,:case_content,:box_content,:landing_cost)');

    // Upsert also updates stock and min_stock on the product row
    $updateStmt = $pdo->prepare('
        UPDATE products SET name=:name, sku=:sku, item_code=:item_code, brand=:brand, category=:category, vendor_id=:vendor_id, pending_vendor_name=:pending_vendor_name,
            cost=:cost, list_price=:list_price, sell=:sell, wholesale_price=:wholesale_price, stock=:stock, min_stock=:min_stock, unit=:unit, description=:description, case_content=:case_content, box_content=:box_content, landing_cost=:landing_cost
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

        $params = [
            ':name'=>$name, ':sku'=>$sku, ':item_code'=>$itemCode, ':brand'=>$brand, ':category'=>$category,
            ':vendor_id'=>$vendorId, ':pending_vendor_name'=>$pendingVendorName,
            ':cost'=>(float)$cost, ':sell'=>(float)$sell, ':stock'=>$stock, ':min_stock'=>$minStock,
            ':unit'=>$unit, ':description'=>$desc, ':case_content'=>$caseQty, ':box_content'=>$boxQty, ':landing_cost'=>$landCost, ':wholesale_price'=>$wholeSale, ':list_price'=>$listPrice
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
function importExpenses(PDO $pdo, array $rows): array {
    requireRole('admin','manager');
    $inserted = 0; $skipped = 0; $errors = [];

    // Pre-load payees (name → id, case-insensitive)
    $payeeMap = [];
    foreach ($pdo->query("SELECT id, name FROM payees")->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $payeeMap[strtolower(trim($p['name']))] = $p['id'];
    }
    // Pre-load vendors (name → id)
    $vendorMap = [];
    foreach ($pdo->query("SELECT id, name FROM vendors")->fetchAll(PDO::FETCH_ASSOC) as $v) {
        $vendorMap[strtolower(trim($v['name']))] = $v['id'];
    }
    // Load existing expense categories
    $catSet = [];
    foreach ($pdo->query("SELECT DISTINCT category FROM expenses")->fetchAll(PDO::FETCH_COLUMN) as $c) {
        $catSet[strtolower(trim($c))] = trim($c);
    }

    $stmt = $pdo->prepare("INSERT INTO expenses
        (expense_date, category, amount, vendor_id, payee_id, reference_no, notes, created_by)
        VALUES (:date, :category, :amount, :vendor_id, :payee_id, :ref, :notes, :created_by)");

    foreach ($rows as $i => $row) {
        $rowNum = $i + 2;
        // Normalise keys
        $r = [];
        foreach ($row as $k => $v) {
            $r[strtolower(preg_replace('/[^a-z0-9_]/i','_', trim($k)))] = trim((string)$v);
        }

        $date     = $r['date']     ?? $r['date_']     ?? '';
        $category = $r['category'] ?? $r['category_'] ?? '';
        $amount   = $r['amount']   ?? $r['amount_']   ?? '';
        $payeeName= $r['paid_by__payee_name_'] ?? $r['paid_by'] ?? $r['payee'] ?? $r['payee_name'] ?? '';
        $vendorName=$r['vendor_name'] ?? $r['vendor'] ?? '';
        $ref      = $r['reference_no'] ?? $r['ref'] ?? '';
        $notes    = $r['notes'] ?? '';

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

        // Resolve payee
        $payeeId = null;
        if ($payeeName !== '') {
            $payeeId = $payeeMap[strtolower($payeeName)] ?? null;
            if (!$payeeId) {
                // Auto-create payee as Cash type
                $pdo->prepare("INSERT INTO payees (name, type, is_active) VALUES (?, 'Cash', 1)")->execute([$payeeName]);
                $payeeId = $pdo->lastInsertId();
                $payeeMap[strtolower($payeeName)] = $payeeId;
            }
        }
        // Resolve vendor
        $vendorId = null;
        if ($vendorName !== '') {
            $vendorId = $vendorMap[strtolower($vendorName)] ?? null;
        }
        // Auto-create category if new
        if (!isset($catSet[strtolower($category)])) {
            $catSet[strtolower($category)] = $category;
        }

        $u = currentUser();
        $stmt->execute([
            ':date'       => $date,
            ':category'   => $category,
            ':amount'     => $amount,
            ':vendor_id'  => $vendorId,
            ':payee_id'   => $payeeId,
            ':ref'        => $ref,
            ':notes'      => $notes,
            ':created_by' => $u['id'] ?? null,
        ]);
        $inserted++;
    }
    return ['inserted'=>$inserted,'updated'=>0,'skipped'=>$skipped,'errors'=>$errors];
}

