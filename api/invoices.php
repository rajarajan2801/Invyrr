<?php
/**
 * Invoices API
 * GET    /api/invoices.php            → list (?from=&to=&customer_id=&location_id=&status=)
 * GET    /api/invoices.php?id=N       → single invoice with items
 * POST   /api/invoices.php            → create invoice (deducts stock per item)
 * PUT    /api/invoices.php            → update status/notes only
 * DELETE /api/invoices.php?id=N       → cancel (restores stock)
 * GET    /api/invoices.php?print=N    → HTML invoice for printing
 */
require __DIR__.'/../includes/db.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}
startSession();
$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

// Estimates default to 'open' (awaiting payment) rather than always
// 'paid' -- status is derived from amount_received vs total everywhere
// an estimate is created or its payment is edited (see
// deriveInvoiceStatus() below). 'draft' is kept in the enum for backward
// compatibility with any existing rows but is no longer written.
try { $pdo->exec("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','open','paid','cancelled') NOT NULL DEFAULT 'open'"); } catch (Exception $e) {}
// The payee/account a payment was received into -- sent by the frontend
// on every save already, but never had anywhere to land.
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN upi_payee_id INT DEFAULT NULL AFTER payment_method"); } catch (Exception $e) {}
// Customer mobile number -- powers the WhatsApp button and is shown on
// both print views.
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN customer_phone VARCHAR(20) DEFAULT '' AFTER customer_name"); } catch (Exception $e) {}
// Discount can be entered as a flat ₹ value or a % of subtotal -- the
// frontend always resolves it to a ₹ amount before sending (so the
// `discount` column above stays a plain ₹ figure and every existing
// total/report calculation needs no changes), but discount_type/
// discount_value are stored purely so editing an estimate later can
// redisplay "10%" instead of silently flattening it to a rupee amount.
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN discount_type VARCHAR(10) NOT NULL DEFAULT 'value' AFTER discount"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE invoices ADD COLUMN discount_value DECIMAL(10,2) DEFAULT NULL AFTER discount_type"); } catch (Exception $e) {}

try { $pdo->exec("CREATE TABLE IF NOT EXISTS customer_payments (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id INT UNSIGNED DEFAULT NULL, customer_name VARCHAR(200) DEFAULT '', amount DECIMAL(12,2) NOT NULL DEFAULT 0, payment_date DATE NOT NULL, payee_id INT UNSIGNED DEFAULT NULL, mode VARCHAR(20) NOT NULL DEFAULT 'account', reference_no VARCHAR(100) DEFAULT '', note VARCHAR(500) DEFAULT '', created_by INT UNSIGNED DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_order (order_id))"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE customer_payments ADD COLUMN invoice_id INT UNSIGNED DEFAULT NULL AFTER order_id"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE customer_payments ADD INDEX idx_invoice (invoice_id)"); } catch (Exception $e) {}

function validateInvoicePhone(string $raw): string {
    $digits = preg_replace('/[^0-9]/', '', $raw);
    if ($digits === '') return '';
    if (strlen($digits) < 10 || strlen($digits) > 13) jsonError('Enter a valid mobile number');
    return $digits;
}

// ── Print invoice (HTML) ─────────────────────────────────
if ($method==='GET' && !empty($_GET['print'])) {
    $inv = getFullInvoice($pdo,(int)$_GET['print']);
    if (!$inv) { http_response_code(404); echo 'Not found'; exit; }
    $biz = getAllSettings($pdo);
    if (($_GET['view'] ?? '') === 'pick') {
        outputInvoicePickHTML($inv,$biz);
    } else {
        outputInvoiceHTML($inv,$biz);
    }
    exit;
}

