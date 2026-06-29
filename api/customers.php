<?php
/**
 * Customers API
 * GET    /api/customers.php          → list (?q=search)
 * GET    /api/customers.php?id=N     → single + purchase history
 * POST   /api/customers.php          → create
 * PUT    /api/customers.php          → update
 * DELETE /api/customers.php?id=N     → delete
 */
require __DIR__.'/../includes/db.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}
startSession();
$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

if ($method==='GET') {
    if (!empty($_GET['id'])) {
        $c = $pdo->prepare("SELECT * FROM customers WHERE id=?");
        $c->execute([(int)$_GET['id']]);
        $row = $c->fetch();
        if (!$row) jsonError('Customer not found',404);
        // Purchase history via invoices
        $inv = $pdo->prepare("SELECT i.id,i.invoice_number,i.date,i.total,i.status FROM invoices i WHERE i.customer_id=? ORDER BY i.date DESC LIMIT 20");
        $inv->execute([$row['id']]);
        $row['invoices'] = $inv->fetchAll();
        $row['total_spent'] = (float)$pdo->prepare("SELECT COALESCE(SUM(total),0) FROM invoices WHERE customer_id=? AND status='paid'")->execute([$row['id']]) ? $pdo->query("SELECT COALESCE(SUM(total),0) FROM invoices WHERE customer_id={$row['id']} AND status='paid'")->fetchColumn() : 0;
        jsonOk($row);
    }
    $where=[]; $params=[];
    if (!empty($_GET['q'])) { $like='%'.$_GET['q'].'%'; $where[]='(name LIKE ? OR phone LIKE ? OR email LIKE ?)'; $params=[$like,$like,$like]; }
    $sql = "SELECT c.*, (SELECT COUNT(*) FROM invoices WHERE customer_id=c.id) AS invoice_count,
            (SELECT COALESCE(SUM(total),0) FROM invoices WHERE customer_id=c.id AND status='paid') AS total_spent
            FROM customers c".($where?' WHERE '.implode(' AND ',$where):'')." ORDER BY c.name";
    $s = $pdo->prepare($sql); $s->execute($params); jsonList($s->fetchAll());
}
if ($method==='POST') {
    requireAuth();
    $b=getBody(); requireFields($b,['name']);
    $pdo->prepare("INSERT INTO customers (name,phone,email,address,gst,notes) VALUES (?,?,?,?,?,?)")
        ->execute([trim($b['name']),trim($b['phone']??''),trim($b['email']??''),trim($b['address']??''),trim($b['gst']??''),trim($b['notes']??'')]);
    jsonOk(['id'=>(int)$pdo->lastInsertId()],'Customer created');
}
if ($method==='PUT') {
    requireAuth();
    $b=getBody(); requireFields($b,['id','name']);
    $pdo->prepare("UPDATE customers SET name=?,phone=?,email=?,address=?,gst=?,notes=? WHERE id=?")
        ->execute([trim($b['name']),trim($b['phone']??''),trim($b['email']??''),trim($b['address']??''),trim($b['gst']??''),trim($b['notes']??''),(int)$b['id']]);
    jsonOk(null,'Customer updated');
}
if ($method==='DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    requireRole('admin','manager','partner');
    $pdo->prepare("DELETE FROM customers WHERE id=?")->execute([(int)($_GET['id']??0)]);
    jsonOk(null,'Customer deleted');
}
