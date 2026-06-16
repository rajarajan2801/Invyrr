<?php
/**
 * Invyrr Backup API
 *
 * GET  ?action=sql              → stream .sql dump as download
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

// ── SQL DUMP ─────────────────────────────────────────────
if ($action === 'sql') {
    $filename = 'invyrr_' . date('Y-m-d_H-i-s') . '.sql';
    $path     = $dir . $filename;

    $sql  = "-- Invyrr SQL Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $dbname = _env("MYSQLDATABASE", _env("DB_NAME", "invyrr"));
    $sql .= "-- Database: " . $dbname . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // DROP + CREATE
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        $sql   .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql   .= $create[1] . ";\n\n";

        // INSERT rows in batches
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_NUM);
        if ($rows) {
            $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
            $colList = '`' . implode('`, `', $cols) . '`';
            $sql .= "INSERT INTO `$table` ($colList) VALUES\n";
            $vals = [];
            foreach ($rows as $row) {
                $escaped = array_map(function($v){ return $v === null ? 'NULL' : "'" . addslashes($v) . "'"; }, $row);
                $vals[] = '(' . implode(', ', $escaped) . ')';
            }
            $sql .= implode(",\n", $vals) . ";\n\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    file_put_contents($path, $sql);

    // Log to backup_log table if it exists, else just write a meta file
    $metaFile = $dir . $filename . '.meta';
    $user = currentUser();
    file_put_contents($metaFile, json_encode([
        'type'       => 'sql',
        'filename'   => $filename,
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $user['name'] ?? 'unknown',
        'size'       => filesize($path),
    ]));

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: no-cache');
    readfile($path);
    exit;
}

// ── HISTORY ──────────────────────────────────────────────
if ($action === 'history') {
    header('Content-Type: application/json');
    $metas = glob($dir . '*.meta');
    $list  = [];
    foreach ((array)$metas as $m) {
        $data = json_decode(file_get_contents($m), true);
        if (!$data) continue;
        $size = $data['size'] ?? 0;
        $data['size_human'] = $size > 1048576
            ? round($size / 1048576, 1) . ' MB'
            : round($size / 1024, 1) . ' KB';
        $list[] = $data;
    }
    usort($list, function($a, $b){ return strcmp($b['created_at'], $a['created_at']); });
    jsonList(array_slice($list, 0, 20));
}

// ── DOWNLOAD ─────────────────────────────────────────────
if ($action === 'download') {
    $file = basename($_GET['file'] ?? '');
    $path = $dir . $file;
    if (!$file || !file_exists($path) || strpos($file, '..') !== false) {
        jsonError('File not found', 404);
    }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

// ── DELETE ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || $action === 'delete') {
    requireRole('admin');
    $file = basename($_GET['file'] ?? '');
    if (!$file || strpos($file, '..') !== false) jsonError('Invalid file');
    @unlink($dir . $file);
    @unlink($dir . $file . '.meta');
    jsonOk(null, 'Backup deleted');
}

// ── RESTORE ──────────────────────────────────────────────
if ($action === 'restore' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('admin');

    if (empty($_FILES['sql_file'])) jsonError('No file uploaded');
    $file = $_FILES['sql_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) jsonError('Upload error');
    if (substr(strtolower($file['name']), -4) !== '.sql') jsonError('Only .sql files allowed');
    if ($file['size'] > 50 * 1024 * 1024) jsonError('File too large (max 50 MB)');

    $sql = file_get_contents($file['tmp_name']);
    if (!$sql) jsonError('Could not read SQL file');

    // Execute statements
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        // Split on semicolons but preserve those inside strings
        $statements = preg_split('/;\s*\n/', $sql);
        $count = 0;
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || strncmp($stmt, '--', 2) === 0) continue;
            $pdo->exec($stmt);
            $count++;
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        auditLog($pdo, 'restore_backup', 'database', 0, $file['name']);
        jsonOk(['statements' => $count], "Restore complete — $count statements executed");
    } catch (PDOException $e) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        jsonError('Restore failed: ' . $e->getMessage());
    }
}

jsonError('Unknown action');
