<?php
/**
 * Invyrr API — Customer Payments
 * GET    /api/customer_payments.php?order_id=N   → payments for one order
 * GET    /api/customer_payments.php?report=1     → flat list (filters: from,to,mode,payee_id)
 * POST   /api/customer_payments.php              → record a payment (admin only)
 * PUT    /api/customer_payments.php              → edit a payment (admin only)
 * DELETE /api/customer_payments.php?id=N         → delete a payment (admin only)
 */
require __DIR__ . '/../includes/db.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

startSession(); requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();
$u      = currentUser();

// ── GET ────────────────────────────────────────────────────
if ($method === 'GET') {
    // Single payment fetch by ?id=N (for edit modal)
    if (!empty($_GET['id']) && !isset($_GET['order_id'])) {
        $s = $pdo->prepare("SELECT * FROM customer_payments WHERE id=?");
        $s->execute([(int)$_GET['id']]);
        $row = $s->fetch();
        if (!$row) jsonError('Payment not found', 404);
        jsonOk($row);
    }

    // Flat report — all payments with filters
    if (isset($_GET['report'])) {
        $where = ['1=1']; $params = [];
        if (!empty($_GET['from']))     { $where[] = 'cp.payment_date >= ?'; $params[] = $_GET['from']; }
        if (!empty($_GET['to']))       { $where[] = 'cp.payment_date <= ?'; $params[] = $_GET['to']; }
        if (!empty($_GET['mode']))     { $where[] = 'cp.mode = ?';          $params[] = $_GET['mode']; }
        if (!empty($_GET['payee_id'])) { $where[] = 'cp.payee_id = ?';      $params[] = (int)$_GET['payee_id']; }
        $sql = "SELECT cp.*, wo.order_number, wo.customer_name AS order_customer,
                       pa.name AS payee_name, pa.type AS payee_type
                FROM customer_payments cp
                LEFT JOIN website_orders wo ON wo.id = cp.order_id
                LEFT JOIN payees pa ON pa.id = cp.payee_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY cp.payment_date DESC, cp.id DESC LIMIT 2000";
        $s = $pdo->prepare($sql); $s->execute($params);
        jsonList($s->fetchAll());
    }

    // Payments for one order
    $orderId = (int)($_GET['order_id'] ?? 0);
    if (!$orderId) jsonError('order_id required');
    $order = $pdo->query("SELECT * FROM website_orders WHERE id=$orderId")->fetch();
    if (!$order) jsonError('Order not found', 404);

    $p = $pdo->prepare("SELECT cp.*, pa.name AS payee_name, pa.type AS payee_type
                         FROM customer_payments cp LEFT JOIN payees pa ON pa.id = cp.payee_id
                         WHERE cp.order_id = ? ORDER BY cp.payment_date DESC, cp.id DESC");
    $p->execute([$orderId]);
    $payments = $p->fetchAll();
    $paid = array_sum(array_column($payments, 'amount'));
    jsonOk([
        'order'    => $order,
        'payments' => $payments,
        'summary'  => [
            'amount'      => round((float)$order['amount'], 2),
            'amount_paid' => round($paid, 2),
            'balance'     => round((float)$order['amount'] - $paid, 2),
        ],
    ]);
}

// ── POST: record a payment (admin or Cashier) ─────────────
if ($method === 'POST') {
    requireRole('admin', 'Cashier');
    $b = getBody();
    requireFields($b, ['order_id', 'amount', 'payment_date', 'payee_id']);
    $orderId = (int)$b['order_id'];
    $order = $pdo->query("SELECT * FROM website_orders WHERE id=$orderId")->fetch();
    if (!$order) jsonError('Order not found', 404);

    $mode = in_array($b['mode'] ?? '', ['account','cash']) ? $b['mode'] : 'account';

    $pdo->prepare("INSERT INTO customer_payments
        (order_id, customer_name, amount, payment_date, payee_id, mode, reference_no, note, created_by)
        VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([
            $orderId,
            $order['customer_name'],
            round((float)$b['amount'], 2),
            $b['payment_date'],
            !empty($b['payee_id']) ? (int)$b['payee_id'] : null,
            $mode,
            // Default to the order number when the client doesn't send a
            // reference (it never has -- the payment modal has no
            // Reference input) so every payment is tagged with its
            // estimate # for tracking in the Payee Ledger's Reference
            // column and any export, without needing a UI change.
            trim($b['reference_no'] ?? '') !== '' ? trim($b['reference_no']) : $order['order_number'],
            trim($b['note'] ?? ''),
            $u['id'] ?? null,
        ]);
    $id = (int)$pdo->lastInsertId();

    // Auto-update order status based on new total paid
    $totalPaid = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM customer_payments WHERE order_id=$orderId")->fetchColumn();
    $newStatus = $order['status'];
    if ($totalPaid <= 0)                         { $newStatus = 'Pending'; }
    elseif ($totalPaid >= (float)$order['amount']) { $newStatus = 'Paid'; }
    else                                          { $newStatus = 'Partial'; }
    if ($newStatus !== $order['status'] && !in_array($order['status'], ['Cancelled'])) {
        $pdo->prepare("UPDATE website_orders SET status=? WHERE id=?")->execute([$newStatus, $orderId]);
    }
    syncPickingStatusForOrder($pdo, $order['order_number'], $newStatus === 'Paid');

    auditLog($pdo, 'customer_payment', 'website_order', $orderId, "Payment ₹".$b['amount']." (".$mode.") for ".$order['order_number']);
    jsonOk(['id' => $id], 'Payment recorded');
}

// ── PUT (edit, admin only) ────────────────────────────────
if ($method === 'PUT') {
    if (!$u || $u['role'] !== 'admin') jsonError('Admin access required', 403);
    $b = getBody();
    requireFields($b, ['id','amount','payment_date','payee_id']);
    $id = (int)$b['id'];
    $row = $pdo->query("SELECT * FROM customer_payments WHERE id=$id")->fetch();
    if (!$row) jsonError('Payment not found', 404);

    $mode = in_array($b['mode'] ?? '', ['account','cash']) ? $b['mode'] : $row['mode'];
    $pdo->prepare("UPDATE customer_payments SET amount=?, payment_date=?, payee_id=?, mode=?, reference_no=?, note=? WHERE id=?")
        ->execute([
            round((float)$b['amount'], 2),
            $b['payment_date'],
            !empty($b['payee_id']) ? (int)$b['payee_id'] : null,
            $mode,
            trim($b['reference_no'] ?? $row['reference_no']),
            trim($b['note'] ?? $row['note']),
            $id,
        ]);

    if ($row['order_id']) {
        $order = $pdo->query("SELECT * FROM website_orders WHERE id={$row['order_id']}")->fetch();
        if ($order) {
            $totalPaid = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM customer_payments WHERE order_id={$row['order_id']}")->fetchColumn();
            $newStatus = $totalPaid <= 0 ? 'Pending' : ($totalPaid >= (float)$order['amount'] ? 'Paid' : 'Partial');
            if (!in_array($order['status'], ['Cancelled'])) {
                $pdo->prepare("UPDATE website_orders SET status=? WHERE id=?")->execute([$newStatus, $row['order_id']]);
            }
            syncPickingStatusForOrder($pdo, $order['order_number'], $newStatus === 'Paid');
        }
    }

    $orderNoForLog = isset($order['order_number']) ? $order['order_number'] : ($row['order_id'] ? '?' : '—');
    auditLog($pdo, 'update_customer_payment', 'customer_payment', $id, "Edited payment #{$id}: amount={$b['amount']} for {$orderNoForLog}");
    jsonOk(null, 'Payment updated');
}

// ── DELETE ─────────────────────────────────────────────────
if ($method === 'DELETE') {
    requireRole('admin');
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $row = $pdo->query("SELECT * FROM customer_payments WHERE id=$id")->fetch();
    if (!$row) jsonError('Not found', 404);
    $pdo->prepare("DELETE FROM customer_payments WHERE id=?")->execute([$id]);

    if ($row['order_id']) {
        $order = $pdo->query("SELECT * FROM website_orders WHERE id={$row['order_id']}")->fetch();
        if ($order) {
            $totalPaid = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM customer_payments WHERE order_id={$row['order_id']}")->fetchColumn();
            $newStatus = $totalPaid <= 0 ? 'Pending' : ($totalPaid >= (float)$order['amount'] ? 'Paid' : 'Partial');
            if (!in_array($order['status'], ['Cancelled'])) {
                $pdo->prepare("UPDATE website_orders SET status=? WHERE id=?")->execute([$newStatus, $row['order_id']]);
            }
            syncPickingStatusForOrder($pdo, $order['order_number'], $newStatus === 'Paid');
        }
    }

    $orderNoForLog = isset($order['order_number']) ? $order['order_number'] : ($row['order_id'] ? '?' : '—');
    auditLog($pdo, 'delete_customer_payment', 'customer_payment', $id, "Deleted ₹".$row['amount']." for {$orderNoForLog}");
    jsonOk(null, 'Payment deleted');
}
