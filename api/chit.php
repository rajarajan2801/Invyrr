<?php
/**
 * Invyrr API — Chit Scheme (RR Crackers' festival savings plans, e.g. the
 * 2026-27 "RR 300 .. RR 3000" flyer: pay a fixed amount every month for
 * 10 months, get it back as purchase credit plus a matching bonus if
 * every installment lands by the 10th of its month).
 *
 * Scope, per Raj's own answers when this was scoped (2026-11-17):
 *   - RR Crackers only for now (not per-business like shop.php's ?loc=).
 *   - Staff enroll customers and record payments -- no online self-signup.
 *   - Redemption against an actual purchase stays manual/off-system: this
 *     is a running ledger + bonus-eligibility tracker, nothing wired into
 *     Estimates as a payment method.
 *   - The 6 plans below are hardcoded to match the flyer, not editable
 *     from the UI -- a future year's scheme is a follow-up code change.
 *
 * This file is for staff (authenticated) use only. The customer-facing
 * "check my balance" page is a separate public, phone-scoped endpoint --
 * see api/public_chit_status.php, same split as public_catalog.php vs.
 * products.php.
 *
 * GET    /api/chit.php?plans=1                → the 6 chit plans
 * GET    /api/chit.php                        → enrollments (?q=name/phone, ?status=active|completed|cancelled)
 * GET    /api/chit.php?id=N                   → one enrollment + its full payment ledger
 * POST   /api/chit.php                        → create an enrollment
 * PUT    /api/chit.php                        → edit an enrollment (status/notes/start_date)
 * DELETE /api/chit.php?id=N                   → delete an enrollment (admin/partner only; cascades payments)
 * POST   /api/chit.php?payment=1              → record a payment
 * PUT    /api/chit.php?payment=1              → edit a payment
 * DELETE /api/chit.php?payment=1&id=N         → delete a payment (admin/partner only)
 */
require __DIR__.'/../includes/db.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}
startSession();
$method=$_SERVER['REQUEST_METHOD'];
$pdo=getDB();