// ── GET list ─────────────────────────────────────────────
if ($method==='GET' && empty($_GET['id'])) {
    $where=['1=1']; $params=[];
    if (!empty($_GET['customer_id'])) { $where[]='i.customer_id=?'; $params[]=(int)$_GET['customer_id']; }
    if (!empty($_GET['location_id']))  { $where[]='i.location_id=?';  $params[]=(int)$_GET['location_id']; }
    if (!empty($_GET['status']))       { $where[]='i.status=?';        $params[]=$_GET['status']; }
    if (!empty($_GET['from']))         { $where[]='i.date>=?';         $params[]=$_GET['from']; }
    if (!empty($_GET['to']))           { $where[]='i.date<=?';         $params[]=$_GET['to']; }
    if (!empty($_GET['q'])) { $like='%'.$_GET['q'].'%'; $where[]='(i.invoice_number LIKE ? OR i.customer_name LIKE ?)'; $params[]=$like; $params[]=$like; }
    $sql="SELECT i.*,l.name AS location_name,
          (SELECT COUNT(*) FROM invoice_items ii WHERE ii.invoice_id=i.id) AS item_count
          FROM invoices i LEFT JOIN locations l ON l.id=i.location_id
          WHERE ".implode(' AND ',$where)." ORDER BY i.date DESC,i.id DESC LIMIT 500";
    $s=$pdo->prepare($sql); $s->execute($params); jsonList($s->fetchAll());
}

// ── GET single ───────────────────────────────────────────
if ($method==='GET' && !empty($_GET['id'])) {
    $inv = getFullInvoice($pdo,(int)$_GET['id']);
    if (!$inv) jsonError('Invoice not found',404);
    jsonOk($inv);
}

