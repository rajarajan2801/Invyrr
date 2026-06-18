<?php
// ── Invyrr Automated DB Backup → Google Drive ─────────────
// Uses OAuth Refresh Token (personal Google account).
// No Shared Drive needed — uploads to your personal Drive folder.
//
// Required Railway env vars:
//   GOOGLE_CLIENT_ID      → OAuth Client ID from Google Cloud Console
//   GOOGLE_CLIENT_SECRET  → OAuth Client Secret
//   GOOGLE_REFRESH_TOKEN  → Permanent refresh token from OAuth Playground
//   GOOGLE_DRIVE_FOLDER_ID → Folder ID of Invyrr_db_backup in your Drive
//   BACKUP_SECRET         → Random string to protect the URL endpoint
//
// Cron URL: https://invyrr.up.railway.app/cron_backup.php?secret=YOUR_SECRET

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
    echo "[" . date('Y-m-d H:i:s') . "] {$msg}\n";
    flush();
};

$log('Starting Invyrr DB backup...');

// ── Step 1: Generate SQL dump ─────────────────────────────
try {
    $pdo    = getDB();
    $dbname = _env('MYSQLDATABASE', _env('MYSQL_DATABASE', _env('DB_NAME', 'invyrr')));
    $sql    = "-- Invyrr SQL Backup\n";
    $sql   .= "-- Generated: " . date('Y-m-d H:i:s') . " UTC\n";
    $sql   .= "-- Database: {$dbname}\n\n";
    $sql   .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $log('Tables found: ' . count($tables));

    foreach ($tables as $table) {
        $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        $sql   .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql   .= $create[1] . ";\n\n";
        $rows   = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_NUM);
        if (!$rows) continue;
        $cols    = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
        $colList = '`' . implode('`, `', $cols) . '`';
        $sql    .= "INSERT INTO `{$table}` ({$colList}) VALUES\n";
        $vals    = [];
        foreach ($rows as $row) {
            $escaped = array_map(function ($v) {
                return $v === null ? 'NULL' : "'" . addslashes((string)$v) . "'";
            }, $row);
            $vals[] = '(' . implode(', ', $escaped) . ')';
        }
        $sql .= implode(",\n", $vals) . ";\n\n";
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $log('SQL dump generated: ' . round(strlen($sql) / 1024, 1) . ' KB');
} catch (Exception $e) {
    $log('ERROR generating dump: ' . $e->getMessage());
    exit(1);
}

// ── Step 2: Get Access Token via Refresh Token ────────────
$clientId     = _env('GOOGLE_CLIENT_ID', '');
$clientSecret = _env('GOOGLE_CLIENT_SECRET', '');
$refreshToken = _env('GOOGLE_REFRESH_TOKEN', '');
$folderId     = _env('GOOGLE_DRIVE_FOLDER_ID', '');

if (!$clientId || !$clientSecret || !$refreshToken || !$folderId) {
    $log('ERROR: Missing env vars. Need: GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REFRESH_TOKEN, GOOGLE_DRIVE_FOLDER_ID');
    exit(1);
}

$tokenResp = curlPost('https://oauth2.googleapis.com/token', http_build_query([
    'client_id'     => $clientId,
    'client_secret' => $clientSecret,
    'refresh_token' => $refreshToken,
    'grant_type'    => 'refresh_token',
]));

$tokenData = json_decode($tokenResp, true);
if (empty($tokenData['access_token'])) {
    $log('ERROR: Could not get access token. Response: ' . $tokenResp);
    exit(1);
}
$accessToken = $tokenData['access_token'];
$log('Google auth OK (personal account)');

// ── Step 3: Upload SQL to Google Drive ────────────────────
$filename = 'Invyrr_Backup_' . date('Y-m-d_H-i-s') . '.sql';
$meta     = json_encode(['name' => $filename, 'parents' => [$folderId]]);
$boundary = '----InvyrrBackup' . uniqid();
$body     = "--{$boundary}\r\n"
          . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
          . $meta . "\r\n"
          . "--{$boundary}\r\n"
          . "Content-Type: text/plain\r\n\r\n"
          . $sql . "\r\n"
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
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 60,
]);
$uploadResp = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if (curl_errno($ch)) {
    $log('ERROR: cURL failed: ' . curl_error($ch));
    curl_close($ch); exit(1);
}
curl_close($ch);

$uploadData = json_decode($uploadResp, true);
if ($httpCode === 200 && !empty($uploadData['id'])) {
    $log("✅ Backup complete: {$uploadData['name']}");
    $log("   https://drive.google.com/file/d/{$uploadData['id']}/view");
} else {
    $log("ERROR uploading (HTTP {$httpCode}): " . $uploadResp);
    exit(1);
}

// ── Helper ────────────────────────────────────────────────
function curlPost(string $url, string $body, array $headers = []): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/x-www-form-urlencoded'], $headers),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $resp = curl_exec($ch);
    if (curl_errno($ch)) throw new Exception('cURL error: ' . curl_error($ch));
    curl_close($ch);
    return $resp;
}
