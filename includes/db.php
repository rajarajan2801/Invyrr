<?php
// Server timezone — Railway containers default to UTC, but this shop and
// every date the app writes with PHP's date()/CURDATE()-adjacent logic
// (session_date on new picking sessions, invoice/PO numbering, expense
// and stock-in default dates, etc.) is meant to reflect IST. Left unset,
// a brand-new picking_sessions row created any time in the ~5.5hr/day
// window after IST midnight but before UTC midnight got session_date
// stamped one day ahead of the server's own CURDATE() — silently hiding
// that order from the Picking dashboard until the server's date caught
// up. Setting this once, here, before any date() call in any api/*.php
// file (they all require this file first) fixes that at the source
// instead of chasing it symptom-by-symptom.
date_default_timezone_set('Asia/Kolkata');

// ── Database configuration ────────────────────────────────
// Reads from environment variables (Railway) with fallback
// to constants for local XAMPP development.
// Do NOT hardcode credentials here — set them in Railway dashboard.

function _env(string $key, string $default = ''): string {
    // Check $_ENV first, then getenv(), then fallback
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    return $default;
}

// Local XAMPP fallbacks (only used when env vars are not set)
$_DB_HOST = _env('MYSQLHOST', _env('MYSQL_HOST', _env('DB_HOST', 'localhost')));
$_DB_PORT = _env('MYSQLPORT', _env('MYSQL_PORT', _env('DB_PORT', '3306')));
$_DB_NAME = _env('MYSQLDATABASE', _env('MYSQL_DATABASE', _env('DB_NAME', 'invyrr')));
$_DB_USER = _env('MYSQLUSER', _env('MYSQL_USER', _env('DB_USER', 'root')));
$_DB_PASS = _env('MYSQLPASSWORD', _env('MYSQL_PASSWORD', _env('DB_PASS', '')));

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    global $_DB_HOST, $_DB_PORT, $_DB_NAME, $_DB_USER, $_DB_PASS;

    // Force TCP — Railway MySQL requires TCP, not Unix socket
    $dsn = "mysql:host={$_DB_HOST};port={$_DB_PORT};dbname={$_DB_NAME};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ];
    // If host is not localhost/127.0.0.1, force TCP (needed for Railway)
    if (!in_array($_DB_HOST, ['localhost', '127.0.0.1'], true)) {
        $options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
        $dsn = "mysql:host={$_DB_HOST};port={$_DB_PORT};dbname={$_DB_NAME};charset=utf8mb4";
    }
    try {
        $pdo = new PDO($dsn, $_DB_USER, $_DB_PASS, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'DB connection failed: ' . $e->getMessage()]));
    }
    return $pdo;
}

function jsonOk($data = null, string $message = 'OK'): void {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}
function jsonError(string $message, int $code = 400): void {
    if (ob_get_level()) ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}
function jsonList(array $rows, int $total = -1): void {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $rows, 'total' => $total >= 0 ? $total : count($rows)]);
    exit;
}
function getBody(): array {
    return json_decode(file_get_contents('php://input'), true) ?? $_POST;
}
function requireFields(array $body, array $fields): void {
    foreach ($fields as $f) {
        if (!isset($body[$f]) || (is_string($body[$f]) && trim($body[$f]) === ''))
            jsonError("Missing required field: $f");
    }
}

// ── Auth helpers ──────────────────────────────────────────
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('SM_SESSION');
        session_start();
    }
}
function currentUser(): ?array {
    startSession();
    return $_SESSION['user'] ?? null;
}
function requireAuth(): array {
    $u = currentUser();
    if (!$u) jsonError('Not authenticated', 401);
    return $u;
}
function requireRole(): array {
    $roles = func_get_args();
    $u = requireAuth();
    if (!in_array($u['role'], $roles)) jsonError('Insufficient permissions', 403);
    return $u;
}
// Delete is restricted to admin and partner. Use canDelete() before any
// DELETE operation -- every api/*.php DELETE handler already funnels
// through this single function, so this is the one place that needs to
// change to update the rule everywhere at once.
function canDelete(): bool {
    $u = currentUser();
    return in_array($u['role'] ?? '', ['admin','partner']);
}
function auditLog(PDO $pdo, string $action, string $entity = '', int $entityId = 0, string $detail = ''): void {
    $u = currentUser();
    try {
        $pdo->prepare("INSERT INTO audit_log (user_id,user_name,action,entity,entity_id,detail,ip)
                       VALUES (?,?,?,?,?,?,?)")
            ->execute([$u['id'] ?? null, $u['name'] ?? 'system', $action, $entity, $entityId ?: null, $detail, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {}
}
function getSetting(PDO $pdo, string $key, string $default = ''): string {
    $stmt = $pdo->prepare("SELECT v FROM settings WHERE k = ?");
    $stmt->execute([$key]);
    $r = $stmt->fetchColumn();
    return $r !== false ? (string)$r : $default;
}

// Returns user id only if it exists in users table — prevents FK violations across instances
function safeUserId(PDO $pdo): ?int {
    $u = currentUser();
    if (empty($u['id'])) return null;
    $s = $pdo->prepare("SELECT id FROM users WHERE id=?");
    $s->execute([(int)$u['id']]);
    return $s->fetchColumn() ? (int)$u['id'] : null;
}