// ── Self-healing schema -- same pattern as picking_sessions.php/
// public_checkout.php: no install.php entry, these tables appear the
// first time this endpoint is ever hit, so an existing deployment picks
// this feature up with no migration step. ─────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS chit_plans (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(20)   NOT NULL UNIQUE,
    label           VARCHAR(50)   NOT NULL,
    monthly_amount  DECIMAL(10,2) NOT NULL,
    duration_months INT           NOT NULL DEFAULT 10,
    bonus_amount    DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_value     DECIMAL(10,2) NOT NULL,
    sort_order      INT           NOT NULL DEFAULT 0,
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS chit_enrollments (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    customer_id  INT NOT NULL,
    plan_id      INT NOT NULL,
    start_date   DATE NOT NULL,
    status       VARCHAR(20)  NOT NULL DEFAULT 'active' COMMENT 'active, completed, cancelled',
    notes        VARCHAR(500) DEFAULT '',
    created_by   INT DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES chit_plans(id),
    INDEX idx_customer (customer_id), INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS chit_payments (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id  INT NOT NULL,
    amount         DECIMAL(10,2) NOT NULL,
    payment_date   DATE NOT NULL,
    mode           VARCHAR(20)  NOT NULL DEFAULT 'Cash' COMMENT 'Cash, UPI, Bank Transfer, Other',
    payee_id       INT DEFAULT NULL,
    reference_no   VARCHAR(100) DEFAULT '',
    notes          VARCHAR(500) DEFAULT '',
    created_by     INT DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES chit_enrollments(id) ON DELETE CASCADE,
    INDEX idx_enrollment (enrollment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed the 6 RR Crackers 2026-27 plans (offer window Nov'26-Aug'27, pay
// by the 10th of every month to keep the bonus) from the flyer Raj
// shared. Idempotent on the unique `code`, so re-running this is safe;
// only seeds once (on an empty table) so an admin tweaking a row by hand
// later is never silently overwritten.
if ((int)$pdo->query("SELECT COUNT(*) FROM chit_plans")->fetchColumn() === 0) {
    $seed = $pdo->prepare("INSERT INTO chit_plans (code,label,monthly_amount,duration_months,bonus_amount,total_value,sort_order) VALUES (?,?,?,?,?,?,?)");
    foreach ([
        ['RR300',  'RR 300',  300,  10, 300,  3300,  1],
        ['RR400',  'RR 400',  400,  10, 400,  4400,  2],
        ['RR500',  'RR 500',  500,  10, 500,  5500,  3],
        ['RR1000', 'RR 1000', 1000, 10, 1000, 11000, 4],
        ['RR2000', 'RR 2000', 2000, 10, 2000, 22000, 5],
        ['RR3000', 'RR 3000', 3000, 10, 3000, 33000, 6],
    ] as $p) { $seed->execute($p); }
}

// ── Bonus-eligibility helpers ───────────────────────────────────────
// Payments aren't tied to a fixed installment slot (a customer can pay
// late, early, or a different amount) -- so eligibility is derived by
// walking payments in date order and comparing each one's position
// against where it "should" land: installment N is due by the 10th of
// the Nth month after start_date. One late installment forfeits the
// bonus outright, same as the flyer's own rule ("paid on or before the
// 10th of every month"), even if the plan isn't finished yet.
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

// ── Enrollments / plans ──────────────────────────────────────────────
if ($method==='GET') {
    requireAuth();
    if (isset($_GET['plans'])) {
        jsonList($pdo->query("SELECT * FROM chit_plans WHERE is_active=1 ORDER BY sort_order")->fetchAll());
    }
    if (!empty($_GET['id'])) {
        $id = (int)$_GET['id'];
        $e = $pdo->prepare(
            "SELECT ce.*, c.name AS customer_name, c.phone AS customer_phone,
                    cp.code AS plan_code, cp.label AS plan_label, cp.monthly_amount,
                    cp.duration_months, cp.bonus_amount, cp.total_value
             FROM chit_enrollments ce
             JOIN customers c  ON c.id = ce.customer_id
             JOIN chit_plans cp ON cp.id = ce.plan_id
             WHERE ce.id = ?"
        );
        $e->execute([$id]);
        $enr = $e->fetch();
        if (!$enr) jsonError('Enrollment not found', 404);
        $p = $pdo->prepare(
            "SELECT cpay.*, pa.name AS payee_name
             FROM chit_payments cpay
             LEFT JOIN payees pa ON pa.id = cpay.payee_id
             WHERE cpay.enrollment_id = ?
             ORDER BY cpay.payment_date, cpay.id"
        );
        $p->execute([$id]);
        $payments = $p->fetchAll();
        $annotated = chitAnnotatePayments($payments, $enr['start_date']);
        $enr['payments']        = $annotated;
        $enr['total_paid']      = array_sum(array_column($payments, 'amount'));
        $enr['months_covered']  = (float)$enr['monthly_amount'] > 0 ? floor($enr['total_paid'] / (float)$enr['monthly_amount']) : 0;
        $enr['bonus_status']    = chitBonusStatus($annotated, $enr);
        jsonOk($enr);
    }
    $where = []; $params = [];
    if (!empty($_GET['status'])) { $where[] = 'ce.status = ?'; $params[] = $_GET['status']; }
    if (!empty($_GET['q'])) {
        $like = '%'.$_GET['q'].'%';
        $where[] = '(c.name LIKE ? OR c.phone LIKE ?)';
        $params[] = $like; $params[] = $like;
    }
    $sql = "SELECT ce.*, c.name AS customer_name, c.phone AS customer_phone,
                   cp.code AS plan_code, cp.label AS plan_label, cp.monthly_amount,
                   cp.duration_months, cp.bonus_amount, cp.total_value,
                   COALESCE((SELECT SUM(amount) FROM chit_payments WHERE enrollment_id = ce.id), 0) AS total_paid,
                   (SELECT COUNT(*) FROM chit_payments WHERE enrollment_id = ce.id) AS payment_count
            FROM chit_enrollments ce
            JOIN customers c   ON c.id = ce.customer_id
            JOIN chit_plans cp ON cp.id = ce.plan_id"
            .($where ? ' WHERE '.implode(' AND ', $where) : '')
            ." ORDER BY ce.created_at DESC";
    $s = $pdo->prepare($sql);
    $s->execute($params);
    $rows = $s->fetchAll();
    // One extra query per row to compute bonus_status -- fine at the
    // scale this runs at (a handful of chit customers, not thousands);
    // needed because eligibility depends on walking each payment against
    // its own due date (see chitAnnotatePayments), not just the sum.
    $payStmt = $pdo->prepare("SELECT amount, payment_date FROM chit_payments WHERE enrollment_id = ? ORDER BY payment_date, id");
    foreach ($rows as &$r) {
        $r['months_covered'] = (float)$r['monthly_amount'] > 0 ? floor((float)$r['total_paid'] / (float)$r['monthly_amount']) : 0;
        $payStmt->execute([$r['id']]);
        $annotated = chitAnnotatePayments($payStmt->fetchAll(), $r['start_date']);
        $r['bonus_status'] = chitBonusStatus($annotated, $r);
    }
    unset($r);
    jsonList($rows);
}

if ($method==='POST' && !isset($_GET['payment'])) {
    $u = requireAuth();
    $b = getBody();
    requireFields($b, ['plan_id']);
    $customerId = !empty($b['customer_id']) ? (int)$b['customer_id'] : null;
    if (!$customerId) {
        $name = trim((string)($b['customer_name'] ?? ''));
        if ($name === '') jsonError('Select or enter a customer');
        $phone = trim((string)($b['customer_phone'] ?? ''));
        // Reuse an existing customer with the same name+phone instead of
        // creating a duplicate row every time the same walk-in signs up
        // for another plan -- same name match latitude the Estimate
        // modal's own inline customer search already relies on.
        $existing = null;
        if ($phone !== '') {
            $m = $pdo->prepare("SELECT id FROM customers WHERE phone = ? AND name = ? LIMIT 1");
            $m->execute([$phone, $name]);
            $existing = $m->fetchColumn();
        }
        if ($existing) {
            $customerId = (int)$existing;
        } else {
            $pdo->prepare("INSERT INTO customers (name, phone) VALUES (?, ?)")->execute([$name, $phone]);
            $customerId = (int)$pdo->lastInsertId();
        }
    }
    $planId = (int)$b['plan_id'];
    $pchk = $pdo->prepare("SELECT id FROM chit_plans WHERE id = ?");
    $pchk->execute([$planId]);
    if (!$pchk->fetch()) jsonError('Chit plan not found', 404);
    $startDate = !empty($b['start_date']) ? $b['start_date'] : date('Y-m-d');
    $pdo->prepare("INSERT INTO chit_enrollments (customer_id, plan_id, start_date, notes, created_by) VALUES (?,?,?,?,?)")
        ->execute([$customerId, $planId, $startDate, trim($b['notes'] ?? ''), $u['id']]);
    $id = (int)$pdo->lastInsertId();
    auditLog($pdo, 'chit_enroll', 'chit_enrollment', $id, "Enrolled customer #$customerId in chit plan #$planId");
    jsonOk(['id' => $id], 'Enrollment created');
}

if ($method==='PUT' && !isset($_GET['payment'])) {
    requireAuth();
    $b = getBody();
    requireFields($b, ['id']);
    $id = (int)$b['id'];
    $row = $pdo->prepare("SELECT id FROM chit_enrollments WHERE id = ?");
    $row->execute([$id]);
    if (!$row->fetch()) jsonError('Enrollment not found', 404);
    $status = $b['status'] ?? null;
    if ($status !== null && !in_array($status, ['active', 'completed', 'cancelled'])) jsonError('Invalid status');
    $sets = []; $params = [];
    if ($status !== null) { $sets[] = 'status = ?'; $params[] = $status; }
    if (array_key_exists('notes', $b)) { $sets[] = 'notes = ?'; $params[] = trim($b['notes']); }
    if (!empty($b['start_date'])) { $sets[] = 'start_date = ?'; $params[] = $b['start_date']; }
    if (!$sets) jsonError('Nothing to update');
    $params[] = $id;
    $pdo->prepare("UPDATE chit_enrollments SET ".implode(', ', $sets)." WHERE id = ?")->execute($params);
    auditLog($pdo, 'chit_update', 'chit_enrollment', $id, 'Updated chit enrollment');
    jsonOk(null, 'Enrollment updated');
}

if ($method==='DELETE' && !isset($_GET['payment'])) {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    requireRole('admin', 'manager', 'partner');
    $id = (int)($_GET['id'] ?? 0);
    $row = $pdo->prepare("SELECT id FROM chit_enrollments WHERE id = ?");
    $row->execute([$id]);
    if (!$row->fetch()) jsonError('Enrollment not found', 404);
    $pdo->prepare("DELETE FROM chit_enrollments WHERE id = ?")->execute([$id]); // cascades chit_payments
    auditLog($pdo, 'chit_delete', 'chit_enrollment', $id, 'Deleted chit enrollment');
    jsonOk(null, 'Enrollment deleted');
}

// ── Payments ─────────────────────────────────────────────────────────
if ($method==='POST' && isset($_GET['payment'])) {
    $u = requireAuth();
    $b = getBody();
    requireFields($b, ['enrollment_id', 'amount', 'payment_date']);
    $enrId = (int)$b['enrollment_id'];
    $enr = $pdo->prepare("SELECT id FROM chit_enrollments WHERE id = ?");
    $enr->execute([$enrId]);
    if (!$enr->fetch()) jsonError('Enrollment not found', 404);
    $amount = (float)$b['amount'];
    if ($amount <= 0) jsonError('Amount must be greater than zero');
    $pdo->prepare("INSERT INTO chit_payments (enrollment_id, amount, payment_date, mode, payee_id, reference_no, notes, created_by) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([
            $enrId, $amount, $b['payment_date'], $b['mode'] ?? 'Cash',
            !empty($b['payee_id']) ? (int)$b['payee_id'] : null,
            trim($b['reference_no'] ?? ''), trim($b['notes'] ?? ''), $u['id'],
        ]);
    $id = (int)$pdo->lastInsertId();
    auditLog($pdo, 'chit_payment', 'chit_payment', $id, "Recorded chit payment of ₹$amount for enrollment #$enrId");
    jsonOk(['id' => $id], 'Payment recorded');
}

if ($method==='PUT' && isset($_GET['payment'])) {
    requireAuth();
    $b = getBody();
    requireFields($b, ['id', 'amount', 'payment_date']);
    $id = (int)$b['id'];
    $row = $pdo->prepare("SELECT id FROM chit_payments WHERE id = ?");
    $row->execute([$id]);
    if (!$row->fetch()) jsonError('Payment not found', 404);
    $amount = (float)$b['amount'];
    if ($amount <= 0) jsonError('Amount must be greater than zero');
    $pdo->prepare("UPDATE chit_payments SET amount=?, payment_date=?, mode=?, payee_id=?, reference_no=?, notes=? WHERE id=?")
        ->execute([
            $amount, $b['payment_date'], $b['mode'] ?? 'Cash',
            !empty($b['payee_id']) ? (int)$b['payee_id'] : null,
            trim($b['reference_no'] ?? ''), trim($b['notes'] ?? ''), $id,
        ]);
    auditLog($pdo, 'chit_payment_edit', 'chit_payment', $id, 'Edited chit payment');
    jsonOk(null, 'Payment updated');
}

if ($method==='DELETE' && isset($_GET['payment'])) {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    requireRole('admin', 'manager', 'partner');
    $id = (int)($_GET['id'] ?? 0);
    $row = $pdo->prepare("SELECT id FROM chit_payments WHERE id = ?");
    $row->execute([$id]);
    if (!$row->fetch()) jsonError('Payment not found', 404);
    $pdo->prepare("DELETE FROM chit_payments WHERE id = ?")->execute([$id]);
    auditLog($pdo, 'chit_payment_delete', 'chit_payment', $id, 'Deleted chit payment');
    jsonOk(null, 'Payment deleted');
}
