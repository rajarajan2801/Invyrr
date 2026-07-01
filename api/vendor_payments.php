<?php
/**
 * Invyrr API — Vendor Payments
 * GET    /api/vendor_payments.php?vendor_id=N   → ledger for a vendor
 * GET    /api/vendor_payments.php?summary=1     → all vendors with balance
 * POST   /api/vendor_payments.php               → record payment / credit / opening balance
 * DELETE /api/vendor_payments.php?id=N          → delete entry
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../includes/db.php';
startSession(); requireAuth();

// ── Ensure tables exist (graceful first-run) ─────────────
function ensureTables(PDO $pdo): void {
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
    $pdo->exec("CREATE TABLE IF NOT EXISTS vendor_payments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        vendor_id INT UNSIGNED NOT NULL,
        payee_id INT UNSIGNED DEFAULT NULL,
        amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        
        reference_no VARCHAR(100) DEFAULT '',
        payment_date DATE NOT NULL,
        notes VARCHAR(500) DEFAULT '',
        type VARCHAR(20) NOT NULL DEFAULT 'payment',
        created_by INT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$pdo = getDB();
ensureTables($pdo);

$method = $_SERVER['REQUEST_METHOD'];
$u      = currentUser();

// ── GET: vendor ledger or summary ────────────────────────
if ($method === 'GET') {
    requireAuth();

    // Single payment fetch by ?id=N (for edit modal)
    if (!empty($_GET['id']) && !isset($_GET['vendor_id'])) {
        $row = $pdo->prepare("SELECT id, vendor_id, amount, payment_date, type, reference_no, notes, payee_id FROM vendor_payments WHERE id=?");
        $row->execute([(int)$_GET['id']]);
        $p = $row->fetch();
        if (!$p) jsonError('Payment not found', 404);
        jsonOk($p);
    }

    // Flat list for VP Report — all payments across all vendors with optional filters
    if (isset($_GET['report'])) {
        $where = ['1=1'];
        $params = [];
        if (!empty($_GET['from'])) { $where[] = 'vp.payment_date >= ?'; $params[] = $_GET['from']; }
        if (!empty($_GET['to']))   { $where[] = 'vp.payment_date <= ?'; $params[] = $_GET['to']; }
        if (!empty($_GET['type'])) { $where[] = 'vp.type = ?'; $params[] = $_GET['type']; }
        $sql = "SELECT vp.id, vp.payment_date, vp.amount, vp.type,
                       vp.reference_no, vp.notes AS description,
                       v.name AS vendor_name,
                       p.name AS payee_name, p.type AS payee_type
                FROM vendor_payments vp
                LEFT JOIN vendors v ON v.id = vp.vendor_id
                LEFT JOIN payees p  ON p.id = vp.payee_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY vp.payment_date DESC, vp.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonList($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Summary: all vendors with purchase total, payments total, balance
    if (isset($_GET['summary'])) {
        try {
            $rows = $pdo->query("
                SELECT v.id, v.name, v.type, v.phone,
                    COALESCE((
                        SELECT SUM(poi.qty_ordered * poi.cost)
                        FROM purchase_orders po
                        JOIN purchase_order_items poi ON poi.po_id = po.id
                        WHERE po.vendor_id = v.id AND po.status IN ('received','partial')
                    ),0) + COALESCE((
                        SELECT SUM(amount) FROM vendor_payments
                        WHERE vendor_id = v.id AND type='manual_purchase'
                    ),0) AS total_purchases,
                    COALESCE((SELECT SUM(amount) FROM vendor_payments WHERE vendor_id = v.id AND type='payment'),0) AS total_paid,
                    COALESCE((SELECT SUM(amount) FROM vendor_payments WHERE vendor_id = v.id AND type='credit_note'),0) AS total_credits,
                    COALESCE((SELECT SUM(amount) FROM vendor_payments WHERE vendor_id = v.id AND type='opening_balance'),0) AS opening_balance,
                    (SELECT MAX(payment_date) FROM vendor_payments WHERE vendor_id = v.id AND type='payment') AS last_payment_date
                FROM vendors v ORDER BY v.name
            ")->fetchAll();
            foreach ($rows as &$r) {
                $r['balance'] = round((float)$r['opening_balance'] + (float)$r['total_purchases'] - (float)$r['total_paid'] - (float)$r['total_credits'], 2);
            }
            unset($r);
            jsonList($rows);
        } catch (PDOException $e) {
            jsonError('Query error: ' . $e->getMessage(), 500);
        }
    }

    // Ledger for a specific vendor
    $vendorId = (int)($_GET['vendor_id'] ?? 0);
    if (!$vendorId) jsonError('vendor_id required');

    $vendor = $pdo->query("SELECT * FROM vendors WHERE id=$vendorId")->fetch();
    if (!$vendor) jsonError('Vendor not found', 404);

    // Purchase lines from received/partial POs
    $purchases = $pdo->query("
        SELECT po.id AS po_id, po.po_number, po.expected_date AS txn_date,
               SUM(poi.qty_ordered * poi.cost) AS amount,
               'purchase' AS type, po.status,
               CONCAT('PO ', po.po_number, ' — ', COUNT(poi.id), ' item(s)') AS description
        FROM purchase_orders po
        JOIN purchase_order_items poi ON poi.po_id = po.id
        WHERE po.vendor_id = $vendorId AND po.status IN ('received','partial')
        GROUP BY po.id ORDER BY po.expected_date DESC, po.id DESC
    ")->fetchAll();

    // Payments, credits, opening balances
    $payments = $pdo->query("
        SELECT vp.id, vp.payment_date AS txn_date, vp.amount, vp.type,
               vp.reference_no, vp.notes AS description,
               p.name AS payee_name, p.type AS payee_type,
               p.bank_name AS payee_bank, p.account_no AS payee_account
        FROM vendor_payments vp
        LEFT JOIN payees p ON p.id = vp.payee_id
        WHERE vp.vendor_id = $vendorId
        ORDER BY vp.payment_date DESC, vp.id DESC
    ")->fetchAll();

    // Running balance (opening → purchases → payments/credits)
    $opening   = array_sum(array_column(array_filter($payments, function($p){ return $p['type']==='opening_balance'; }), 'amount'));
    $purchased = array_sum(array_column($purchases, 'amount'));
    $manual    = array_sum(array_column(array_filter($payments, function($p){ return $p['type']==='manual_purchase'; }), 'amount'));
    $paid      = array_sum(array_column(array_filter($payments, function($p){ return $p['type']==='payment'; }), 'amount'));
    $credits   = array_sum(array_column(array_filter($payments, function($p){ return $p['type']==='credit_note'; }), 'amount'));

    jsonOk([
        'vendor'   => $vendor,
        'summary'  => [
            'opening_balance' => round($opening, 2),
            'total_purchases' => round($purchased + $manual, 2),
            'total_paid'      => round($paid, 2),
            'total_credits'   => round($credits, 2),
            'balance'         => round($opening + $purchased + $manual - $paid - $credits, 2),
        ],
        'purchases' => $purchases,
        'payments'  => $payments,
    ]);
}

// ── POST: record a payment / credit / opening balance ────
if ($method === 'POST') {
    $b = getBody();
    requireFields($b, ['vendor_id', 'amount', 'payment_date', 'type']);

    $type = $b['type'];
    if (!in_array($type, ['payment','credit_note','opening_balance','manual_purchase'])) jsonError('Invalid type');

    $pdo->prepare("INSERT INTO vendor_payments (vendor_id,payee_id,amount,reference_no,payment_date,notes,type,created_by)
                   VALUES (?,?,?,?,?,?,?,?)")
        ->execute([
            (int)$b['vendor_id'],
            !empty($b['payee_id']) ? (int)$b['payee_id'] : null,
            round((float)$b['amount'], 2),
            trim($b['reference_no'] ?? ''),
            $b['payment_date'],
            trim(!empty($b['description']) ? $b['description'] : ($b['notes'] ?? '')),
            $type,
            $u['id'] ?? null,
        ]);
    $id = (int)$pdo->lastInsertId();
    $vname = $pdo->query("SELECT name FROM vendors WHERE id=".(int)$b['vendor_id'])->fetchColumn();
    auditLog($pdo,'vendor_payment','vendor',(int)$b['vendor_id'], ucfirst($type)." ₹".$b['amount']." → ".$vname);
    jsonOk(['id'=>$id], ucfirst(str_replace('_',' ',$type)).' recorded');
}

// ── PUT (edit) ────────────────────────────────────────────
if ($method === 'PUT') {
    requireAuth();
    $u = currentUser();
    if (!$u || $u['role'] !== 'admin') jsonError('Admin access required', 403);
    $b = getBody();
    requireFields($b, ['id','amount','txn_date']);
    $id      = (int)$b['id'];
    $amount  = (float)$b['amount'];
    $date    = trim($b['txn_date']);
    $ref     = trim($b['reference_no'] ?? '');
    $notes   = trim($b['notes'] ?? '');
    $type    = trim($b['type'] ?? 'payment');
    $payeeId = !empty($b['payee_id']) ? (int)$b['payee_id'] : null;

    $pdo->prepare("UPDATE vendor_payments SET amount=?, payment_date=?, reference_no=?, notes=?, type=?, payee_id=? WHERE id=?")
        ->execute([$amount, $date, $ref, $notes, $type, $payeeId, $id]);

    auditLog($pdo, 'update', 'vendor_payment', $id, "Edited payment #{$id}: amount={$amount}, date={$date}");
    jsonOk(['id'=>$id], 'Payment updated');
}

// ── DELETE ────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    requireRole('admin','manager','partner');
    $id = (int)($_GET['id'] ?? 0); if (!$id) jsonError('ID required');
    $row = $pdo->query("SELECT * FROM vendor_payments WHERE id=$id")->fetch();
    if (!$row) jsonError('Not found', 404);
    $pdo->prepare("DELETE FROM vendor_payments WHERE id=?")->execute([$id]);
    auditLog($pdo,'delete_vendor_payment','vendor',$row['vendor_id'],"Deleted ₹".$row['amount']);
    jsonOk(null,'Entry deleted');
}
