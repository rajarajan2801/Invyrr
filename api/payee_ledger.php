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
if ($from) { $vpDateFilter  .= " AND payment_date>='$from'"; $expDateFilter .= " AND expense_date>='$from'"; }
if ($to)   { $vpDateFilter  .= " AND payment_date<='$to'";   $expDateFilter .= " AND expense_date<='$to'"; }

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
    } else {
        $t['vendor_name'] = '';
    }
}
unset($t);

// Summary
$totalVPPaid   = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM vendor_payments WHERE payee_id=$id AND type='payment'")->fetchColumn();
$totalCredits  = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM vendor_payments WHERE payee_id=$id AND type='credit_note'")->fetchColumn();
try { $totalExpenses = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE payee_id=$id")->fetchColumn(); } catch (Exception $e) { $totalExpenses = 0; }
$totalPaid = $totalVPPaid + $totalExpenses; // payments + expenses = total paid out
$txnCount     = count($txns);
$lastVP   = $pdo->query("SELECT COALESCE(MAX(payment_date),'1970-01-01') FROM vendor_payments WHERE payee_id=$id")->fetchColumn();
$lastExp  = $pdo->query("SELECT COALESCE(MAX(expense_date),'1970-01-01') FROM expenses WHERE payee_id=$id")->fetchColumn();
$lastDate = $lastVP > $lastExp ? $lastVP : $lastExp;
if ($lastDate === '1970-01-01') $lastDate = null;

jsonOk([    'payee'        => $p,
    'transactions' => $txns,
    'summary'      => [
        'total_paid'      => round($totalPaid, 2),
        'total_vp_paid'   => round($totalVPPaid, 2),
        'total_credits'   => round($totalCredits, 2),
        'total_expenses'  => round($totalExpenses, 2),
        'txn_count'      => $txnCount,
        'last_txn_date'  => $lastDate,
    ],
]);
