<?php
/**
 * Users API (admin only)
 */
// Suppress error display - return JSON for any error
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);
ob_start();

require __DIR__.'/../includes/db.php';

// Global error handler converts PHP errors to JSON
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>"PHP Error: $message in ".basename($file).":$line"]);
    exit;
});
set_exception_handler(function($e) {
    if (ob_get_level()) ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>'Exception: ' . $e->getMessage()]);
    exit;
});

header('Access-Control-Allow-Origin: *');
startSession();
$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

if ($method === 'OPTIONS') { http_response_code(204); exit; }

if ($method === 'GET') {
    requireRole('admin');
    $rows = $pdo->query("SELECT id,name,email,role,is_active,last_login,created_at FROM users ORDER BY name")->fetchAll();
    jsonList($rows);
}
if ($method === 'POST') {
    try {
        $u = requireRole('admin');
        $b = getBody();
        requireFields($b, ['name','password','role']);
        if (!in_array($b['role'],['admin','manager','cashier','partner'])) jsonError('Invalid role');
        $email = trim($b['email']??'');
        // Name must be unique (used as login)
        $exists = $pdo->prepare("SELECT id FROM users WHERE name=?");
        $exists->execute([trim($b['name'])]);
        if ($exists->fetch()) jsonError('A user with this name already exists', 409);
        $hash = password_hash($b['password'], PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users (name,email,password,role,is_active) VALUES (?,?,?,?,?)")
            ->execute([trim($b['name']),$email,$hash,$b['role'],(int)($b['is_active']??1)]);
        $id = (int)$pdo->lastInsertId();
        auditLog($pdo,'create_user','user',$id,"Created user {$b['name']}");
        jsonOk(['id'=>$id], 'User created');
    } catch (Throwable $e) {
        jsonError('Server error: ' . $e->getMessage(), 500);
    }
}
if ($method === 'PUT') {
    try {
        $u = requireRole('admin');
        $b = getBody();
        requireFields($b, ['id','name','role']);
        $email = trim($b['email']??'');
        // Check name uniqueness (excluding self)
        $exists = $pdo->prepare("SELECT id FROM users WHERE name=? AND id<>?");
        $exists->execute([trim($b['name']),(int)$b['id']]);
        if ($exists->fetch()) jsonError('A user with this name already exists', 409);
        $params = [trim($b['name']),$email,$b['role'],(int)($b['is_active']??1),(int)$b['id']];
        $sql = "UPDATE users SET name=?,email=?,role=?,is_active=?";
        if (!empty($b['password'])) { $sql .= ",password=?"; $params = [trim($b['name']),$email,$b['role'],(int)($b['is_active']??1),password_hash($b['password'],PASSWORD_BCRYPT),(int)$b['id']]; }
        $pdo->prepare("$sql WHERE id=?")->execute($params);
        auditLog($pdo,'update_user','user',(int)$b['id']);
        jsonOk(null,'User updated');
    } catch (Throwable $e) {
        jsonError('Server error: ' . $e->getMessage(), 500);
    }
}
if ($method === 'DELETE') {
    try {
        requireRole('admin');
        $id = (int)($_GET['id']??0);
        $u  = currentUser();
        if ($id === (int)$u['id']) jsonError('Cannot delete your own account');
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        auditLog($pdo,'delete_user','user',$id);
        jsonOk(null,'User deleted');
    } catch (Throwable $e) {
        jsonError('Server error: ' . $e->getMessage(), 500);
    }
}
