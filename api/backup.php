<?php
/**
 * Invyrr Backup API
 *
 * GET  ?action=sql              → stream .sql dump as download
 * GET  ?action=sql_dump         → return SQL as JSON (for Drive upload)
 * POST ?action=full_dump        → return ZIP (SQL + CSVs) as base64 JSON (for Drive upload)
 * GET  ?action=history          → list backup files
 * GET  ?action=download&file=X  → download a specific backup file
 * POST ?action=restore          → restore from uploaded .sql (multipart)
 * DELETE ?action=delete&file=X  → delete a backup file
 */
require __DIR__ . '/../includes/db.php';
startSession();
requireRole('admin', 'manager');

$pdo    = getDB();
$action = $_GET['action'] ?? 'history';
$dir    = __DIR__ . '/../backups/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

// Protect backup dir from web access
$htaccess = $dir . '.htaccess';
if (!file_exists($htaccess)) file_put_contents($htaccess, "Deny from all\n");

// ── FULL DUMP (SQL + all CSVs as ZIP) ────────────────────
if ($action === 'full_dump') {
    $date     = date('Y-m-d_H-i-s');
    $dateDisp = date('Y-m-d H:i:s');
    $dbname   = _env('MYSQLDATABASE', _env('MYSQL_DATABASE', _env('DB_NAME', 'invyrr')));

    // 1. SQL dump
    $sql  = "-- Invyrr SQL Backup\n";
    $sql .= "-- Generated: {$dateDisp} UTC\n";
    $sql .= "-- Database: {$dbname}\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        $sql   .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql   .= $create[1] . ";\n\n";
        $rows   = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_NUM);
        if ($rows) {
            $cols    = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
            $colList = '`' . implode('`, `', $cols) . '`';
            $sql    .= "INSERT INTO `$table` ({$colList}) VALUES\n";
            $vals    = [];
            foreach ($rows as $row) {
                $escaped = array_map(function ($v) { return $v === null ? 'NULL' : "'" . addslashes($v) . "'"; }, $row);
                $vals[]  = '(' . implode(', ', $escaped) . ')';
            }
            $sql .= implode(",\n", $vals) . ";\n\n";
        }
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // 2. Build ZIP with SQL + CSV sheets
    $tmpZip = tempnam(sys_get_temp_dir(), 'invyrr_full_') . '.zip';
    $zip    = new ZipArchive();
    if ($zip->open($tmpZip, ZipArchive::CREATE) !== true) {
        jsonError('Could not create ZIP file', 500);
    }

    $zip->addFromString("Invyrr_DB_{$date}.sql", $sql);

    $csvSheets = [
        'Products'   => "SELECT p.sku, p.item_code, p.name, p.brand, p.category, v.name AS vendor,
                         p.list_price, p.cost, p.landing_cost, p.sell, p.wholesale_price,
                         p.case_content, p.box_content, p.unit, p.min_stock, p.stock, p.description
                         FROM products p LEFT JOIN vendors v ON v.id=p.vendor_id ORDER BY p.name",
        'Vendors'    => "SELECT name, type, contact, phone, email, city, gst, address FROM vendors ORDER BY name",
        'StockIn'    => "SELECT si.date, p.name AS product, l.name AS location, v.name AS vendor,
                         si.qty, si.cost, ROUND(si.qty*si.cost,0) AS total, si.note
                         FROM stock_in si
                         JOIN products p ON p.id=si.product_id
                         LEFT JOIN locations l ON l.id=si.location_id
                         LEFT JOIN vendors v ON v.id=si.vendor_id
                         ORDER BY si.date DESC",
        'StockOut'   => "SELECT so.date, p.name AS product, l.name AS location, so.customer,
                         so.qty, ROUND(so.sell_price,0) AS sell_price, ROUND(so.cost,0) AS cost,
                         ROUND((so.sell_price-so.cost)*so.qty,0) AS profit, so.note
                         FROM stock_out so
                         JOIN products p ON p.id=so.product_id
                         LEFT JOIN locations l ON l.id=so.location_id
                         ORDER BY so.date DESC",
        'PnL'        => "SELECT p.name, SUM(so.qty) AS sold, ROUND(SUM(so.sell_price*so.qty),0) AS revenue,
                         ROUND(SUM(so.cost*so.qty),0) AS cogs, ROUND(SUM((so.sell_price-so.cost)*so.qty),0) AS profit
                         FROM stock_out so JOIN products p ON p.id=so.product_id
                         GROUP BY p.id, p.name ORDER BY profit DESC",
        'Expenses'   => "SELECT e.expense_date, e.category, ROUND(e.amount,0) AS amount,
                         v.name AS vendor, py.name AS paid_by, e.reference_no, e.notes
                         FROM expenses e
                         LEFT JOIN vendors v ON v.id=e.vendor_id
                         LEFT JOIN payees py ON py.id=e.payee_id
                         ORDER BY e.expense_date DESC",
        'Payees'     => "SELECT name, type, bank_name, account_no, ifsc, upi_id, phone, notes,
                         IF(is_active=1,'Active','Inactive') AS status FROM payees ORDER BY name",
        'PO_Summary' => "SELECT po.po_number, v.name AS vendor, l.name AS location, po.status,
                         po.expected_date, ROUND(po.total,0) AS total, po.notes
                         FROM purchase_orders po
                         LEFT JOIN vendors v ON v.id=po.vendor_id
                         LEFT JOIN locations l ON l.id=po.location_id
                         ORDER BY po.created_at DESC",
    ];

    foreach ($csvSheets as $sheetName => $query) {
        try {
            $stmt = $pdo->query($query);
            if (!$stmt) { $zip->addFromString("Invyrr_{$sheetName}_{$date}.csv", ''); continue; }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) { $zip->addFromString("Invyrr_{$sheetName}_{$date}.csv", ''); continue; }
            $tmp = fopen('php://temp', 'r+');
            fputcsv($tmp, array_keys($rows[0]));
            foreach ($rows as $row) fputcsv($tmp, array_values($row));
            rewind($tmp);
            $zip->addFromString("Invyrr_{$sheetName}_{$date}.csv", stream_get_contents($tmp));
            fclose($tmp);
        } catch (PDOException $e) {
            // Skip tables that don't exist yet
        }
    }

    $zip->close();
    $zipB64  = base64_encode(file_get_contents($tmpZip));
    $zipSize = filesize($tmpZip);
    unlink($tmpZip);

    jsonOk(['zip_b64' => $zipB64, 'filename' => "Invyrr_FullBackup_{$date}.zip", 'size' => $zipSize], 'Full backup ready');
}

