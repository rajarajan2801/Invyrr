<?php
/**
 * Stock Adjustments API
 * GET    /api/adjustments.php        → history
 * POST   /api/adjustments.php        → record adjustment (adds or subtracts stock)
 * DELETE /api/adjustments.php?id=N   → reverse
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
    $rows=$pdo->query("SELECT sa.*,p.name AS product_name,p.unit,l.name AS location_name FROM stock_adjustments sa JOIN products p ON p.id=sa.product_id LEFT JOIN locations l ON l.id=sa.location_id ORDER BY sa.date DESC,sa.id DESC LIMIT 500")->fetchAll();
    jsonList($rows);
}
if ($method==='POST') {
    $u=requireAuth(); $b=getBody();
    requireFields($b,['product_id','qty_change','reason','date']);
    $pid=(int)$b['product_id']; $change=(int)$b['qty_change'];
    $locId=!empty($b['location_id'])?(int)$b['location_id']:null;
    if ($change===0) jsonError('Quantity change cannot be zero');
    $pdo->beginTransaction();
    try {
        $p=$pdo->query("SELECT stock,name,unit FROM products WHERE id=$pid FOR UPDATE")->fetch();
        if (!$p) throw new Exception('Product not found');
        $newStock=(int)$p['stock']+$change;
        if ($newStock<0) throw new Exception("Cannot reduce stock below 0. Current: {$p['stock']} {$p['unit']}");
        $pdo->exec("UPDATE products SET stock=GREATEST(0,stock+($change)) WHERE id=$pid");
        if ($locId) $pdo->exec("INSERT INTO product_locations (product_id,location_id,stock,min_stock) VALUES ($pid,$locId,GREATEST(0,$change),0) ON DUPLICATE KEY UPDATE stock=GREATEST(0,stock+($change))");
        $pdo->prepare("INSERT INTO stock_adjustments (product_id,location_id,qty_change,reason,note,date,created_by) VALUES (?,?,?,?,?,?,?)")
            ->execute([$pid,$locId,$change,$b['reason'],$b['note']??'',$b['date'],$u['id']]);
        $adjId=(int)$pdo->lastInsertId();
        $pdo->commit();
        auditLog($pdo,'adjustment','adjustment',$adjId,($change>0?'+':'')."$change units of {$p['name']}: {$b['reason']}");
        jsonOk(['id'=>$adjId,'new_stock'=>max(0,$newStock)],'Adjustment recorded');
    } catch(Exception $e){ $pdo->rollBack(); jsonError($e->getMessage(),500); }
}
if ($method==='DELETE') {
    requireRole('admin','manager');
    $id=(int)($_GET['id']??0);
    $adj=$pdo->query("SELECT * FROM stock_adjustments WHERE id=$id")->fetch();
    if (!$adj) jsonError('Not found',404);
    $pdo->beginTransaction();
    try {
        $reverse=-(int)$adj['qty_change'];
        $pdo->exec("UPDATE products SET stock=GREATEST(0,stock+($reverse)) WHERE id={$adj['product_id']}");
        if ($adj['location_id']) $pdo->exec("UPDATE product_locations SET stock=GREATEST(0,stock+($reverse)) WHERE product_id={$adj['product_id']} AND location_id={$adj['location_id']}");
        $pdo->exec("DELETE FROM stock_adjustments WHERE id=$id");
        $pdo->commit();
        jsonOk(null,'Adjustment reversed');
    } catch(PDOException $e){ $pdo->rollBack(); jsonError($e->getMessage(),500); }
}
