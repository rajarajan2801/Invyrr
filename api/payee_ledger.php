<?php
/**
 * Payee Ledger API
 * GET /api/payee_ledger.php?id=N&from=YYYY-MM-DD&to=YYYY-MM-DD
 */
require __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
startSession(); requireAuth();

$id  = (int)($_GET['id'] ?? 0);
if (!$id) jsonError('Payee ID required');

$pdo  = getDB();

// Ensure expenses table exists before UNION query
$pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_date DATE NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'General',
    amount DECIMAL(12,2) NOT NULL,
    vendor_id INT DEFAULT NULL,
    payee_id INT DEFAULT NULL,
    reference_no VARCHAR(100) DEFAULT '',
    notes TEXT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
// Ensure payee_id column exists on expenses (may have been created without it)
try { $pdo->exec("ALTER TABLE expenses ADD COLUMN payee_id INT DEFAULT NULL AFTER vendor_id"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE expenses ADD COLUMN vendor_id INT DEFAULT NULL AFTER amount"); } catch (Exception $e) {}
// Ensure customer_payments table exists too -- this ledger now pulls
// from it alongside vendor_payments/expenses (see $cpRows below).
try { $pdo->exec("CREATE TABLE IF NOT EXISTS customer_payments (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id INT UNSIGNED DEFAULT NULL, customer_name VARCHAR(200) DEFAULT '', amount DECIMAL(12,2) NOT NULL DEFAULT 0, payment_date DATE NOT NULL, payee_id INT UNSIGNED DEFAULT NULL, mode VARCHAR(20) NOT NULL DEFAULT 'account', reference_no VARCHAR(100) DEFAULT '', note VARCHAR(500) DEFAULT '', created_by INT UNSIGNED DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_order (order_id))"); } catch (Exception $e) {}
$from = $_GET['from'] ?? null;
$to   = $_GET['to']   ?? null;

// Payee info
$payee = $pdo->prepare("SELECT * FROM payees WHERE id=?");
$payee->execute([$id]);
$p = $payee->fetch();
if (!$p) jsonError('Payee not found', 404);

// Build date filters using actual column names (aliases can't be used in WHERE)
$vpDateFilter  = "";
$expDateFilter = "";
$cpDateFilter  = "";
if ($from) { $vpDateFilter  .= " AND payment_date>='$from'"; $expDateFilter .= " AND expense_date>='$from'"; $cpDateFilter .= " AND cp.payment_date>='$from'"; }
if ($to)   { $vpDateFilter  .= " AND payment_date<='$to'";   $expDateFilter .= " AND expense_date<='$to'";   $cpDateFilter .= " AND cp.payment_date<='$to'"; }

// Fetch vendor payments and expenses separately, merge in PHP
$txns = [];

$vpRows = $pdo->query("
    SELECT id, payment_date AS txn_date, type, amount,
           reference_no, notes AS description, vendor_id,
           NULL AS expense_category
    FROM vendor_payments
    WHERE payee_id=$id $vpDateFilter
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($vpRows as $r) $txns[] = $r;

$expRows = $pdo->query("
    SELECT id, expense_date AS txn_date, 'expense' AS type, amount,
           reference_no,
           CONCAT(category, CASE WHEN notes IS NOT NULL AND notes != '' THEN CONCAT(' — ', notes) ELSE '' END) AS description,
           vendor_id, category AS expense_category
    FROM expenses
    WHERE payee_id=$id $expDateFilter
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($expRows as $r) $txns[] = $r;

$cpRows = $pdo->query("
    SELECT cp.id, cp.payment_date AS txn_date, 'customer_payment' AS type, cp.amount,
           cp.reference_no,
           CONCAT('Customer payment', CASE WHEN wo.order_number IS NOT NULL THEN CONCAT(' — Order ', wo.order_number) ELSE '' END,
                  CASE WHEN cp.note IS NOT NULL AND cp.note != '' THEN CONCAT(' — ', cp.note) ELSE '' END) AS description,
           NULL AS vendor_id, NULL AS expense_category,
           COALESCE(NULLIF(cp.customer_name,''), wo.customer_name, '') AS vendor_name
    FROM customer_payments cp
    LEFT JOIN website_orders wo ON wo.id = cp.order_id
    WHERE cp.payee_id=$id $cpDateFilter
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cpRows as $r) $txns[] = $r;

// Sort by date then id
usort($txns, function($a, $b) {
    $d = strcmp($a['txn_date'] ?? '', $b['txn_date'] ?? '');
    return $d !== 0 ? $d : (($a['id'] ?? 0) - ($b['id'] ?? 0));
});

// Resolve vendor names
$vendorNames = [];
foreach ($txns as &$t) {
    if (!empty($t['vendor_id'])) {
        if (!isset($vendorNames[$t['vendor_id']])) {
            $vn = $pdo->prepare("SELECT name FROM vendors WHERE id=?");
            $vn->execute([$t['vendor_id']]);
            $vendorNames[$t['vendor_id']] = $vn->fetchColumn() ?: '';
        }
        $t['vendor_name'] = $vendorNames[$t['vendor_id']];
    } elseif (empty($t['vendor_name'])) {
        $t['vendor_name'] = '';
    }
}
unset($t);

// Summary — must match the same date filter applied to transactions
$totalVPPaid  = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM vendor_payments WHERE payee_id=$id AND type='payment' $vpDateFilter")->fetchColumn();
$totalCredits = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM vendor_payments WHERE payee_id=$id AND type='credit_note' $vpDateFilter")->fetchColumn();
try { $totalExpenses = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE payee_id=$id $expDateFilter")->fetchColumn(); } catch (Exception $e) { $totalExpenses = 0; }
$totalCustPaid = (float)$pdo->query("SELECT COALESCE(SUM(cp.amount),0) FROM customer_payments cp WHERE cp.payee_id=$id $cpDateFilter")->fetchColumn();
// All-time totals (for context regardless of filter)
$allTimePaid    = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM vendor_payments WHERE payee_id=$id AND type='payment'")->fetchColumn();
$allTimeExp     = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE payee_id=$id")->fetchColumn();
$allTimeCust    = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM customer_payments WHERE payee_id=$id")->fetchColumn();
$totalPaid = $totalVPPaid + $totalExpenses;
$txnCount  = count($txns);
$lastVP  = $pdo->query("SELECT COALESCE(MAX(payment_date),'1970-01-01') FROM vendor_payments WHERE payee_id=$id")->fetchColumn();
$lastExp = $pdo->query("SELECT COALESCE(MAX(expense_date),'1970-01-01') FROM expenses WHERE payee_id=$id")->fetchColumn();
$lastCust = $pdo->query("SELECT COALESCE(MAX(payment_date),'1970-01-01') FROM customer_payments WHERE payee_id=$id")->fetchColumn();
$lastDate = max($lastVP, $lastExp, $lastCust);
if ($lastDate === '1970-01-01') $lastDate = null;

jsonOk([    'payee'        => $p,
    'transactions' => $txns,
    'summary'      => [
        'total_paid'          => round($totalPaid, 2),
        'total_vp_paid'       => round($totalVPPaid, 2),
        'total_credits'       => round($totalCredits, 2),
        'total_expenses'      => round($totalExpenses, 2),
        'total_customer_paid' => round($totalCustPaid, 2),
        'txn_count'      => $txnCount,
        'last_txn_date'  => $lastDate,
        'all_time_paid'  => round($allTimePaid + $allTimeExp, 2),
        'all_time_customer_paid' => round($allTimeCust, 2),
    ],
]);
