<?php
// ── Invyrr Automated Database Backup ─────────────────────
// Called by Railway cron job — exports DB as SQL and emails it.
// Set these env vars in Railway dashboard:
//   BACKUP_EMAIL   → where to send the backup
//   BACKUP_SECRET  → a random string to prevent unauthorized access
//   SMTP_HOST      → smtp.gmail.com
//   SMTP_PORT      → 587
//   SMTP_USER      → your Gmail address
//   SMTP_PASS      → your Gmail App Password (not your real password)

require_once __DIR__ . '/includes/db.php';

// ── Security: only run via cron secret or CLI ─────────────
$secret = _env('BACKUP_SECRET', '');
$provided = $_GET['secret'] ?? '';
$isCLI = (php_sapi_name() === 'cli');

if (!$isCLI && ($secret === '' || $provided !== $secret)) {
    http_response_code(403);
    die('Forbidden');
}

// ── Generate SQL dump ─────────────────────────────────────
$host   = $_DB_HOST;
$port   = $_DB_PORT;
$dbname = $_DB_NAME;
$user   = $_DB_USER;
$pass   = $_DB_PASS;

$pdo = getDB();
$sql = "-- Invyrr Database Backup\n";
$sql .= "-- Generated: " . date('Y-m-d H:i:s') . " UTC\n";
$sql .= "-- Database: {$dbname}\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

// Get all tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    // Table structure
    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
    $sql .= "DROP TABLE IF EXISTS `$table`;\n";
    $sql .= $create['Create Table'] . ";\n\n";

    // Table data
    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) continue;

    $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
    $sql .= "INSERT INTO `$table` ({$cols}) VALUES\n";

    $chunks = array_chunk($rows, 100);
    foreach ($chunks as $ci => $chunk) {
        $values = [];
        foreach ($chunk as $row) {
            $escaped = array_map(function($v) use ($pdo) {
                if ($v === null) return 'NULL';
                return $pdo->quote((string)$v);
            }, array_values($row));
            $values[] = '(' . implode(', ', $escaped) . ')';
        }
        $isLast = ($ci === count($chunks) - 1);
        $sql .= implode(",\n", $values) . ($isLast ? ";\n\n" : ",\n");
    }
}

$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

// ── Send via email ─────────────────────────────────────────
$toEmail   = _env('BACKUP_EMAIL', '');
$smtpHost  = _env('SMTP_HOST', 'smtp.gmail.com');
$smtpPort  = (int)_env('SMTP_PORT', '587');
$smtpUser  = _env('SMTP_USER', '');
$smtpPass  = _env('SMTP_PASS', '');

if (empty($toEmail) || empty($smtpUser) || empty($smtpPass)) {
    // No email configured — just output the SQL
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="invyrr_backup_' . date('Y-m-d') . '.sql"');
    echo $sql;
    exit;
}

// Encode attachment
$filename   = 'invyrr_backup_' . date('Y-m-d_His') . '.sql';
$encoded    = base64_encode($sql);
$boundary   = md5(time());
$subject    = 'Invyrr DB Backup — ' . date('d M Y');

$headers  = "From: Invyrr Backup <{$smtpUser}>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

$body  = "--{$boundary}\r\n";
$body .= "Content-Type: text/plain; charset=utf-8\r\n\r\n";
$body .= "Invyrr automated backup — " . date('d M Y H:i') . " UTC\n";
$body .= "Tables: " . count($tables) . "\n";
$body .= "Size: " . round(strlen($sql)/1024, 1) . " KB\n\r\n";
$body .= "--{$boundary}\r\n";
$body .= "Content-Type: application/sql; name=\"{$filename}\"\r\n";
$body .= "Content-Transfer-Encoding: base64\r\n";
$body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
$body .= chunk_split($encoded) . "\r\n";
$body .= "--{$boundary}--";

// Send via SMTP using stream
$result = sendViaSMTP($smtpHost, $smtpPort, $smtpUser, $smtpPass, $toEmail, $subject, $headers, $body);

echo $result ? "✅ Backup sent to {$toEmail}" : "❌ Email failed — check SMTP settings";

function sendViaSMTP($host, $port, $user, $pass, $to, $subject, $headers, $body): bool {
    $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $sock = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$sock) return false;

    $read = function() use ($sock) { return fgets($sock, 512); };
    $send = function($cmd) use ($sock) { fwrite($sock, $cmd . "\r\n"); };

    $read(); // greeting
    $send("EHLO invyrr.app"); $read(); $read(); $read(); $read(); $read(); $read(); $read(); $read();
    $send("STARTTLS"); $read();
    stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    $send("EHLO invyrr.app"); $read(); $read(); $read(); $read(); $read(); $read(); $read(); $read();
    $send("AUTH LOGIN"); $read();
    $send(base64_encode($user)); $read();
    $send(base64_encode($pass)); $r = $read();
    if (strpos($r, '235') === false) { fclose($sock); return false; }
    $send("MAIL FROM:<{$user}>"); $read();
    $send("RCPT TO:<{$to}>"); $read();
    $send("DATA"); $read();
    $send("Subject: {$subject}\r\n{$headers}\r\n{$body}\r\n."); $read();
    $send("QUIT"); fclose($sock);
    return true;
}
