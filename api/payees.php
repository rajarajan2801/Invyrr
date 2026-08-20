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
// Ensure customer_payments table exists too (safety for cross-dependency
// -- the list query below now counts/sums it alongside vendor_payments
// and expenses so a payee's Payments/Total figures and delete-guard
// account for money received via customer payments, not just paid out).
try { $pdo->exec("CREATE TABLE IF NOT EXISTS customer_payments (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id INT UNSIGNED DEFAULT NULL, customer_name VARCHAR(200) DEFAULT '', amount DECIMAL(12,2) NOT NULL DEFAULT 0, payment_date DATE NOT NULL, payee_id INT UNSIGNED DEFAULT NULL, mode VARCHAR(20) NOT NULL DEFAULT 'account', reference_no VARCHAR(100) DEFAULT '', note VARCHAR(500) DEFAULT '', created_by INT UNSIGNED DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_order (order_id))"); } catch (Exception $e) {}

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

// account_kind separates the big shared Payees list into 'debit'
// accounts (money paid OUT -- vendor payments/expenses, the existing
// default) and 'credit' accounts (money paid IN -- customer payment
// collection accounts like a UPI/cash till), plus 'both' for an account
// used either way. This lets the dropdowns each show only the relevant
// subset instead of every payee ever created.
try { $pdo->exec("ALTER TABLE payees ADD COLUMN account_kind VARCHAR(10) NOT NULL DEFAULT 'debit'"); } catch (Exception $e) {}
// Backfill, kept separate from the ALTER above (and in its own
// try/catch) so it isn't skipped just because vendor_payments/expenses
// happen not to exist yet on a fresh install -- it re-checks every
// request but is self-limiting (only touches rows still at the
// untouched 'debit' default that already have a customer payment
// against them), so it's a cheap no-op once everything's caught up.
// Any payee that already has customer payments recorded against it was
// clearly being used to collect customer money before this column
// existed -- reclassify it now rather than silently dropping it out of
// the (about to be credit-filtered) collection dropdown. If it's also
// got vendor payments/expenses against it, mark it 'both' so it keeps
// showing up on the disbursement side too.
try {
    $pdo->exec("UPDATE payees p SET account_kind = IF(
            EXISTS(SELECT 1 FROM vendor_payments vp WHERE vp.payee_id = p.id)
            OR EXISTS(SELECT 1 FROM expenses e WHERE e.payee_id = p.id),
            'both', 'credit'
        )
        WHERE account_kind = 'debit'
          AND EXISTS(SELECT 1 FROM customer_payments cp WHERE cp.payee_id = p.id)");
} catch (Exception $e) {}

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
    if (!empty($_GET['kind']) && in_array($_GET['kind'], ['credit','debit'])) {
        $where[] = "(account_kind = ? OR account_kind = 'both')";
        $params[] = $_GET['kind'];
    }
    // Optional YTD filter on the joined vendor_payments
    $vpWhere = "";
    if (!empty($_GET['ytd'])) {
        $year = date('Y');
        $vpWhere = "AND vp.payment_date >= '$year-01-01' AND vp.payment_date <= '$year-12-31'";
    }
    $sql = "SELECT p.*,
                   (SELECT COUNT(*) FROM vendor_payments WHERE payee_id=p.id)
                   + (SELECT COUNT(*) FROM expenses WHERE payee_id=p.id)
                   + (SELECT COUNT(*) FROM customer_payments WHERE payee_id=p.id) AS payment_count,
                   COALESCE((SELECT SUM(CASE WHEN type='credit_note' THEN -amount ELSE amount END) FROM vendor_payments WHERE payee_id=p.id),0) AS total_vp_paid,
                   COALESCE((SELECT SUM(amount) FROM expenses WHERE payee_id=p.id),0) AS total_expenses,
                   COALESCE((SELECT SUM(amount) FROM customer_payments WHERE payee_id=p.id),0) AS total_customer_paid,
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
    $kind = in_array($b['account_kind'] ?? '', ['credit','debit','both']) ? $b['account_kind'] : 'debit';
    $pdo->prepare("INSERT INTO payees (name,type,account_no,bank_name,ifsc,upi_id,phone,notes,is_active,account_kind)
                   VALUES (?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            trim($b['name']), trim($b['type']??''), trim($b['account_no']??''),
            trim($b['bank_name']??''), trim($b['ifsc']??''), trim($b['upi_id']??''),
            trim($b['phone']??''), trim($b['notes']??''), isset($b['is_active'])?(int)$b['is_active']:1,
            $kind
        ]);
    $id = (int)$pdo->lastInsertId();
    auditLog($pdo,'create_payee','payee',$id,trim($b['name']));
    jsonOk($pdo->query("SELECT * FROM payees WHERE id=$id")->fetch(), 'Payee created');
}

if ($method === 'PUT') {
    requireRole('admin','manager','partner');
    $b = getBody(); requireFields($b, ['id','name']);
    $kind = in_array($b['account_kind'] ?? '', ['credit','debit','both']) ? $b['account_kind'] : 'debit';
    $pdo->prepare("UPDATE payees SET name=?,type=?,account_no=?,bank_name=?,ifsc=?,upi_id=?,phone=?,notes=?,is_active=?,account_kind=? WHERE id=?")
        ->execute([
            trim($b['name']), trim($b['type']??''), trim($b['account_no']??''),
            trim($b['bank_name']??''), trim($b['ifsc']??''), trim($b['upi_id']??''),
            trim($b['phone']??''), trim($b['notes']??''), isset($b['is_active'])?(int)$b['is_active']:1,
            $kind, (int)$b['id']
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