// ── POST create ──────────────────────────────────────────
if ($method==='POST') {
    $u = requireAuth();
    $b = getBody();
    requireFields($b,['items','date']);
    if (empty($b['items']) || !is_array($b['items'])) jsonError('Invoice must have at least one item');

    $pdo->beginTransaction();
    try {
        $biz        = getAllSettings($pdo);
        $taxRate    = (float)($b['tax_rate'] ?? $biz['tax_rate'] ?? 0);
        $discount   = (float)($b['discount'] ?? 0);
        $locId      = !empty($b['location_id']) ? (int)$b['location_id'] : getDefaultLocationId($pdo);
        $custId     = !empty($b['customer_id']) ? (int)$b['customer_id'] : null;
        $custName   = '';
        if ($custId) {
            $cn = $pdo->query("SELECT name FROM customers WHERE id=$custId")->fetchColumn();
            $custName = $cn ?: '';
        } else {
            $custName = trim($b['customer_name'] ?? '');
        }
        $custPhone = validateInvoicePhone((string)($b['customer_phone'] ?? ''));

        // Invoice number is finalized AFTER insert, from the row's own
        // auto-increment id (see below) -- NOT from COUNT(*), which was
        // the bug here: COUNT(*) drops the moment any invoice is
        // deleted, so the very next invoice created that day could
        // recompute a number that's already in use and hit invoices'
        // UNIQUE key on invoice_number. An id is never reused once
        // assigned, deleted or not, so basing the number on it can't
        // collide. $invNumPlaceholder just satisfies the NOT NULL/UNIQUE
        // column for the brief moment between INSERT and the follow-up
        // UPDATE a few lines down.
        $prefix = $biz['invoice_prefix'] ?? 'INV';
        $invNumPlaceholder = 'TMP-'.bin2hex(random_bytes(8));

        // Calculate totals
        $subtotal = 0;
        $lineItems = [];
        foreach ($b['items'] as $item) {
            if (empty($item['product_id']) || empty($item['qty'])) continue;
            $pid = (int)$item['product_id'];
            $qty = (int)$item['qty'];
            $p   = $pdo->query("SELECT id,name,cost,sell,stock,unit FROM products WHERE id=$pid FOR UPDATE")->fetch();
            if (!$p) throw new Exception("Product ID $pid not found");
            if ($qty <= 0) throw new Exception("Quantity must be greater than 0 for {$p['name']}");
            // Check available stock — use location stock if location set, else aggregate
            if ($locId) {
                $ls    = $pdo->query("SELECT stock FROM product_locations WHERE product_id=$pid AND location_id=$locId FOR UPDATE")->fetch();
                $avail = $ls ? (int)$ls['stock'] : 0;
            } else {
                $avail = (int)$p['stock'];
            }
            if ($avail <= 0) throw new Exception("'{$p['name']}' is out of stock (0 available)");
            if ($avail < $qty) throw new Exception("Insufficient stock for '{$p['name']}': only $avail {$p['unit']} available");
            $price = isset($item['unit_price']) && $item['unit_price']!=='' ? (float)$item['unit_price'] : (float)$p['sell'];
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;
            $lineItems[] = ['pid'=>$pid,'name'=>$p['name'],'qty'=>$qty,'price'=>$price,'cost'=>(float)$p['cost'],'total'=>$lineTotal];
        }
        if (!$lineItems) throw new Exception('No valid items');

        $subtotalAfterDiscount = max(0, $subtotal - $discount);
        $taxAmount = round($subtotalAfterDiscount * $taxRate / 100, 2);
        $packing   = round((float)($b['packing_charges']??0), 2);
        $misc      = round((float)($b['misc_charges']??0), 2);
        $total     = round($subtotalAfterDiscount + $taxAmount + $packing + $misc, 2);

        // Insert estimate -- status reflects whether the payment received at
        // creation already covers the total, not a fixed 'paid'.
        $amountReceivedIn = round((float)($b['amount_received']??0), 2);
        $payeeId = !empty($b['upi_payee_id']) ? (int)$b['upi_payee_id'] : null;
        $status  = deriveInvoiceStatus($amountReceivedIn, $total);
        // discount_type/discount_value are display-only (see ALTER guard
        // above) -- $discount itself is always already the resolved ₹
        // amount the frontend computed and used in $subtotalAfterDiscount.
        $discountType  = in_array($b['discount_type'] ?? '', ['value','percent']) ? $b['discount_type'] : 'value';
        $discountValue = isset($b['discount_value']) && $b['discount_value'] !== '' ? round((float)$b['discount_value'], 2) : null;
        $iStmt = $pdo->prepare("INSERT INTO invoices (invoice_number,customer_id,customer_name,customer_phone,location_id,subtotal,discount,discount_type,discount_value,tax_rate,tax_amount,packing_charges,misc_charges,total,payment_method,upi_payee_id,amount_received,status,notes,date,created_by)
                                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $iStmt->execute([$invNumPlaceholder,$custId,$custName,$custPhone,$locId,$subtotal,$discount,$discountType,$discountValue,$taxRate,$taxAmount,$packing,$misc,$total,
                         $b['payment_method']??'',$payeeId,$amountReceivedIn,$status,$b['notes']??'',$b['date'],$u['id']]);
        $invId  = (int)$pdo->lastInsertId();
        $invNum = $prefix.'-'.date('Ymd').'-'.str_pad($invId,4,'0',STR_PAD_LEFT);
        $pdo->prepare("UPDATE invoices SET invoice_number=? WHERE id=?")->execute([$invNum,$invId]);

        // Insert items + stock_out + deduct stock
        foreach ($lineItems as $li) {
            $pdo->prepare("INSERT INTO invoice_items (invoice_id,product_id,product_name,qty,unit_price,cost,total) VALUES (?,?,?,?,?,?,?)")
                ->execute([$invId,$li['pid'],$li['name'],$li['qty'],$li['price'],$li['cost'],$li['total']]);
            $pdo->prepare("INSERT INTO stock_out (product_id,location_id,invoice_id,qty,sell_price,cost,customer,date,created_by) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$li['pid'],$locId,$invId,$li['qty'],$li['price'],$li['cost'],$custName,$b['date'],$u['id']]);
            $pdo->exec("UPDATE products SET stock=stock-{$li['qty']} WHERE id={$li['pid']}");
            $pdo->exec("UPDATE product_locations SET stock=GREATEST(0,stock-{$li['qty']}) WHERE product_id={$li['pid']} AND location_id=$locId");
        }

        syncInvoicePaymentLedger($pdo,$invId,$invNum,$custName,$amountReceivedIn,$b['payment_method']??'',$payeeId,$b['date'],$u['id']);
        auditLog($pdo,'create_invoice','invoice',$invId,"Estimate $invNum total ₹$total");
        $pdo->commit();
        jsonOk(getFullInvoice($pdo,$invId), "Estimate $invNum created");
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonError($e->getMessage(), 500);
    }
}

// ── PUT update (full edit for draft estimates) ───────────
if ($method==='PUT') {
    $u = requireAuth();
    $b = getBody();
    requireFields($b,['id']);
    $id = (int)$b['id'];
    $inv = $pdo->query("SELECT * FROM invoices WHERE id=$id")->fetch();
    if (!$inv) jsonError('Not found',404);

    // Full edit only allowed on draft/paid (not cancelled)
    if (!empty($b['items']) && is_array($b['items'])) {
        $pdo->beginTransaction();
        try {
            $custId   = !empty($b['customer_id'])   ? (int)$b['customer_id']   : null;
            $custName = trim($b['customer_name']     ?? $inv['customer_name']   ?? 'Walk-in');
            $custPhone = array_key_exists('customer_phone',$b) ? validateInvoicePhone((string)$b['customer_phone']) : ($inv['customer_phone']??'');
            $locId    = !empty($b['location_id'])    ? (int)$b['location_id']   : (int)$inv['location_id'];
            $discount = round((float)($b['discount'] ?? $inv['discount']), 2);
            $taxRate  = round((float)($b['tax_rate'] ?? $inv['tax_rate']), 2);
            $packing  = round((float)($b['packing_charges'] ?? $inv['packing_charges']), 2);
            $misc     = round((float)($b['misc_charges'] ?? $inv['misc_charges']), 2);

            // Restore old stock
            $oldItems = $pdo->query("SELECT * FROM invoice_items WHERE invoice_id=$id")->fetchAll();
            foreach ($oldItems as $oi) {
                $pdo->exec("UPDATE products SET stock=stock+{$oi['qty']} WHERE id={$oi['product_id']}");
                $pdo->exec("UPDATE product_locations SET stock=stock+{$oi['qty']} WHERE product_id={$oi['product_id']} AND location_id={$inv['location_id']}");
            }
            $pdo->exec("DELETE FROM invoice_items WHERE invoice_id=$id");
            $pdo->exec("DELETE FROM stock_out WHERE invoice_id=$id");

            // Rebuild items
            $subtotal = 0;
            foreach ($b['items'] as $item) {
                $pid = (int)$item['product_id'];
                $qty = max(1, (int)$item['qty']);
                if ($qty <= 0) continue;
                $price = round((float)$item['unit_price'], 2);
                $p = $pdo->query("SELECT * FROM products WHERE id=$pid FOR UPDATE")->fetch();
                if (!$p) continue;
                // Stock check (stock was already restored above)
                if ($locId) {
                    $ls = $pdo->query("SELECT stock FROM product_locations WHERE product_id=$pid AND location_id=$locId")->fetch();
                    $avail = $ls ? (int)$ls['stock'] : 0;
                } else {
                    $avail = (int)$p['stock'];
                }
                if ($avail < $qty) throw new Exception("Insufficient stock for '{$p['name']}': only $avail available after restoring previous stock");
                $lineTotal = $qty * $price;
                $subtotal += $lineTotal;
                $pdo->prepare("INSERT INTO invoice_items (invoice_id,product_id,product_name,qty,unit_price,cost,total) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$id,$pid,$p['name'],$qty,$price,(float)$p['cost'],$lineTotal]);
                $pdo->prepare("INSERT INTO stock_out (product_id,location_id,invoice_id,qty,sell_price,cost,customer,date,created_by) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$pid,$locId,$id,$qty,$price,(float)$p['cost'],$custName,$b['date']??$inv['date'],$u['id']]);
                $pdo->exec("UPDATE products SET stock=stock-$qty WHERE id=$pid");
                $pdo->exec("UPDATE product_locations SET stock=GREATEST(0,stock-$qty) WHERE product_id=$pid AND location_id=$locId");
            }
            $afterDiscount = max(0, $subtotal - $discount);
            $taxAmount = round($afterDiscount * $taxRate / 100, 2);
            $total = round($afterDiscount + $taxAmount + $packing + $misc, 2);
            $amountReceivedIn = round((float)($b['amount_received']??$inv['amount_received']??0), 2);
            $payeeId = array_key_exists('upi_payee_id',$b) ? (!empty($b['upi_payee_id'])?(int)$b['upi_payee_id']:null) : (isset($inv['upi_payee_id'])?$inv['upi_payee_id']:null);
            $paymentMethod = $b['payment_method']??$inv['payment_method'];
            $newStatus = deriveInvoiceStatus($amountReceivedIn, $total, $inv['status']);
            $discountType  = array_key_exists('discount_type',$b) && in_array($b['discount_type'], ['value','percent']) ? $b['discount_type'] : ($inv['discount_type'] ?? 'value');
            $discountValue = array_key_exists('discount_value',$b) ? (($b['discount_value']!=='') ? round((float)$b['discount_value'],2) : null) : ($inv['discount_value'] ?? null);
            $pdo->prepare("UPDATE invoices SET customer_id=?,customer_name=?,customer_phone=?,location_id=?,date=?,payment_method=?,upi_payee_id=?,amount_received=?,subtotal=?,discount=?,discount_type=?,discount_value=?,tax_rate=?,tax_amount=?,packing_charges=?,misc_charges=?,total=?,notes=?,status=? WHERE id=?")
                ->execute([$custId,$custName,$custPhone,$locId,$b['date']??$inv['date'],$paymentMethod,$payeeId,
                           $amountReceivedIn,
                           $subtotal,$discount,$discountType,$discountValue,$taxRate,$taxAmount,$packing,$misc,$total,
                           $b['notes']??$inv['notes'],$newStatus,$id]);
            syncInvoicePaymentLedger($pdo,$id,$inv['invoice_number'],$custName,$amountReceivedIn,$paymentMethod,$payeeId,$b['date']??$inv['date'],$u['id']);
            // If picking had already started (or further) on this estimate
            // and an edit here just dropped it below fully-paid, flag it
            // instead of letting it silently vanish from the Estimates
            // Fulfillment board (see api/invoice_picking.php's GET list,
            // which surfaces pick_status='flagged' rows even though
            // they're not 'paid') -- mirrors Website Orders'
            // syncPickingStatusForOrder() flagging a payment shortfall
            // discovered mid-pick.
            if ($newStatus !== 'paid') {
                $pdo->exec("UPDATE invoices SET pick_status='flagged' WHERE id=$id AND pick_status NOT IN ('pending','flagged','dispatched')");
            }
            auditLog($pdo,'update_invoice','invoice',$id,"Updated estimate #".$inv['invoice_number']);
            $pdo->commit();
            jsonOk(getFullInvoice($pdo,$id),'Estimate updated');
        } catch(Exception $e){ $pdo->rollBack(); jsonError($e->getMessage(),500); }
    } else {
        // Simple status/notes update
        $pdo->prepare("UPDATE invoices SET status=?,notes=? WHERE id=?")
            ->execute([$b['status']??$inv['status'],$b['notes']??$inv['notes'],$id]);
        jsonOk(null,'Estimate updated');
    }
}

// ── DELETE cancel (default) / permanent delete (?hard=1) ─
if ($method==='DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    requireRole('admin','manager','partner');
    $id  = (int)($_GET['id']??0);
    $inv = getFullInvoice($pdo,$id);
    if (!$inv) jsonError('Not found',404);

    // Permanent delete -- only ever allowed on an estimate that has
    // already been cancelled (so stock was already restored back then,
    // and cancel is always a required first step before a real delete).
    if (!empty($_GET['hard'])) {
        if ($inv['status'] !== 'cancelled') jsonError('Cancel this estimate before deleting it');
        $pdo->beginTransaction();
        try {
            $pdo->exec("DELETE FROM stock_out WHERE invoice_id=$id");
            $pdo->exec("DELETE FROM invoice_items WHERE invoice_id=$id");
            $pdo->exec("DELETE FROM invoices WHERE id=$id");
            $pdo->commit();
            auditLog($pdo,'delete_invoice','invoice',$id,"Deleted estimate {$inv['invoice_number']}");
            jsonOk(null,'Estimate permanently deleted');
        } catch (PDOException $e) { $pdo->rollBack(); jsonError($e->getMessage(),500); }
    }

    if ($inv['status']==='cancelled') jsonError('Already cancelled');
    $pdo->beginTransaction();
    try {
        // Restore stock
        foreach ($inv['items'] as $item) {
            $pdo->exec("UPDATE products SET stock=stock+{$item['qty']} WHERE id={$item['product_id']}");
            if ($inv['location_id'])
                $pdo->exec("UPDATE product_locations SET stock=stock+{$item['qty']} WHERE product_id={$item['product_id']} AND location_id={$inv['location_id']}");
        }
        $pdo->exec("UPDATE stock_out SET note=CONCAT(COALESCE(note,''),' [CANCELLED]') WHERE invoice_id=$id");
        $pdo->exec("UPDATE invoices SET status='cancelled' WHERE id=$id");
        $pdo->commit();
        auditLog($pdo,'cancel_invoice','invoice',$id);
        jsonOk(null,'Invoice cancelled and stock restored');
    } catch (PDOException $e) { $pdo->rollBack(); jsonError($e->getMessage(),500); }
}

// ── Helpers ──────────────────────────────────────────────
// An estimate is 'paid' once the amount received covers the total, 'open'
// otherwise -- never auto-derived away from 'cancelled', since that's
// only ever set by the dedicated cancel action (see DELETE above).
function deriveInvoiceStatus(float $amountReceived, float $total, ?string $currentStatus = null): string {
    if ($currentStatus === 'cancelled') return 'cancelled';
    return $amountReceived >= $total ? 'paid' : 'open';
}
// Keeps a single customer_payments row (the same table/Payee Ledger the
// Website Orders payment flow uses) in sync with one estimate's current
// payment_method/amount_received/payee -- upserted in place rather than
// appended, since an Estimate only ever tracks one running
// amount_received figure, not a list of individual payments.
function syncInvoicePaymentLedger(PDO $pdo, int $invId, string $invNum, string $custName, float $amountReceived, string $paymentMethod, ?int $payeeId, string $date, ?int $createdBy): void {
    $existing = $pdo->query("SELECT id FROM customer_payments WHERE invoice_id=$invId")->fetchColumn();
    if ($amountReceived > 0 && $payeeId) {
        $mode = ($paymentMethod === 'cash') ? 'cash' : 'account';
        if ($existing) {
            $pdo->prepare("UPDATE customer_payments SET amount=?, payment_date=?, payee_id=?, mode=?, customer_name=? WHERE id=?")
                ->execute([$amountReceived, $date, $payeeId, $mode, $custName, $existing]);
        } else {
            $pdo->prepare("INSERT INTO customer_payments (invoice_id, customer_name, amount, payment_date, payee_id, mode, reference_no, note, created_by) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$invId, $custName, $amountReceived, $date, $payeeId, $mode, $invNum, '', $createdBy]);
        }
    } elseif ($existing) {
        // No payee chosen, or nothing received -- nothing meaningful to
        // show on the Payee Ledger for this estimate right now.
        $pdo->exec("DELETE FROM customer_payments WHERE id=$existing");
    }
}
function getFullInvoice(PDO $pdo, int $id): ?array {
    $inv = $pdo->query("SELECT i.*,l.name AS location_name,c.phone AS customer_phone_onfile,c.gst AS customer_gst,c.address AS customer_address
                        FROM invoices i LEFT JOIN locations l ON l.id=i.location_id LEFT JOIN customers c ON c.id=i.customer_id
                        WHERE i.id=$id")->fetch();
    if (!$inv) return null;
    $inv['items'] = $pdo->query("SELECT ii.*, p.sku AS product_sku FROM invoice_items ii LEFT JOIN products p ON p.id=ii.product_id WHERE ii.invoice_id=$id ORDER BY ii.id")->fetchAll();
    return $inv;
}
function getAllSettings(PDO $pdo): array {
    return $pdo->query("SELECT k,v FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
}
function getDefaultLocationId(PDO $pdo): ?int {
    $r = $pdo->query("SELECT id FROM locations WHERE is_default=1 LIMIT 1")->fetch();
    if (!$r) $r = $pdo->query("SELECT id FROM locations ORDER BY id LIMIT 1")->fetch();
    return $r ? (int)$r['id'] : null;
}
function outputInvoiceHTML(array $inv, array $biz): void {
    $sym  = $biz['currency_symbol'] ?? '₹';
    $items = $inv['items'];
    $rows  = '';
    foreach ($items as $it) {
        $code = $it['product_sku'] ? "<b style='font-size:11px;color:#555'>{$it['product_sku']}</b> " : '';
        $rows .= "<tr><td>{$code}{$it['product_name']}</td><td style='text-align:center'>{$it['qty']}</td><td style='text-align:right'>{$sym}".number_format($it['unit_price'],2)."</td><td style='text-align:right'>{$sym}".number_format($it['total'],2)."</td></tr>";
    }
    $discountLabel = (($inv['discount_type'] ?? 'value') === 'percent' && (float)($inv['discount_value'] ?? 0) > 0)
        ? 'Discount ('.rtrim(rtrim(number_format($inv['discount_value'],2),'0'),'.').'%)'
        : 'Discount';
    $discount   = (float)$inv['discount'] > 0 ? "<tr><td colspan='3' style='text-align:right;color:#666'>{$discountLabel}</td><td style='text-align:right;color:#e44'>-{$sym}".number_format($inv['discount'],2)."</td></tr>" : '';
    $tax        = (float)$inv['tax_rate'] > 0  ? "<tr><td colspan='3' style='text-align:right;color:#666'>Tax ({$inv['tax_rate']}%)</td><td style='text-align:right'>{$sym}".number_format($inv['tax_amount'],2)."</td></tr>" : '';
    $packing    = (float)($inv['packing_charges']??0) > 0 ? "<tr><td colspan='3' style='text-align:right;color:#666'>Packing</td><td style='text-align:right'>{$sym}".number_format($inv['packing_charges'],2)."</td></tr>" : '';
    $miscChg    = (float)($inv['misc_charges']??0) > 0 ? "<tr><td colspan='3' style='text-align:right;color:#666'>Misc. Charges</td><td style='text-align:right'>{$sym}".number_format($inv['misc_charges'],2)."</td></tr>" : '';
    $custAddr   = $inv['customer_address'] ? "<div style='color:#666;font-size:13px'>{$inv['customer_address']}</div>" : '';
    $custGst    = $inv['customer_gst']     ? "<div style='color:#666;font-size:13px'>GST: {$inv['customer_gst']}</div>" : '';
    $custPhone  = $inv['customer_phone']   ? "<div style='color:#666;font-size:13px'>{$inv['customer_phone']}</div>" : '';
    $bizGst     = $biz['business_gst']     ? "<div style='color:#666;font-size:13px'>GST: {$biz['business_gst']}</div>" : '';
    $notes      = $inv['notes']            ? "<div style='background:#fffbe6;border-radius:6px;padding:12px;font-size:13px;color:#666'><strong>Notes:</strong> {$inv['notes']}</div>" : '';
    $payMethod  = ucfirst($inv['payment_method'] ?? '');
    echo <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Invoice {$inv['invoice_number']}</title>
<style>
 body{font-family:Arial,sans-serif;color:#222;margin:0;padding:32px;font-size:14px}
 .header{display:flex;justify-content:space-between;margin-bottom:32px}
 .biz-name{font-size:22px;font-weight:700;color:#1a1a2e}
 .inv-title{font-size:28px;font-weight:800;color:#4f8eff;letter-spacing:-1px}
 .inv-meta{margin-top:6px;color:#666;font-size:13px}
 table{width:100%;border-collapse:collapse;margin:24px 0}
 th{background:#f0f4ff;padding:10px 12px;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#555}
 td{padding:10px 12px;border-bottom:1px solid #eee}
 .totals-row td{border:none}
 .total-row td{font-weight:700;font-size:16px;border-top:2px solid #222;padding-top:12px}
 .footer{margin-top:40px;padding-top:16px;border-top:1px solid #eee;color:#999;font-size:12px;text-align:center}
 @media print{body{padding:0}.no-print{display:none}}
</style></head><body>
<div class="no-print" style="margin-bottom:20px">
  <button onclick="window.print()" style="background:#4f8eff;color:#fff;border:none;padding:10px 24px;border-radius:6px;cursor:pointer;font-size:14px">🖨️ Print / Save PDF</button>
  <button onclick="window.close()" style="background:#eee;border:none;padding:10px 24px;border-radius:6px;cursor:pointer;font-size:14px;margin-left:8px">Close</button>
</div>
<div class="header">
  <div>
    <div class="biz-name">{$biz['business_name']}</div>
    <div style="color:#666;font-size:13px;margin-top:4px">{$biz['business_address']}</div>
    <div style="color:#666;font-size:13px">{$biz['business_phone']} {$biz['business_email']}</div>
    $bizGst
  </div>
  <div style="text-align:right">
    <div class="inv-title">ESTIMATE</div>
    <div class="inv-meta"><strong>{$inv['invoice_number']}</strong></div>
    <div class="inv-meta">Date: {$inv['date']}</div>
    <div class="inv-meta">Payment: $payMethod</div>
    <div class="inv-meta">Location: {$inv['location_name']}</div>
  </div>
</div>
<div style="background:#f8faff;border-radius:8px;padding:16px;margin-bottom:8px">
  <div style="font-size:12px;text-transform:uppercase;letter-spacing:.8px;color:#888;margin-bottom:4px">Bill To</div>
  <div style="font-weight:600;font-size:15px">{$inv['customer_name']}</div>
  $custAddr$custGst$custPhone
</div>
<table><thead><tr><th>Item</th><th style='text-align:center'>Qty</th><th style='text-align:right'>Unit Price</th><th style='text-align:right'>Total</th></tr></thead>
<tbody>$rows</tbody>
<tfoot>
  $discount$tax$packing$miscChg
  <tr class='total-row'><td colspan='3' style='text-align:right'>TOTAL</td><td style='text-align:right'>{$sym}
HTML;
    echo number_format($inv['total'],2);
    echo <<<HTML
</td></tr>
</tfoot></table>
$notes
<div class='footer'>Thank you for your business!</div>
</body></html>
HTML;
}

// Picker/verifier print -- a plain checklist (item code, name, qty, a
// checkbox to mark it picked and a second to mark it verified), the same
// idea as the Fulfillment module's Picking/Checking sheet, but rendered
// straight from an estimate's own data since Estimates don't share that
// module's picking_sessions table. Deliberately carries no prices --
// this copy is for whoever is physically gathering/checking the order,
// not the customer.
function outputInvoicePickHTML(array $inv, array $biz): void {
    $items = $inv['items'];
    $now   = date('d M Y, h:i A');
    $rows  = '';
    $i = 0;
    foreach ($items as $it) {
        $i++;
        $code = $it['product_sku'] ? "<b style='font-size:10px;color:#555'>".htmlspecialchars($it['product_sku'])."</b> " : '';
        $name = htmlspecialchars($it['product_name']);
        $rows .= "<tr style='border-bottom:1px solid #eee'><td style='text-align:center;font-size:11px;color:#666'>{$i}</td>"
               . "<td>{$code}{$name}</td>"
               . "<td style='text-align:center;font-weight:700'>{$it['qty']}</td>"
               . "<td style='text-align:center'><input type='checkbox'></td>"
               . "<td style='text-align:center'><input type='checkbox'></td></tr>";
    }
    $custAddr = $inv['customer_address'] ? "<div class='addr'><b style='font-size:10px;color:#556;display:block'>ADDRESS</b>".htmlspecialchars($inv['customer_address'])."</div>" : '';
    $phone    = $inv['customer_phone'] ?: '--';
    $custNameEsc = htmlspecialchars($inv['customer_name']);
    echo <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Picking Sheet - {$inv['invoice_number']}</title>
<style>
 body{font-family:Arial,sans-serif;font-size:13px;padding:14px}
 .hdr{display:flex;justify-content:space-between;border-bottom:2px solid #333;padding-bottom:8px;margin-bottom:10px}
 .meta{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;background:#f8f8f8;padding:8px 12px;border-radius:4px;margin-bottom:8px;font-size:12px}
 .meta b{display:block;font-size:10px;color:#888;font-weight:400}
 .addr{background:#e8f0ff;padding:6px 12px;border-radius:4px;margin-bottom:8px;font-size:12px}
 table{width:100%;border-collapse:collapse;margin-bottom:10px}
 th{background:#333;color:#fff;padding:6px 8px;text-align:left;font-size:10px}
 td{padding:6px 8px;vertical-align:middle}
 .sign{display:flex;gap:40px;margin-top:18px}
 .sign-box{flex:1;border-top:1px solid #999;padding-top:6px;font-size:10px;color:#666;text-align:center}
 @media print{button{display:none}}
</style></head><body>
<div class="no-print" style="margin-bottom:20px">
  <button onclick="window.print()" style="background:#4f8eff;color:#fff;border:none;padding:10px 24px;border-radius:6px;cursor:pointer;font-size:14px">Print</button>
  <button onclick="window.close()" style="background:#eee;border:none;padding:10px 24px;border-radius:6px;cursor:pointer;font-size:14px;margin-left:8px">Close</button>
</div>
<div class="hdr"><div><h1 style="font-size:16px">Picking Sheet</h1><div style="font-size:10px;color:#666">{$biz['business_name']}</div></div>
<div style="text-align:right;font-size:10px;color:#666">Printed: {$now}</div></div>
<div class="meta"><div><b>Estimate</b>{$inv['invoice_number']}</div><div><b>Customer</b>{$custNameEsc}</div><div><b>Phone</b>{$phone}</div></div>
{$custAddr}
<table><thead><tr><th style="width:30px">#</th><th>Product</th><th style="width:50px;text-align:center">Qty</th><th style="width:70px;text-align:center">Picked</th><th style="width:70px;text-align:center">Verified</th></tr></thead>
<tbody>{$rows}</tbody></table>
<div class="sign"><div class="sign-box">Picker</div><div class="sign-box">Verifier</div></div>
<script>window.onload=function(){window.print();};</script>
</body></html>
HTML;
}