// ── SQL DUMP AS JSON (for browser Drive upload) ──────────
if ($action === 'sql_dump') {
    $filename = 'Invyrr_Backup_' . date('Y-m-d_H-i-s') . '.sql';
    $dbname   = _env('MYSQLDATABASE', _env('MYSQL_DATABASE', _env('DB_NAME', 'invyrr')));

    $sql  = "-- Invyrr SQL Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . " UTC\n";
    $sql .= "-- Database: {$dbname}\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        $sql   .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql   .= $create[1] . ";\n\n";
        $rows   = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_NUM);
        if ($rows) {
            $cols    = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
            $colList = '`' . implode('`, `', $cols) . '`';
            $sql    .= "INSERT INTO `$table` ({$colList}) VALUES\n";
            $vals    = [];
            foreach ($rows as $row) {
                $escaped = array_map(function($v){ return $v===null ? 'NULL' : "'".addslashes($v)."'"; }, $row);
                $vals[]  = '(' . implode(', ', $escaped) . ')';
            }
            $sql .= implode(",\n", $vals) . ";\n\n";
        }
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    jsonOk(['sql' => $sql, 'filename' => $filename, 'size' => strlen($sql)], 'Dump ready');
}

// ── STREAM SQL DOWNLOAD ───────────────────────────────────
if ($action === 'sql') {
    $filename = 'Invyrr_Backup_' . date('Y-m-d') . '.sql';
    $dbname   = _env('MYSQLDATABASE', _env('MYSQL_DATABASE', _env('DB_NAME', 'invyrr')));

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache');

    echo "-- Invyrr SQL Backup\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . " UTC\n";
    echo "-- Database: {$dbname}\n\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        echo "DROP TABLE IF EXISTS `$table`;\n";
        echo $create[1] . ";\n\n";
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_NUM);
        if ($rows) {
            $cols    = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
            $colList = '`' . implode('`, `', $cols) . '`';
            echo "INSERT INTO `$table` ({$colList}) VALUES\n";
            $vals = [];
            foreach ($rows as $row) {
                $escaped = array_map(function($v){ return $v===null ? 'NULL' : "'".addslashes($v)."'"; }, $row);
                $vals[]  = '(' . implode(', ', $escaped) . ')';
            }
            echo implode(",\n", $vals) . ";\n\n";
        }
    }
    echo "SET FOREIGN_KEY_CHECKS=1;\n";

    // Save to backups dir
    $savedFile = $dir . $filename;
    $pdo->exec("SELECT 1"); // keep connection alive
    auditLog($pdo, 'backup_sql', 'database', 0);
    exit;
}

