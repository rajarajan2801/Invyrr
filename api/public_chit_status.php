<?php
/**
 * Invyrr API — Public Chit Status (no auth — same deliberate exception as
 * public_catalog.php/public_checkout.php/public_order_status.php; this is
 * the fourth). Lets a chit customer check their own payment history and
 * bonus-eligibility by phone number alone — same no-OTP/no-password
 * design as track-order.php: knowing the phone number on file is treated
 * as proof enough, matching how this business already handles order
 * lookups.
 *
 * GET /api/public_chit_status.php?phone=9876543210
 *
 * Deliberately narrow about what it exposes: no customer address/GST/
 * notes, no internal payee/created_by ids — just enough to answer
 * "what have I paid and what's left" and let a receipt be printed.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

require __DIR__ . '/../includes/db.php';
// No startSession()/requireAuth() here on purpose -- see header comment.

$pdo = getDB();

$phone = preg_replace('/[^0-9]/', '', (string)($_GET['phone'] ?? ''));
if (strlen($phone) < 10) jsonError('Enter a valid 10-digit phone number');
$last10 = substr($phone, -10);

// Same duplicated annotation helpers as api/chit.php -- not shared via
// include, matching how publicShopLocationId() is deliberately
// duplicated across public_catalog.php/public_checkout.php rather than
// pulling the authenticated file's logic into a public one.
function chitAnnotatePayments(array $payments, string $startDate): array {
    $start = new DateTime($startDate);
    $i = 0;
    foreach ($payments as &$p) {
        $i++;
        $due = (clone $start)->modify('+'.($i-1).' months');
        $due->setDate((int)$due->format('Y'), (int)$due->format('m'), 10);
        $p['installment_no'] = $i;
        $p['due_date']       = $due->format('Y-m-d');
        $p['on_time']        = (new DateTime($p['payment_date'])) <= $due;
    }
    unset($p);
    return $payments;
}
function chitBonusStatus(array $annotatedPayments, array $enr): string {
    foreach ($annotatedPayments as $p) { if (!$p['on_time']) return 'forfeited'; }
    $durationMonths = (int)$enr['duration_months'];
    $totalPaid      = array_sum(array_column($annotatedPayments, 'amount'));
    $requiredTotal  = $durationMonths * (float)$enr['monthly_amount'];
    if (count($annotatedPayments) >= $durationMonths && $totalPaid >= $requiredTotal) return 'earned';
    return 'on_track';
}

// Deliberately no CREATE TABLE here (unlike api/chit.php) -- this
// endpoint only ever reads, and if no chit enrollment has ever been made
// the tables may not exist yet. Caught rather than left to bubble up as
// a raw SQL error in a public JSON response -- "the table isn't there
// yet" and "no chit for this phone number" look identical to a customer
// either way.
try {
    $custStmt = $pdo->prepare(
        "SELECT id FROM customers
         WHERE RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'+',''),'.',''), 10) = ?"
    );
    $custStmt->execute([$last10]);
    $custIds = array_column($custStmt->fetchAll(), 'id');
    if (!$custIds) jsonOk(['enrollments' => []]);

    $placeholders = implode(',', array_fill(0, count($custIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT ce.id, ce.start_date, ce.status, c.name AS customer_name,
                cp.code AS plan_code, cp.label AS plan_label, cp.monthly_amount,
                cp.duration_months, cp.bonus_amount, cp.total_value
         FROM chit_enrollments ce
         JOIN customers c   ON c.id = ce.customer_id
         JOIN chit_plans cp ON cp.id = ce.plan_id
         WHERE ce.customer_id IN ($placeholders)
         ORDER BY ce.created_at DESC"
    );
    $stmt->execute($custIds);
    $enrollments = $stmt->fetchAll();

    $payStmt = $pdo->prepare(
        "SELECT amount, payment_date, mode, reference_no
         FROM chit_payments WHERE enrollment_id = ? ORDER BY payment_date, id"
    );
    foreach ($enrollments as &$e) {
        $payStmt->execute([$e['id']]);
        $payments  = $payStmt->fetchAll();
        $annotated = chitAnnotatePayments($payments, $e['start_date']);
        $e['payments']       = $annotated;
        $e['total_paid']     = array_sum(array_column($payments, 'amount'));
        $e['months_covered'] = (float)$e['monthly_amount'] > 0 ? floor($e['total_paid'] / (float)$e['monthly_amount']) : 0;
        $e['bonus_status']   = chitBonusStatus($annotated, $e);
    }
    unset($e);
    jsonOk(['enrollments' => $enrollments]);
} catch (Exception $ex) {
    jsonOk(['enrollments' => []]);
}
