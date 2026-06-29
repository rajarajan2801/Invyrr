<?php
/**
 * Invyrr API — Payees (payment accounts / parties)
 * GET    /api/payees.php        → list
 * GET    /api/payees.php?id=N   → single
 * POST   /api/payees.php        → create
 * PUT    /api/payees.php        → update
 * DELETE /api/payees.php?id=N   → delete
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../includes/db.php';
startSession(); requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

// Ensure expenses table exists (safety for cross-dependency)
try { $pdo->exec("CREATE TABLE IF NOT EXISTS expenses (id INT AUTO_INCREMENT PRIMARY KEY, expense_date DATE NOT NULL, category VARCHAR(100) NOT NULL DEFAULT 'General', amount DECIMAL(12,2) NOT NULL, vendor_id INT DEFAULT NULL, payee_id INT DEFAULT NULL, reference_no VARCHAR(100) DEFAULT '', notes TEXT NULL, created_by INT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); } catch (Exception $e) {}

// Auto-create table if missing (graceful first-run)
$pdo->exec("CREATE TABLE IF NOT EXISTS payees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    type VARCHAR(50) DEFAULT '',
    account_no VARCHAR(100) DEFAULT '',
    bank_name VARCHAR(150) DEFAULT '',
    ifsc VARCHAR(20) DEFAULT '',
    upi_id VARCHAR(150) DEFAULT '',
    phone VARCHAR(30) DEFAULT '',
    notes VARCHAR(500) DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($method === 'GET') {
    if (!empty($_GET['id'])) {
        $s = $pdo->prepare("SELECT * FROM payees WHERE id=?");
        $s->execute([(int)$_GET['id']]);
        $row = $s->fetch(); if (!$row) jsonError('Not found', 404);
        jsonOk($row);
    }
    $where  = ['1=1'];
    $params = [];
    if (!empty($_GET['q'])) {
        $like    = '%' . $_GET['q'] . '%';
        $where[] = '(name LIKE ? OR type LIKE ? OR bank_name LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    if (isset($_GET['active_only'])) { $where[] = 'is_active=1'; }
    // Optional YTD filter on the joined vendor_payments
    $vpWhere = "";
    if (!empty($_GET['ytd'])) {
        $year = date('Y');
        $vpWhere = "AND vp.payment_date >= '$year-01-01' AND vp.payment_date <= '$year-12-31'";
    }
    $sql = "SELECT p.*,
                   (SELECT COUNT(*) FROM vendor_payments WHERE payee_id=p.id)
                   + (SELECT COUNT(*) FROM expenses WHERE payee_id=p.id) AS payment_count,
                   COALESCE((SELECT SUM(CASE WHEN type='credit_note' THEN -amount ELSE amount END) FROM vendor_payments WHERE payee_id=p.id),0) AS total_vp_paid,
                   COALESCE((SELECT SUM(amount) FROM expenses WHERE payee_id=p.id),0) AS total_expenses,
                   COALESCE((SELECT SUM(CASE WHEN type='credit_note' THEN -amount ELSE amount END) FROM vendor_payments WHERE payee_id=p.id),0)
                   + COALESCE((SELECT SUM(amount) FROM expenses WHERE payee_id=p.id),0) AS total_paid
            FROM payees p
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.name";
    $s = $pdo->prepare($sql);
    $s->execute($params);
    jsonList($s->fetchAll());
}

if ($method === 'POST') {
    requireRole('admin','manager','partner');
    $b = getBody(); requireFields($b, ['name']);
    $pdo->prepare("INSERT INTO payees (name,type,account_no,bank_name,ifsc,upi_id,phone,notes,is_active)
                   VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([
            trim($b['name']), trim($b['type']??''), trim($b['account_no']??''),
            trim($b['bank_name']??''), trim($b['ifsc']??''), trim($b['upi_id']??''),
            trim($b['phone']??''), trim($b['notes']??''), isset($b['is_active'])?(int)$b['is_active']:1
        ]);
    $id = (int)$pdo->lastInsertId();
    auditLog($pdo,'create_payee','payee',$id,trim($b['name']));
    jsonOk($pdo->query("SELECT * FROM payees WHERE id=$id")->fetch(), 'Payee created');
}

if ($method === 'PUT') {
    requireRole('admin','manager','partner');
    $b = getBody(); requireFields($b, ['id','name']);
    $pdo->prepare("UPDATE payees SET name=?,type=?,account_no=?,bank_name=?,ifsc=?,upi_id=?,phone=?,notes=?,is_active=? WHERE id=?")
        ->execute([
            trim($b['name']), trim($b['type']??''), trim($b['account_no']??''),
            trim($b['bank_name']??''), trim($b['ifsc']??''), trim($b['upi_id']??''),
            trim($b['phone']??''), trim($b['notes']??''), isset($b['is_active'])?(int)$b['is_active']:1,
            (int)$b['id']
        ]);
    auditLog($pdo,'update_payee','payee',(int)$b['id'],trim($b['name']));
    jsonOk(null,'Payee updated');
}

if ($method === 'DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    requireRole('admin');
    $id   = (int)($_GET['id'] ?? 0); if (!$id) jsonError('ID required');
    $name = $pdo->query("SELECT name FROM payees WHERE id=$id")->fetchColumn();
    // Soft-check: don't allow delete if has payments
    $count = (int)$pdo->query("SELECT COUNT(*) FROM vendor_payments WHERE payee_id=$id")->fetchColumn();
    if ($count > 0) jsonError("Cannot delete — $count payment(s) use this payee. Deactivate instead.", 409);
    $pdo->prepare("DELETE FROM payees WHERE id=?")->execute([$id]);
    auditLog($pdo,'delete_payee','payee',$id,$name);
    jsonOk(null,'Payee deleted');
}
