<?php
/**
 * Stock Adjustments API
 * GET    /api/adjustments.php        → history
 * POST   /api/adjustments.php        → record adjustment (adds or subtracts stock)
 * PUT    /api/adjustments.php        → edit an existing adjustment (qty/location/reason/note/date)
 * DELETE /api/adjustments.php?id=N   → reverse
 */
require __DIR__.'/../includes/db.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}
startSession();
$method=$_SERVER['REQUEST_METHOD'];
$pdo=getDB();

// 'fulfillment' -- automatic deductions/reversals driven by the
// Fulfillment/Picking flow (order loaded, substitute or gift added, or
// any of those reversed on removal/delete) -- distinct from a person
// manually recording damage/theft/correction/recount here. Self-healing
// for databases that already had this table before this reason existed.
try { $pdo->exec("ALTER TABLE stock_adjustments MODIFY reason ENUM('damage','theft','correction','recount','other','fulfillment') NOT NULL"); } catch (Exception $e) {}

if ($method==='GET') {
    $rows=$pdo->query("SELECT sa.*,p.name AS product_name,p.unit,l.name AS location_name,u.name AS created_by_name FROM stock_adjustments sa JOIN products p ON p.id=sa.product_id LEFT JOIN locations l ON l.id=sa.location_id LEFT JOIN users u ON u.id=sa.created_by ORDER BY sa.date DESC,sa.id DESC LIMIT 500")->fetchAll();
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
        // Deliberately NOT floored at 0 (and no longer rejected outright) --
        // same reasoning as api/invoices.php's own stock handling (see
        // "Stop blocking Estimates on stock, and let location stock go
        // negative"): the stock figure in Invyrr often lags what's
        // physically on the shelf (counts not updated yet, a transfer in
        // transit, etc.), so a staff member recording a correction/recount/
        // damage/theft adjustment needs to be able to land on whatever the
        // real number is -- including negative, when Invyrr's own count was
        // already wrong going in -- rather than being blocked here and
        // forced to go figure out the "right" non-negative number first.
        $pdo->exec("UPDATE products SET stock=stock+($change) WHERE id=$pid");
        if ($locId) $pdo->exec("INSERT INTO product_locations (product_id,location_id,stock,min_stock) VALUES ($pid,$locId,$change,0) ON DUPLICATE KEY UPDATE stock=stock+($change)");
        $pdo->prepare("INSERT INTO stock_adjustments (product_id,location_id,qty_change,reason,note,date,created_by) VALUES (?,?,?,?,?,?,?)")
            ->execute([$pid,$locId,$change,$b['reason'],$b['note']??'',$b['date'],$u['id']]);
        $adjId=(int)$pdo->lastInsertId();
        $pdo->commit();
        auditLog($pdo,'adjustment','adjustment',$adjId,($change>0?'+':'')."$change units of {$p['name']}: {$b['reason']}");
        jsonOk(['id'=>$adjId,'new_stock'=>$newStock],'Adjustment recorded');
    } catch(Exception $e){ $pdo->rollBack(); jsonError($e->getMessage(),500); }
}
if ($method==='PUT') {
    $u=requireAuth(); $b=getBody();
    requireFields($b,['id']);
    $id=(int)$b['id'];
    $pdo->beginTransaction();
    try {
        $adj=$pdo->query("SELECT * FROM stock_adjustments WHERE id=$id FOR UPDATE")->fetch();
        if (!$adj) throw new Exception('Adjustment not found');
        $pid=(int)$adj['product_id'];
        $p=$pdo->query("SELECT stock,name,unit FROM products WHERE id=$pid FOR UPDATE")->fetch();
        if (!$p) throw new Exception('Product not found');

        $oldChange = (int)$adj['qty_change'];
        $oldLocId  = $adj['location_id'] !== null ? (int)$adj['location_id'] : null;
        $newChange = isset($b['qty_change']) && $b['qty_change'] !== '' ? (int)$b['qty_change'] : $oldChange;
        $newLocId  = array_key_exists('location_id',$b) ? (!empty($b['location_id']) ? (int)$b['location_id'] : null) : $oldLocId;
        if ($newChange===0) throw new Exception('Quantity change cannot be zero');

        // Product is intentionally NOT editable here — changing which
        // product an adjustment applies to is really "delete and
        // re-create", not an edit. Only qty/location/reason/note/date.

        // Global product stock only cares about the net delta between the
        // old and new qty_change, regardless of whether location changed.
        // Not floored/rejected at 0 -- see the matching comment on the
        // POST handler above.
        $delta = $newChange - $oldChange;
        if ($delta !== 0) {
            $pdo->exec("UPDATE products SET stock=stock+($delta) WHERE id=$pid");
        }

        // Per-location stock: reverse the old location's share of the old
        // qty_change, then apply the new qty_change to the new location.
        if ($oldLocId !== null && $oldLocId !== $newLocId) {
            $revOld = -$oldChange;
            $pdo->exec("UPDATE product_locations SET stock=stock+($revOld) WHERE product_id=$pid AND location_id=$oldLocId");
            if ($newLocId !== null) {
                $pdo->exec("INSERT INTO product_locations (product_id,location_id,stock,min_stock) VALUES ($pid,$newLocId,$newChange,0) ON DUPLICATE KEY UPDATE stock=stock+($newChange)");
            }
        } elseif ($newLocId !== null && $delta !== 0) {
            // Same location, quantity changed — apply just the delta
            $pdo->exec("INSERT INTO product_locations (product_id,location_id,stock,min_stock) VALUES ($pid,$newLocId,$delta,0) ON DUPLICATE KEY UPDATE stock=stock+($delta)");
        }

        $pdo->prepare("UPDATE stock_adjustments SET qty_change=?,location_id=?,reason=?,note=?,date=? WHERE id=?")
            ->execute([
                $newChange,
                $newLocId,
                $b['reason'] ?? $adj['reason'],
                $b['note']   ?? $adj['note'],
                $b['date']   ?? $adj['date'],
                $id,
            ]);
        $pdo->commit();
        auditLog($pdo,'adjustment_edit','adjustment',$id,"Edited adjustment for {$p['name']}: {$oldChange} -> {$newChange}");
        jsonOk(null,'Adjustment updated');
    } catch(Exception $e){ $pdo->rollBack(); jsonError($e->getMessage(),500); }
}
if ($method==='DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    requireRole('admin','manager','partner');
    $id=(int)($_GET['id']??0);
    $adj=$pdo->query("SELECT * FROM stock_adjustments WHERE id=$id")->fetch();
    if (!$adj) jsonError('Not found',404);
    $pdo->beginTransaction();
    try {
        // Not floored at 0 either -- see the POST handler's comment. A
        // floored reversal wouldn't be a true inverse of the original
        // adjustment whenever stock has moved since, e.g. reversing a -10
        // adjustment should always add back exactly +10, even if the
        // product's current stock is already negative for an unrelated
        // reason.
        $reverse=-(int)$adj['qty_change'];
        $pdo->exec("UPDATE products SET stock=stock+($reverse) WHERE id={$adj['product_id']}");
        if ($adj['location_id']) $pdo->exec("UPDATE product_locations SET stock=stock+($reverse) WHERE product_id={$adj['product_id']} AND location_id={$adj['location_id']}");
        $pdo->exec("DELETE FROM stock_adjustments WHERE id=$id");
        $pdo->commit();
        jsonOk(null,'Adjustment reversed');
    } catch(PDOException $e){ $pdo->rollBack(); jsonError($e->getMessage(),500); }
}
