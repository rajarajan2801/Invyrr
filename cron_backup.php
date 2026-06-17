<?php
// ── Invyrr Automated DB Backup → Google Drive ─────────────
// Run by Railway cron job — no browser needed.
// Uses Google Service Account (server-to-server auth).
//
// Required Railway env vars:
//   GOOGLE_SERVICE_ACCOUNT_JSON  → full contents of service account .json key file
//   GOOGLE_DRIVE_FOLDER_ID       → ID of the shared "Invyrr_db_backup" Drive folder
//   BACKUP_SECRET                → random string to prevent unauthorized web access
//
// Railway cron command: php cron_backup.php
// Schedule: 0 2 * * 0   (every Sunday 2 AM UTC)

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
}

$log = function(string $msg) {
    $ts = date('Y-m-d H:i:s');
    echo "[{$ts}] {$msg}\n";
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

// ── Step 2: Get Google OAuth token via Service Account ────
$saJson   = _env('GOOGLE_SERVICE_ACCOUNT_JSON', '');
$folderId = _env('GOOGLE_DRIVE_FOLDER_ID', '');

if (!$saJson || !$folderId) {
    $log('ERROR: GOOGLE_SERVICE_ACCOUNT_JSON or GOOGLE_DRIVE_FOLDER_ID not set in Railway env vars');
    exit(1);
}

$sa = json_decode($saJson, true);
if (!$sa || empty($sa['private_key'])) {
    $log('ERROR: Invalid service account JSON');
    exit(1);
}

// Build JWT
$now    = time();
$header = base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
$claims = base64url(json_encode([
    'iss'   => $sa['client_email'],
    'scope' => 'https://www.googleapis.com/auth/drive.file',
    'aud'   => 'https://oauth2.googleapis.com/token',
    'iat'   => $now,
    'exp'   => $now + 3600,
]));
$toSign = "{$header}.{$claims}";

if (!openssl_sign($toSign, $signature, $sa['private_key'], 'SHA256')) {
    $log('ERROR: Failed to sign JWT — check private key in service account JSON');
    exit(1);
}
$jwt = "{$toSign}." . base64url($signature);

// Exchange JWT for access token
$tokenResp = httpPost('https://oauth2.googleapis.com/token', http_build_query([
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion'  => $jwt,
]));
$tokenData = json_decode($tokenResp, true);

if (empty($tokenData['access_token'])) {
    $log('ERROR: Failed to get access token: ' . $tokenResp);
    exit(1);
}
$accessToken = $tokenData['access_token'];
$log('Google auth OK');

// ── Step 3: Upload to Google Drive ───────────────────────
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

$uploadResp = httpPost(
    'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name',
    $body,
    [
        "Authorization: Bearer {$accessToken}",
        "Content-Type: multipart/related; boundary={$boundary}",
    ]
);
$uploadData = json_decode($uploadResp, true);

if (!empty($uploadData['id'])) {
    $log("✅ Backup uploaded: {$uploadData['name']} (ID: {$uploadData['id']})");
} else {
    $log('ERROR uploading to Drive: ' . $uploadResp);
    exit(1);
}

// ── Helpers ───────────────────────────────────────────────
function base64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function httpPost(string $url, string $body, array $headers = []): string {
    $defaultHeaders = ['Content-Type: application/x-www-form-urlencoded'];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => array_merge($defaultHeaders, $headers),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $resp = curl_exec($ch);
    if (curl_errno($ch)) throw new Exception('cURL error: ' . curl_error($ch));
    curl_close($ch);
    return $resp;
}
