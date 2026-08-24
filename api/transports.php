<?php
/**
 * Invyrr API — Transport Companies (System settings list, used to
 * populate the Dispatch modal's Transport Name dropdown)
 * GET    /api/transports.php        → list (?active_only=1 to filter)
 * GET    /api/transports.php?id=N   → single
 * POST   /api/transports.php        → create
 * PUT    /api/transports.php        → update
 * DELETE /api/transports.php?id=N   → delete
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../includes/db.php';
startSession(); requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

$pdo->exec("CREATE TABLE IF NOT EXISTS transports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) DEFAULT '',
    notes VARCHAR(300) DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($method === 'GET') {
    if (!empty($_GET['id'])) {
        $s = $pdo->prepare("SELECT * FROM transports WHERE id=?");
        $s->execute([(int)$_GET['id']]);
        $row = $s->fetch(); if (!$row) jsonError('Not found', 404);
        jsonOk($row);
    }
    $where = ['1=1']; $params = [];
    if (isset($_GET['active_only'])) { $where[] = 'is_active=1'; }
    $sql = "SELECT * FROM transports WHERE " . implode(' AND ', $where) . " ORDER BY name";
    $s = $pdo->prepare($sql); $s->execute($params);
    jsonList($s->fetchAll());
}

if ($method === 'POST') {
    requireRole('admin','manager','partner');
    $b = getBody(); requireFields($b, ['name']);
    $name = trim($b['name']);
    try {
        $pdo->prepare("INSERT INTO transports (name,phone,notes,is_active) VALUES (?,?,?,?)")
            ->execute([$name, trim($b['phone'] ?? ''), trim($b['notes'] ?? ''), isset($b['is_active']) ? (int)$b['is_active'] : 1]);
    } catch (Exception $e) {
        jsonError(strpos($e->getMessage(), 'uniq_name') !== false ? 'A transport with that name already exists' : $e->getMessage(), 409);
    }
    $id = (int)$pdo->lastInsertId();
    auditLog($pdo, 'create_transport', 'transport', $id, $name);
    jsonOk($pdo->query("SELECT * FROM transports WHERE id=$id")->fetch(), 'Transport added');
}

if ($method === 'PUT') {
    requireRole('admin','manager','partner');
    $b = getBody(); requireFields($b, ['id','name']);
    $name = trim($b['name']);
    try {
        $pdo->prepare("UPDATE transports SET name=?,phone=?,notes=?,is_active=? WHERE id=?")
            ->execute([$name, trim($b['phone'] ?? ''), trim($b['notes'] ?? ''), isset($b['is_active']) ? (int)$b['is_active'] : 1, (int)$b['id']]);
    } catch (Exception $e) {
        jsonError(strpos($e->getMessage(), 'uniq_name') !== false ? 'A transport with that name already exists' : $e->getMessage(), 409);
    }
    auditLog($pdo, 'update_transport', 'transport', (int)$b['id'], $name);
    jsonOk(null, 'Transport updated');
}

if ($method === 'DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    requireRole('admin');
    $id = (int)($_GET['id'] ?? 0); if (!$id) jsonError('ID required');
    $name = $pdo->query("SELECT name FROM transports WHERE id=$id")->fetchColumn();
    $pdo->prepare("DELETE FROM transports WHERE id=?")->execute([$id]);
    auditLog($pdo, 'delete_transport', 'transport', $id, $name);
    jsonOk(null, 'Transport deleted');
}
