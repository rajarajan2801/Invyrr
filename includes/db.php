<?php
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
$_DB_HOST = _env('MYSQLHOST',     _env('DB_HOST', 'localhost'));
$_DB_PORT = _env('MYSQLPORT',     _env('DB_PORT', '3306'));
$_DB_NAME = _env('MYSQLDATABASE', _env('DB_NAME', 'invyrr'));
$_DB_USER = _env('MYSQLUSER',     _env('DB_USER', 'root'));
$_DB_PASS = _env('MYSQLPASSWORD', _env('DB_PASS', ''));

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    global $_DB_HOST, $_DB_PORT, $_DB_NAME, $_DB_USER, $_DB_PASS;

    $dsn = "mysql:host={$_DB_HOST};port={$_DB_PORT};dbname={$_DB_NAME};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $_DB_USER, $_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
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