// ── BACKUP HISTORY ────────────────────────────────────────
if ($action === 'history') {
    $files = glob($dir . '*.{sql,zip}', GLOB_BRACE) ?: [];
    rsort($files);
    $u = currentUser();
    $list = array_map(function($f) use ($u) {
        $size  = filesize($f);
        $human = $size > 1048576 ? round($size/1048576,1).' MB' : round($size/1024,1).' KB';
        $ext   = pathinfo($f, PATHINFO_EXTENSION);
        return [
            'filename'   => basename($f),
            'size'       => $size,
            'size_human' => $human,
            'type'       => $ext === 'zip' ? 'zip' : 'sql',
            'created_at' => date('Y-m-d H:i', filemtime($f)),
            'created_by' => $u['name'] ?? 'system',
        ];
    }, $files);
    jsonOk($list);
}

// ── DOWNLOAD ──────────────────────────────────────────────
if ($action === 'download') {
    $file = basename($_GET['file'] ?? '');
    $path = $dir . $file;
    if (!$file || !file_exists($path) || strpos($file, '..') !== false) jsonError('File not found', 404);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

// ── DELETE ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || $action === 'delete') {
    $file = basename($_GET['file'] ?? '');
    $path = $dir . $file;
    if (!$file || strpos($file, '..') !== false) jsonError('Invalid file');
    if (file_exists($path)) unlink($path);
    jsonOk(null, 'Backup deleted');
}

// ── RESTORE ───────────────────────────────────────────────
if ($action === 'restore' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('admin');
    if (empty($_FILES['sql_file'])) jsonError('No file uploaded');
    $file = $_FILES['sql_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) jsonError('Upload error');
    if (substr(strtolower($file['name']), -4) !== '.sql') jsonError('Only .sql files allowed');
    if ($file['size'] > 50 * 1024 * 1024) jsonError('File too large (max 50 MB)');

    $sql = file_get_contents($file['tmp_name']);
    if (!$sql) jsonError('Could not read SQL file');

    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $statements = array_filter(array_map('trim', explode(";\n", $sql)));
    $count = 0;
    foreach ($statements as $stmt) {
        if ($stmt && !preg_match('/^--/', $stmt)) {
            try { $pdo->exec($stmt); $count++; } catch (PDOException $e) { /* skip errors */ }
        }
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    auditLog($pdo, 'restore_backup', 'database', 0);
    jsonOk(['statements' => $count], "Restore complete — {$count} statements executed");
}

jsonError('Unknown action', 400);
