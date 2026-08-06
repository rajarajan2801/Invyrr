<?php
/**
 * api/picking_sessions.php — Cross-device sync for Order Picking
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../includes/db.php';
startSession();
requireAuth();

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Auto-create table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS picking_sessions (
        id            VARCHAR(64)  PRIMARY KEY,
        order_no      VARCHAR(64),
        customer      VARCHAR(255),
        phone         VARCHAR(20),
        address       TEXT,
        picker        VARCHAR(128),
        verify_code   VARCHAR(20),
        verified      TINYINT(1)   DEFAULT 0,
        verified_by   VARCHAR(128),
        verified_at   DATETIME,
        status        VARCHAR(20)  DEFAULT 'pending',
        session_date  DATE         NOT NULL,
        data          LONGTEXT     NOT NULL,
        created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
        updated_at    DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_date (session_date),
        INDEX idx_code (verify_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    try { $pdo->exec("ALTER TABLE picking_sessions ADD COLUMN status VARCHAR(20) DEFAULT 'pending'"); } catch(Exception $e) {}
    try { $pdo->exec("ALTER TABLE picking_sessions ADD COLUMN address TEXT"); } catch(Exception $e) {}
} catch (Exception $e) {}

// ── GET ──────────────────────────────────────────────────
if ($method === 'GET') {

    // Debug: show all distinct dates in the table
    if (isset($_GET['debug'])) {
        $rows = $pdo->query("SELECT session_date, COUNT(*) as cnt FROM picking_sessions GROUP BY session_date ORDER BY session_date DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        jsonOk(['server_date' => date('Y-m-d'), 'server_datetime' => date('Y-m-d H:i:s'), 'dates_in_db' => $rows]);
    }

    if (!empty($_GET['code'])) {
        $s = $pdo->prepare("SELECT * FROM picking_sessions WHERE verify_code = ? LIMIT 1");
        $s->execute([trim($_GET['code'])]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if (!$row) { jsonList([]); exit; }
        $row['data'] = json_decode($row['data'] ?? '[]', true);
        jsonOk($row);
    }

    // If no date given, return ALL recent sessions (last 7 days) so nothing gets missed
    if (empty($_GET['date'])) {
        $s = $pdo->prepare(
            "SELECT id, order_no, customer, phone, address, picker,
                    verify_code, verified, verified_by, verified_at,
                    status, session_date, updated_at, data
             FROM picking_sessions
             WHERE session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             ORDER BY session_date DESC, created_at ASC"
        );
        $s->execute();
    } else {
        $date = preg_replace('/[^0-9\-]/', '', $_GET['date']);
        $s = $pdo->prepare(
            "SELECT id, order_no, customer, phone, address, picker,
                    verify_code, verified, verified_by, verified_at,
                    status, session_date, updated_at, data
             FROM picking_sessions
             WHERE session_date = ?
             ORDER BY created_at ASC"
        );
        $s->execute([$date]);
    }

    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['data'] = json_decode($row['data'] ?? '[]', true);
    }
    jsonList($rows);
}

// ── POST ─────────────────────────────────────────────────
if ($method === 'POST') {
    $b = json_decode(file_get_contents('php://input'), true);
    if (empty($b['id'])) jsonErr('Missing id');
    if (!empty($b['verified']) && !in_array(currentUser()['role'] ?? '', ['admin','manager','partner'])) {
        jsonError('Only admin, manager, or partner can verify orders', 403);
    }

    $pdo->prepare(
        "INSERT INTO picking_sessions
            (id, order_no, customer, phone, address, picker,
             verify_code, verified, verified_by, verified_at,
             status, session_date, data)
         VALUES (?,?,?,?,?, ?,?,?,?,?, ?,?,?)
         ON DUPLICATE KEY UPDATE
             order_no    = VALUES(order_no),
             customer    = VALUES(customer),
             phone       = VALUES(phone),
             address     = VALUES(address),
             picker      = VALUES(picker),
             verify_code = COALESCE(VALUES(verify_code), verify_code),
             verified    = VALUES(verified),
             verified_by = VALUES(verified_by),
             verified_at = VALUES(verified_at),
             status      = VALUES(status),
             data        = VALUES(data),
             updated_at  = CURRENT_TIMESTAMP"
    )->execute([
        $b['id'],
        $b['orderNo']    ?? '',
        $b['customer']   ?? '',
        $b['phone']      ?? '',
        $b['address']    ?? '',
        $b['picker']     ?? '',
        $b['verifyCode'] ?? null,
        empty($b['verified']) ? 0 : 1,
        $b['verifiedBy']  ?? null,
        !empty($b['verifiedAt'])
            ? date('Y-m-d H:i:s', intval($b['verifiedAt']) / 1000)
            : null,
        $b['status'] ?? 'pending',
        $b['date']   ?? date('Y-m-d'),
        json_encode($b['items'] ?? []),
    ]);
    jsonOk(null, 'Saved');
}

// ── DELETE ────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    $id = $_GET['id'] ?? '';
    if (!$id) jsonErr('Missing id');
    $pdo->prepare("DELETE FROM picking_sessions WHERE id = ?")
        ->execute([$id]);
    jsonOk(null, 'Deleted');
}

jsonErr('Method not allowed');
