<?php
/**
 * Invyrr API — Website / Customer Orders
 * Tracks frontend/website orders and their payment + dispatch status,
 * replacing the manual "order tracking" Excel sheet.
 *
 * GET    /api/website_orders.php                → list (?q=&status=&dispatch_status=&from=&to=)
 * GET    /api/website_orders.php?id=N            → single order + payments
 * POST   /api/website_orders.php                 → create
 * PUT    /api/website_orders.php                 → update
 * DELETE /api/website_orders.php?id=N            → delete
 */
require __DIR__ . '/../includes/db.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

startSession(); requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

function ensureWebsiteOrderTables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS website_orders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(50) NOT NULL,
        order_type VARCHAR(50) NOT NULL DEFAULT 'Frontend Order',
        order_date DATE NOT NULL,
        customer_name VARCHAR(200) NOT NULL DEFAULT '',
        city VARCHAR(100) DEFAULT '',
        mobile VARCHAR(30) DEFAULT '',
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        status VARCHAR(30) NOT NULL DEFAULT 'Pending',
        dispatch_status VARCHAR(50) DEFAULT '',
        dispatch_date DATE NULL,
        transport VARCHAR(150) DEFAULT '',
        num_boxes INT DEFAULT 0,
        gift VARCHAR(150) DEFAULT '',
        comments TEXT NULL,
        created_by INT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_order_number (order_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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

    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_payments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT UNSIGNED DEFAULT NULL,
        customer_name VARCHAR(200) DEFAULT '',
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        payment_date DATE NOT NULL,
        payee_id INT UNSIGNED DEFAULT NULL,
        mode VARCHAR(20) NOT NULL DEFAULT 'account',
        reference_no VARCHAR(100) DEFAULT '',
        note VARCHAR(500) DEFAULT '',
        created_by INT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
ensureWebsiteOrderTables($pdo);

// ── GET single (with payments) ────────────────────────────
if ($method === 'GET' && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $o = $pdo->prepare("SELECT * FROM website_orders WHERE id=?");
    $o->execute([$id]);
    $order = $o->fetch();
    if (!$order) jsonError('Order not found', 404);

    $p = $pdo->prepare("SELECT cp.*, pa.name AS payee_name, pa.type AS payee_type
                         FROM customer_payments cp LEFT JOIN payees pa ON pa.id = cp.payee_id
                         WHERE cp.order_id = ? ORDER BY cp.payment_date DESC, cp.id DESC");
    $p->execute([$id]);
    $order['payments'] = $p->fetchAll();
    $paid = array_sum(array_column($order['payments'], 'amount'));
    $order['amount_paid'] = round($paid, 2);
    $order['balance']     = round((float)$order['amount'] - $paid, 2);
    jsonOk($order);
}

// ── GET list ───────────────────────────────────────────────
if ($method === 'GET') {
    $where = ['1=1']; $params = [];
    if (!empty($_GET['q'])) {
        $like = '%'.$_GET['q'].'%';
        $where[] = '(order_number LIKE ? OR customer_name LIKE ? OR mobile LIKE ? OR city LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    if (!empty($_GET['status']))          { $where[] = 'status = ?';          $params[] = $_GET['status']; }
    if (!empty($_GET['dispatch_status'])) { $where[] = 'dispatch_status = ?'; $params[] = $_GET['dispatch_status']; }
    if (!empty($_GET['from']))            { $where[] = 'order_date >= ?';     $params[] = $_GET['from']; }
    if (!empty($_GET['to']))              { $where[] = 'order_date <= ?';     $params[] = $_GET['to']; }

    $sql = "SELECT wo.*,
                   COALESCE((SELECT SUM(amount) FROM customer_payments WHERE order_id = wo.id), 0) AS amount_paid
            FROM website_orders wo
            WHERE " . implode(' AND ', $where) . "
            ORDER BY wo.order_date DESC, wo.id DESC LIMIT 2000";
    $s = $pdo->prepare($sql); $s->execute($params);
    $rows = $s->fetchAll();
    foreach ($rows as &$r) {
        $r['amount_paid'] = round((float)$r['amount_paid'], 2);
        $r['balance']     = round((float)$r['amount'] - (float)$r['amount_paid'], 2);
    }
    unset($r);
    jsonList($rows);
}

// ── POST create (upsert by Order Number) ────────────────────
// Duplicate Order Number is never treated as an error here — the Picking
// dashboard calls this endpoint to silently keep a website_orders record
// in sync (order total, dispatch snapshot, gift) every time it's opened,
// so re-posting the same order number must update the existing row rather
// than fail. Fields not present in the request body are left untouched,
// same merge behavior as PUT, so a sync call never clobbers a status or
// field edited elsewhere (e.g. via a payment already recorded).
if ($method === 'POST') {
    requireRole('admin','manager','partner');
    $b = getBody();
    requireFields($b, ['order_number','order_date','amount']);
    $orderNumber = trim($b['order_number']);

    $existing = $pdo->prepare("SELECT * FROM website_orders WHERE order_number=?");
    $existing->execute([$orderNumber]);
    $cur = $existing->fetch();

    if ($cur) {
        $stmt = $pdo->prepare("UPDATE website_orders SET
            order_type=?, order_date=?, customer_name=?, city=?, mobile=?, amount=?, status=?,
            dispatch_status=?, dispatch_date=?, transport=?, num_boxes=?, gift=?, comments=? WHERE id=?");
        $stmt->execute([
            trim($b['order_type'] ?? $cur['order_type']),
            $b['order_date'] ?? $cur['order_date'],
            trim($b['customer_name'] ?? $cur['customer_name']),
            trim($b['city'] ?? $cur['city']),
            trim($b['mobile'] ?? $cur['mobile']),
            round((float)($b['amount'] ?? $cur['amount']), 2),
            trim($b['status'] ?? $cur['status']),
            array_key_exists('dispatch_status', $b) ? trim($b['dispatch_status']) : $cur['dispatch_status'],
            array_key_exists('dispatch_date', $b) ? (!empty($b['dispatch_date']) ? $b['dispatch_date'] : null) : $cur['dispatch_date'],
            array_key_exists('transport', $b) ? trim($b['transport']) : $cur['transport'],
            array_key_exists('num_boxes', $b) ? (int)$b['num_boxes'] : $cur['num_boxes'],
            array_key_exists('gift', $b) ? trim($b['gift']) : $cur['gift'],
            array_key_exists('comments', $b) ? trim($b['comments']) : $cur['comments'],
            $cur['id'],
        ]);
        jsonOk(['id' => (int)$cur['id']], 'Order synced');
    }

    $stmt = $pdo->prepare("INSERT INTO website_orders
        (order_number, order_type, order_date, customer_name, city, mobile, amount, status,
         dispatch_status, dispatch_date, transport, num_boxes, gift, comments, created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $orderNumber,
        trim($b['order_type'] ?? 'Frontend Order'),
        $b['order_date'],
        trim($b['customer_name'] ?? ''),
        trim($b['city'] ?? ''),
        trim($b['mobile'] ?? ''),
        round((float)$b['amount'], 2),
        trim($b['status'] ?? 'Pending'),
        trim($b['dispatch_status'] ?? ''),
        !empty($b['dispatch_date']) ? $b['dispatch_date'] : null,
        trim($b['transport'] ?? ''),
        (int)($b['num_boxes'] ?? 0),
        trim($b['gift'] ?? ''),
        trim($b['comments'] ?? ''),
        currentUser()['id'] ?? null,
    ]);
    $id = (int)$pdo->lastInsertId();
    auditLog($pdo, 'create_website_order', 'website_order', $id, $orderNumber);
    jsonOk(['id' => $id], 'Order created');
}

// ── PUT update ─────────────────────────────────────────────
if ($method === 'PUT') {
    requireRole('admin','manager','partner');
    $b = getBody();
    requireFields($b, ['id']);
    $id = (int)$b['id'];
    $existing = $pdo->prepare("SELECT * FROM website_orders WHERE id=?");
    $existing->execute([$id]);
    $cur = $existing->fetch();
    if (!$cur) jsonError('Order not found', 404);

    $stmt = $pdo->prepare("UPDATE website_orders SET
        order_number=?, order_type=?, order_date=?, customer_name=?, city=?, mobile=?, amount=?, status=?,
        dispatch_status=?, dispatch_date=?, transport=?, num_boxes=?, gift=?, comments=? WHERE id=?");
    $stmt->execute([
        trim($b['order_number'] ?? $cur['order_number']),
        trim($b['order_type']   ?? $cur['order_type']),
        $b['order_date']        ?? $cur['order_date'],
        trim($b['customer_name'] ?? $cur['customer_name']),
        trim($b['city']         ?? $cur['city']),
        trim($b['mobile']       ?? $cur['mobile']),
        isset($b['amount']) ? round((float)$b['amount'], 2) : $cur['amount'],
        trim($b['status']       ?? $cur['status']),
        array_key_exists('dispatch_status', $b) ? trim($b['dispatch_status']) : $cur['dispatch_status'],
        array_key_exists('dispatch_date', $b) ? (!empty($b['dispatch_date']) ? $b['dispatch_date'] : null) : $cur['dispatch_date'],
        array_key_exists('transport', $b) ? trim($b['transport']) : $cur['transport'],
        array_key_exists('num_boxes', $b) ? (int)$b['num_boxes'] : $cur['num_boxes'],
        array_key_exists('gift', $b) ? trim($b['gift']) : $cur['gift'],
        array_key_exists('comments', $b) ? trim($b['comments']) : $cur['comments'],
        $id,
    ]);
    auditLog($pdo, 'update_website_order', 'website_order', $id, trim($b['order_number'] ?? $cur['order_number']));
    jsonOk(null, 'Order updated');
}

// ── DELETE ─────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    requireRole('admin');
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $row = $pdo->query("SELECT order_number FROM website_orders WHERE id=$id")->fetch();
    if (!$row) jsonError('Order not found', 404);
    $pdo->beginTransaction();
    try {
        $pdo->exec("DELETE FROM customer_payments WHERE order_id=$id");
        $pdo->exec("DELETE FROM website_orders WHERE id=$id");
        $pdo->commit();
        auditLog($pdo, 'delete_website_order', 'website_order', $id, $row['order_number']);
        jsonOk(null, 'Order deleted');
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonError($e->getMessage(), 500);
    }
}
