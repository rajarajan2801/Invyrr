<?php
/**
 * Purchase Orders API
 * GET    /api/purchase_orders.php           → list
 * GET    /api/purchase_orders.php?id=N      → single with items
 * POST   /api/purchase_orders.php           → create
 * PUT    /api/purchase_orders.php           → update status / receive items
 * DELETE /api/purchase_orders.php?id=N      → delete draft only
 */
require __DIR__.'/../includes/db.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}
startSession();
$method=$_SERVER['REQUEST_METHOD'];
$pdo=getDB();
// Tracks who most recently edited a PO (status change, receive, item edit) --
// separate from created_by, which only ever reflects who originally raised it.
try { $pdo->exec("ALTER TABLE purchase_orders ADD COLUMN updated_by INT DEFAULT NULL"); } catch (Exception $e) {}

function getPO(PDO $pdo, int $id): ?array {
    $po=$pdo->query("SELECT po.*,v.name AS vendor_name,l.name AS location_name,
        uu.name AS updated_by_name,
        ROUND(COALESCE((SELECT SUM(poi.qty_ordered*poi.cost) FROM purchase_order_items poi WHERE poi.po_id=$id),0)+COALESCE(po.misc_charges,0),0) AS total
        FROM purchase_orders po
        LEFT JOIN vendors v ON v.id=po.vendor_id
        LEFT JOIN locations l ON l.id=po.location_id
        LEFT JOIN users uu ON uu.id=COALESCE(po.updated_by, po.created_by)
        WHERE po.id=$id")->fetch();
    if (!$po) return null;
    $po['items']=$pdo->query("SELECT poi.*,p.name AS product_name,p.sku,p.brand,p.unit,p.case_content FROM purchase_order_items poi JOIN products p ON p.id=poi.product_id WHERE poi.po_id=$id ORDER BY poi.id")->fetchAll();
    return $po;
}

if ($method==='GET' && empty($_GET['id'])) {
    $where=['1=1']; $params=[];
    if (!empty($_GET['vendor_id']))    { $where[]='po.vendor_id=?'; $params[]=(int)$_GET['vendor_id']; }
    if (!empty($_GET['status']))       { $where[]='po.status=?';    $params[]=$_GET['status']; }
    if (!empty($_GET['status_filter']) && $_GET['status_filter']==='open') {
        $where[]="po.status IN ('draft','sent','partial')";
    }
    $rows=$pdo->prepare("SELECT po.*,v.name AS vendor_name,l.name AS location_name,
        uu.name AS updated_by_name,
        ROUND(COALESCE((SELECT SUM(poi.qty_ordered*poi.cost) FROM purchase_order_items poi WHERE poi.po_id=po.id),0)+COALESCE(po.misc_charges,0),0) AS total,
        (SELECT COUNT(*) FROM purchase_order_items poi WHERE poi.po_id=po.id) AS item_count,
        -- Cases ordered/received: sum of each line's qty / that product's case_content.
        -- Falls back to treating the item as 1 unit = 1 'case' when case_content is
        -- unset (0/NULL), matching how Qty (Cases) already behaves on the PO form.
        ROUND(COALESCE((SELECT SUM(poi.qty_ordered/COALESCE(NULLIF(p.case_content,0),1)) FROM purchase_order_items poi JOIN products p ON p.id=poi.product_id WHERE poi.po_id=po.id),0),1) AS cases_ordered,
        ROUND(COALESCE((SELECT SUM(poi.qty_received/COALESCE(NULLIF(p.case_content,0),1)) FROM purchase_order_items poi JOIN products p ON p.id=poi.product_id WHERE poi.po_id=po.id),0),1) AS cases_received
        FROM purchase_orders po
        LEFT JOIN vendors v ON v.id=po.vendor_id
        LEFT JOIN locations l ON l.id=po.location_id
        LEFT JOIN users uu ON uu.id=COALESCE(po.updated_by, po.created_by)
        WHERE ".implode(' AND ',$where)." ORDER BY po.created_at DESC");
    $rows->execute($params);
    $pos = $rows->fetchAll();
    // compact=1: attach full item details to each PO (used for export)
    if (!empty($_GET['compact']) && $pos) {
        foreach ($pos as &$po) {
            $items = $pdo->prepare("SELECT poi.product_id, poi.qty_ordered, COALESCE(poi.qty_received,0) AS qty_received, poi.cost, p.name AS product_name, p.sku, p.brand, p.unit, p.case_content FROM purchase_order_items poi JOIN products p ON p.id=poi.product_id WHERE poi.po_id=? ORDER BY poi.id");
            $items->execute([$po['id']]);
            $po['items'] = $items->fetchAll();
        }
        unset($po);
    }
    jsonList($pos);
}
if ($method==='GET' && !empty($_GET['id'])) {
    $po=getPO($pdo,(int)$_GET['id']); if (!$po) jsonError('Not found',404); jsonOk($po);
}
if ($method==='POST') {
    $u=requireAuth(); $b=getBody(); requireFields($b,['vendor_id','items']);
    $pdo->beginTransaction();
    try {
        $biz=($pdo->query("SELECT k,v FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR));
        $prefix=$biz['po_prefix']??'PO';
        $last=(int)$pdo->query("SELECT COUNT(*) FROM purchase_orders")->fetchColumn();
        $poNum=$prefix.'-'.date('Ymd').'-'.str_pad($last+1,4,'0',STR_PAD_LEFT);
        $locId=!empty($b['location_id'])?(int)$b['location_id']:null;
        // Verify created_by user exists to avoid FK violation
        $createdBy = !empty($u['id']) ? (int)$u['id'] : null;
        if ($createdBy) {
            $exists = $pdo->prepare("SELECT id FROM users WHERE id=?");
            $exists->execute([$createdBy]);
            if (!$exists->fetch()) $createdBy = null;
        }
        $pdo->prepare("INSERT INTO purchase_orders (po_number,vendor_id,location_id,status,expected_date,notes,misc_charges,created_by) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$poNum,(int)$b['vendor_id'],$locId,'draft',$b['expected_date']??null,$b['notes']??'',round((float)($b['misc_charges']??0),2),$createdBy]);
        $poId=(int)$pdo->lastInsertId();
        foreach ($b['items'] as $item) {
            if (empty($item['product_id'])||empty($item['qty_ordered'])) continue;
            $pdo->prepare("INSERT INTO purchase_order_items (po_id,product_id,qty_ordered,qty_received,cost) VALUES (?,?,?,0,?)")
                ->execute([$poId,(int)$item['product_id'],(int)$item['qty_ordered'],(float)($item['cost']??0)]);
        }
        $pdo->commit();
        auditLog($pdo,'create_po','purchase_order',$poId,"PO $poNum");
        jsonOk(getPO($pdo,$poId),"Purchase Order $poNum created");
    } catch(Exception $e){ $pdo->rollBack(); jsonError($e->getMessage(),500); }
}
if ($method==='PUT') {
    $u=requireAuth(); $b=getBody(); requireFields($b,['id']);
    $id=(int)$b['id'];
    $po=getPO($pdo,$id); if (!$po) jsonError('Not found',404);
    // Receiving items
    if (!empty($b['receive']) && is_array($b['receive'])) {
        $pdo->beginTransaction();
        try {
            foreach ($b['receive'] as $itemId=>$qtyReceived) {
                $qtyReceived=(int)$qtyReceived;
                if ($qtyReceived<=0) continue;
                $item=$pdo->query("SELECT * FROM purchase_order_items WHERE id=$itemId AND po_id=$id")->fetch();
                if (!$item) continue;
                $remaining=$item['qty_ordered']-$item['qty_received'];
                $toReceive=min($qtyReceived,$remaining);
                if ($toReceive<=0) continue;
                $pdo->exec("UPDATE purchase_order_items SET qty_received=qty_received+$toReceive WHERE id=$itemId");
                // Create stock_in
                $locId=$po['location_id']??null;
                if (!$locId) { $r=$pdo->query("SELECT id FROM locations WHERE is_default=1 LIMIT 1")->fetch(); $locId=$r?(int)$r['id']:null; }
                $cost=(float)$item['cost'];
                $pdo->prepare("INSERT INTO stock_in (product_id,location_id,vendor_id,po_id,qty,cost,date,note,created_by) VALUES (?,?,?,?,?,?,NOW(),?,?)")
                    ->execute([$item['product_id'],$locId,$po['vendor_id'],$id,$toReceive,$cost,"Received from PO {$po['po_number']}",$u['id']]);
                $pdo->exec("UPDATE products SET stock=stock+$toReceive, cost=IF($cost>0,$cost,cost) WHERE id={$item['product_id']}");
                if ($locId) $pdo->exec("INSERT INTO product_locations (product_id,location_id,stock,min_stock) VALUES ({$item['product_id']},$locId,$toReceive,0) ON DUPLICATE KEY UPDATE stock=stock+$toReceive");
            }
            // Update PO status
            $items=$pdo->query("SELECT qty_ordered,qty_received FROM purchase_order_items WHERE po_id=$id")->fetchAll();
            $allReceived=true; $anyReceived=false;
            foreach ($items as $it) { if ($it['qty_received']>0) $anyReceived=true; if ($it['qty_received']<$it['qty_ordered']) $allReceived=false; }
            $newStatus=$allReceived?'received':($anyReceived?'partial':'sent');
            $updatedBy=(int)$u['id'];
            $pdo->exec("UPDATE purchase_orders SET status='$newStatus',updated_by=$updatedBy,updated_at=NOW() WHERE id=$id");
            $pdo->commit();
            jsonOk(getPO($pdo,$id),'Items received and stock updated');
        } catch(Exception $e){ $pdo->rollBack(); jsonError($e->getMessage(),500); }
    } else {
        $newStatus = $b['status'] ?? $po['status'];

        // ── Reverse stock when moving back from received OR partial ──
        // Covers: received→partial, received→sent, received→draft, partial→sent, partial→draft
        $wasReceived = $po['status'] === 'received' || $po['status'] === 'partial';
        $goingBack   = in_array($newStatus, ['draft', 'sent', 'partial', 'cancelled']) && $po['status'] !== $newStatus;
        $needsReversal = $wasReceived && $goingBack && !($po['status'] === 'partial' && $newStatus === 'received');

        if ($needsReversal) {
            $pdo->beginTransaction();
            try {
                // Find and reverse all stock_in records linked to this PO
                $siRows = $pdo->query("SELECT id, product_id, location_id, qty FROM stock_in WHERE po_id=$id")->fetchAll();
                foreach ($siRows as $si) {
                    $qty    = (int)$si['qty'];
                    $prodId = (int)$si['product_id'];
                    $locId  = (int)$si['location_id'];
                    $pdo->exec("UPDATE products SET stock=GREATEST(0,stock-$qty) WHERE id=$prodId");
                    if ($locId) $pdo->exec("UPDATE product_locations SET stock=GREATEST(0,stock-$qty) WHERE product_id=$prodId AND location_id=$locId");
                    $pdo->exec("DELETE FROM stock_in WHERE id={$si['id']}");
                }
                // Reset qty_received on all items
                $pdo->exec("UPDATE purchase_order_items SET qty_received=0 WHERE po_id=$id");
                $pdo->prepare("UPDATE purchase_orders SET status=?,expected_date=?,notes=?,updated_by=?,updated_at=NOW() WHERE id=?")
                    ->execute([$newStatus, $b['expected_date']??$po['expected_date'], $b['notes']??$po['notes'], $u['id'], $id]);
                $pdo->commit();
                auditLog($pdo,'update_po','purchase_order',$id,"Reverted to $newStatus — stock reversed");
                jsonOk(getPO($pdo,$id), "PO reverted to $newStatus — stock corrected");
            } catch(Exception $e){ $pdo->rollBack(); jsonError($e->getMessage(),500); }
        }

        // ── Marking draft/sent → received: auto-receive all remaining qty ──
        if ($newStatus === 'received' && $po['status'] !== 'received') {
            $pdo->beginTransaction();
            try {
                $items = $pdo->query("SELECT * FROM purchase_order_items WHERE po_id=$id")->fetchAll();
                $locId = $po['location_id'] ?? null;
                if (!$locId) { $r=$pdo->query("SELECT id FROM locations WHERE is_default=1 LIMIT 1")->fetch(); $locId=$r?(int)$r['id']:null; }
                foreach ($items as $item) {
                    $remaining = (int)$item['qty_ordered'] - (int)$item['qty_received'];
                    if ($remaining <= 0) continue;
                    $cost = (float)$item['cost'];
                    $pdo->exec("UPDATE purchase_order_items SET qty_received=qty_ordered WHERE id={$item['id']}");
                    $pdo->prepare("INSERT INTO stock_in (product_id,location_id,vendor_id,po_id,qty,cost,date,note,created_by) VALUES (?,?,?,?,?,?,NOW(),?,?)")
                        ->execute([$item['product_id'],$locId,$po['vendor_id'],$id,$remaining,$cost,"Received from PO {$po['po_number']}",$u['id']]);
                    $pdo->exec("UPDATE products SET stock=stock+$remaining, cost=IF($cost>0,$cost,cost) WHERE id={$item['product_id']}");
                    if ($locId) $pdo->exec("INSERT INTO product_locations (product_id,location_id,stock,min_stock) VALUES ({$item['product_id']},$locId,$remaining,0) ON DUPLICATE KEY UPDATE stock=stock+$remaining");
                }
                $pdo->prepare("UPDATE purchase_orders SET status='received',expected_date=?,notes=?,updated_by=?,updated_at=NOW() WHERE id=?")
                    ->execute([$b['expected_date']??$po['expected_date'],$b['notes']??$po['notes'],$u['id'],$id]);
                $pdo->commit();
                jsonOk(getPO($pdo,$id),'PO marked received and stock updated');
            } catch(Exception $e){ $pdo->rollBack(); jsonError($e->getMessage(),500); }
        }
        $pdo->beginTransaction();
        try {
            // Update PO header
            $pdo->prepare("UPDATE purchase_orders SET status=?,expected_date=?,notes=?,vendor_id=?,location_id=?,misc_charges=?,updated_by=?,updated_at=NOW() WHERE id=?")
                ->execute([
                    $newStatus,
                    $b['expected_date'] ?? $po['expected_date'],
                    $b['notes'] ?? $po['notes'],
                    !empty($b['vendor_id']) ? (int)$b['vendor_id'] : $po['vendor_id'],
                    !empty($b['location_id']) ? (int)$b['location_id'] : $po['location_id'],
                    round((float)($b['misc_charges'] ?? $po['misc_charges'] ?? 0), 2),
                    $u['id'],
                    $id
                ]);

            // Sync line items if provided
            if (!empty($b['items']) && is_array($b['items'])) {
                // Get existing items (preserve qty_received)
                $existing = [];
                foreach ($pdo->query("SELECT * FROM purchase_order_items WHERE po_id=$id")->fetchAll() as $row) {
                    $existing[$row['id']] = $row;
                }

                $incomingIds = [];
                foreach ($b['items'] as $item) {
                    if (empty($item['product_id']) || empty($item['qty_ordered'])) continue;
                    $prodId  = (int)$item['product_id'];
                    $qty     = (int)$item['qty_ordered'];
                    $cost    = (float)($item['cost'] ?? 0);
                    $itemId  = !empty($item['id']) ? (int)$item['id'] : 0;

                    if ($itemId && isset($existing[$itemId])) {
                        // Update existing item. qty_received is normally preserved as-is
                        // (the dedicated "receive" action above is the usual way to receive
                        // stock) — but if the client explicitly sends a different qty_received
                        // (the Edit PO screen's Qty Received field, used to correct a mistaken
                        // receive), honor it here and apply the stock delta so products/
                        // product_locations and the PO stay in sync with what was corrected.
                        $oldRecv = (int)$existing[$itemId]['qty_received'];
                        $newRecv = $oldRecv;
                        if (isset($item['qty_received'])) {
                            $newRecv = max(0, min($qty, (int)$item['qty_received']));
                        }
                        $pdo->prepare("UPDATE purchase_order_items SET product_id=?,qty_ordered=?,cost=?,qty_received=? WHERE id=? AND po_id=?")
                            ->execute([$prodId, $qty, $cost, $newRecv, $itemId, $id]);
                        $incomingIds[] = $itemId;

                        $delta = $newRecv - $oldRecv;
                        if ($delta !== 0) {
                            $locId = $po['location_id'] ?? null;
                            if (!$locId) { $r=$pdo->query("SELECT id FROM locations WHERE is_default=1 LIMIT 1")->fetch(); $locId=$r?(int)$r['id']:null; }
                            $note = ($delta > 0 ? "Correction: +$delta received" : "Correction: $delta received")." for PO {$po['po_number']}";
                            $pdo->prepare("INSERT INTO stock_in (product_id,location_id,vendor_id,po_id,qty,cost,date,note,created_by) VALUES (?,?,?,?,?,?,NOW(),?,?)")
                                ->execute([$prodId, $locId, $po['vendor_id'], $id, $delta, $cost, $note, $u['id']]);
                            $pdo->exec("UPDATE products SET stock=GREATEST(0,stock+($delta)) WHERE id=$prodId");
                            if ($locId) $pdo->exec("UPDATE product_locations SET stock=GREATEST(0,stock+($delta)) WHERE product_id=$prodId AND location_id=$locId");
                        }
                    } else {
                        // New item
                        $pdo->prepare("INSERT INTO purchase_order_items (po_id,product_id,qty_ordered,qty_received,cost) VALUES (?,?,?,0,?)")
                            ->execute([$id, $prodId, $qty, $cost]);
                        $incomingIds[] = (int)$pdo->lastInsertId();
                    }
                }

                // Remove items that were deleted in the edit modal (only safe for unreceived items)
                foreach ($existing as $exId => $exItem) {
                    if (!in_array($exId, $incomingIds)) {
                        // Only delete if nothing has been received for this item
                        if ((int)$exItem['qty_received'] === 0) {
                            $pdo->exec("DELETE FROM purchase_order_items WHERE id=$exId AND po_id=$id");
                        }
                        // If partially received, keep it — can't delete received stock
                    }
                }
            }

            $pdo->commit();
            auditLog($pdo,'update_po','purchase_order',$id,"Status: $newStatus");
            jsonOk(getPO($pdo,$id),'PO updated');
        } catch(Exception $e){ $pdo->rollBack(); jsonError($e->getMessage(),500); }
    }
}
if ($method==='DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    requireRole('admin','manager','partner');
    $id=(int)($_GET['id']??0);
    $po=$pdo->query("SELECT status FROM purchase_orders WHERE id=$id")->fetch();
    if (!$po) jsonError('Not found',404);
    if (!in_array($po['status'], ['draft','cancelled'])) jsonError('Only draft or cancelled POs can be deleted');
    $pdo->exec("DELETE FROM purchase_orders WHERE id=$id");
    jsonOk(null,'PO deleted');
}
