<?php
/**
 * Picking Sessions API
 * GET  ?date=YYYY-MM-DD          → list all sessions for date
 * GET  ?code=XXXXX               → get session by verify code
 * POST body={session JSON}        → create/update session
 * DELETE ?id=xxx                  → delete session
 */
require __DIR__.'/../includes/db.php';
require __DIR__.'/../includes/auth.php';

header('Content-Type: application/json');

// Auto-create table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS picking_sessions (
        id VARCHAR(64) PRIMARY KEY,
        order_no VARCHAR(64),
        customer VARCHAR(255),
        phone VARCHAR(20),
        picker VARCHAR(128),
        verify_code VARCHAR(20),
        verified TINYINT(1) DEFAULT 0,
        verified_by VARCHAR(128),
        verified_at DATETIME,
        session_date DATE NOT NULL,
        data LONGTEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get by verify code
    if (!empty($_GET['code'])) {
        $s = $pdo->prepare("SELECT * FROM picking_sessions WHERE verify_code=?");
        $s->execute([trim($_GET['code'])]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if (!$row) { echo json_encode(['success'=>false,'message'=>'Code not found']); exit; }
        $row['data'] = json_decode($row['data'], true);
        echo json_encode(['success'=>true,'data'=>$row]); exit;
    }
    // List by date
    $date = $_GET['date'] ?? date('Y-m-d');
    $s = $pdo->prepare("SELECT id,order_no,customer,phone,picker,verify_code,verified,verified_by,verified_at,session_date,updated_at,data FROM picking_sessions WHERE session_date=? ORDER BY created_at ASC");
    $s->execute([$date]);
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) $row['data'] = json_decode($row['data'], true);
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if ($method === 'POST') {
    $b = json_decode(file_get_contents('php://input'), true);
    if (!$b || empty($b['id'])) { echo json_encode(['success'=>false,'message'=>'Invalid data']); exit; }
    $pdo->prepare("INSERT INTO picking_sessions 
        (id,order_no,customer,phone,picker,verify_code,verified,verified_by,verified_at,session_date,data)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        order_no=VALUES(order_no),customer=VALUES(customer),phone=VALUES(phone),
        picker=VALUES(picker),verify_code=VALUES(verify_code),
        verified=VALUES(verified),verified_by=VALUES(verified_by),
        verified_at=VALUES(verified_at),data=VALUES(data),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        $b['id'],
        $b['orderNo'] ?? '',
        $b['customer'] ?? '',
        $b['phone'] ?? '',
        $b['picker'] ?? '',
        $b['verifyCode'] ?? null,
        $b['verified'] ? 1 : 0,
        $b['verifiedBy'] ?? null,
        !empty($b['verifiedAt']) ? date('Y-m-d H:i:s', intval($b['verifiedAt'])/1000) : null,
        $b['date'] ?? date('Y-m-d'),
        json_encode($b['items'] ?? [])
    ]);
    echo json_encode(['success'=>true]); exit;
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    if (!$id) { echo json_encode(['success'=>false]); exit; }
    $pdo->prepare("DELETE FROM picking_sessions WHERE id=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
}

echo json_encode(['success'=>false,'message'=>'Method not allowed']);
