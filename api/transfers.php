<?php
/**
 * Stock Transfers API
 * GET    /api/transfers.php         → history
 * POST   /api/transfers.php         → create transfer (moves stock between locations)
 * DELETE /api/transfers.php?id=N    → reverse
 */
require __DIR__.'/../includes/db.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}
startSession();
$method=$_SERVER['REQUEST_METHOD'];
$pdo=getDB();

if ($method==='GET') {
    $where=['1=1']; $params=[];
    if (!empty($_GET['product_id'])) { $where[]='t.product_id=?'; $params[]=(int)$_GET['product_id']; }
    if (!empty($_GET['location_id'])) { $where[]='(t.from_location=? OR t.to_location=?)'; $params[]=(int)$_GET['location_id']; $params[]=(int)$_GET['location_id']; }
    $rows=$pdo->prepare("SELECT t.*,p.name AS product_name,p.unit,fl.name AS from_name,tl.name AS to_name,u.name AS created_by_name FROM stock_transfers t JOIN products p ON p.id=t.product_id JOIN locations fl ON fl.id=t.from_location JOIN locations tl ON tl.id=t.to_location LEFT JOIN users u ON u.id=t.created_by WHERE ".implode(' AND ',$where)." ORDER BY t.date DESC,t.id DESC LIMIT 500");
    $rows->execute($params); jsonList($rows->fetchAll());
}
if ($method==='POST') {
    $u=requireAuth(); $b=getBody();
    requireFields($b,['product_id','from_location','to_location','qty','date']);
    $from=(int)$b['from_location']; $to=(int)$b['to_location'];
    $pid=(int)$b['product_id']; $qty=(int)$b['qty'];
    if ($from===$to) jsonError('From and To locations must be different');
    if ($qty<1) jsonError('Quantity must be at least 1');
    $pdo->beginTransaction();
    try {
        // Check source stock
        $ls=$pdo->query("SELECT stock FROM product_locations WHERE product_id=$pid AND location_id=$from FOR UPDATE")->fetch();
        $avail=$ls?(int)$ls['stock']:0;
        if ($avail<$qty) {
            $loc=$pdo->query("SELECT name FROM locations WHERE id=$from")->fetchColumn();
            throw new Exception("Insufficient stock at $loc: $avail available");
        }
        // Deduct from source
        $pdo->exec("UPDATE product_locations SET stock=GREATEST(0,stock-$qty) WHERE product_id=$pid AND location_id=$from");
        // Add to destination (upsert)
        $pdo->exec("INSERT INTO product_locations (product_id,location_id,stock,min_stock) VALUES ($pid,$to,$qty,0) ON DUPLICATE KEY UPDATE stock=stock+$qty");
        // Record transfer
        $pdo->prepare("INSERT INTO stock_transfers (from_location,to_location,product_id,qty,note,date,created_by) VALUES (?,?,?,?,?,?,?)")
            ->execute([$from,$to,$pid,$qty,$b['note']??'',$b['date'],$u['id']]);
        $tid=(int)$pdo->lastInsertId();
        $pdo->commit();
        auditLog($pdo,'stock_transfer','transfer',$tid,"Moved $qty units of product $pid from loc $from to $to");
        jsonOk(['id'=>$tid],'Transfer recorded');
    } catch(Exception $e){ $pdo->rollBack(); jsonError($e->getMessage(),500); }
}
if ($method==='DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    requireRole('admin','manager','partner');
    $id=(int)($_GET['id']??0);
    $t=$pdo->query("SELECT * FROM stock_transfers WHERE id=$id")->fetch();
    if (!$t) jsonError('Transfer not found',404);
    $pdo->beginTransaction();
    try {
        $pdo->exec("UPDATE product_locations SET stock=GREATEST(0,stock-{$t['qty']}) WHERE product_id={$t['product_id']} AND location_id={$t['to_location']}");
        $pdo->exec("INSERT INTO product_locations (product_id,location_id,stock,min_stock) VALUES ({$t['product_id']},{$t['from_location']},{$t['qty']},0) ON DUPLICATE KEY UPDATE stock=stock+{$t['qty']}");
        $pdo->exec("DELETE FROM stock_transfers WHERE id=$id");
        $pdo->commit();
        jsonOk(null,'Transfer reversed');
    } catch(PDOException $e){ $pdo->rollBack(); jsonError($e->getMessage(),500); }
}
