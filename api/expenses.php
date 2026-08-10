<?php
/**
 * Invyrr API — Expenses
 * GET    /api/expenses.php               → list (filters: from, to, category, vendor_id, payee_id)
 * GET    /api/expenses.php?categories=1  → list expense categories
 * POST   /api/expenses.php               → create
 * PUT    /api/expenses.php               → update
 * DELETE /api/expenses.php?id=N          → delete
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../includes/db.php';
startSession(); requireAuth();

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Auto-create tables
// Create tables separately so each failure is isolated
$pdo->exec("CREATE TABLE IF NOT EXISTS expense_categories (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
// Only seed default categories if table is completely empty
$count = $pdo->query("SELECT COUNT(*) FROM expense_categories")->fetchColumn();
if ($count == 0) {
    $pdo->exec("INSERT IGNORE INTO expense_categories (name) VALUES
        ('Transport'),('Labour'),('Rent'),('Utilities'),('Salaries'),
        ('Packaging'),('Marketing'),('Office Supplies'),('Maintenance'),('Other')");
}

// Create expenses table without FKs first, then add them safely
$pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    expense_date DATE NOT NULL,
    category     VARCHAR(100) NOT NULL DEFAULT 'General',
    amount       DECIMAL(12,2) NOT NULL,
    vendor_id    INT DEFAULT NULL,
    payee_id     INT DEFAULT NULL,
    reference_no VARCHAR(100) DEFAULT '',
    notes        TEXT NULL,
    created_by   INT DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Add FKs separately — silently ignore if already exist or referenced table missing
// Ensure columns exist (safe on existing tables)
// Ensure expense_entities table exists before adding FK that references it
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS expense_entities (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

// Add missing columns safely — check existence first to avoid silent failures
foreach (['vendor_id','payee_id','paid_to_id','entity_id'] as $_col) {
    if ($pdo->query("SHOW COLUMNS FROM expenses LIKE '$_col'")->rowCount() === 0) {
        try { $pdo->exec("ALTER TABLE expenses ADD COLUMN $_col INT DEFAULT NULL"); } catch (Exception $e) {}
    }
}
try { $pdo->exec("ALTER TABLE expenses ADD CONSTRAINT fk_exp_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE expenses ADD CONSTRAINT fk_exp_payee  FOREIGN KEY (payee_id)  REFERENCES payees(id)  ON DELETE SET NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE expenses ADD CONSTRAINT fk_exp_paidto FOREIGN KEY (paid_to_id) REFERENCES payees(id) ON DELETE SET NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE expenses ADD CONSTRAINT fk_exp_entity FOREIGN KEY (entity_id) REFERENCES expense_entities(id) ON DELETE SET NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE expenses ADD CONSTRAINT fk_exp_user   FOREIGN KEY (created_by) REFERENCES users(id)   ON DELETE SET NULL"); } catch (Exception $e) {}

// Audit tick: once an expense is ticked audited, only admin can edit it.
foreach ([
    ['audited',    "ALTER TABLE expenses ADD COLUMN audited TINYINT(1) NOT NULL DEFAULT 0"],
    ['audited_by', "ALTER TABLE expenses ADD COLUMN audited_by VARCHAR(128) DEFAULT NULL"],
    ['audited_at', "ALTER TABLE expenses ADD COLUMN audited_at DATETIME DEFAULT NULL"],
] as [$_col, $_sql]) {
    if ($pdo->query("SHOW COLUMNS FROM expenses LIKE '$_col'")->rowCount() === 0) {
        try { $pdo->exec($_sql); } catch (Exception $e) {}
    }
}

if ($method === 'GET') {
    // Single expense fetch
    if (!empty($_GET['single'])) {
        $row = $pdo->prepare("SELECT e.*, v.name AS vendor_name, p.name AS payee_name, pt.name AS paid_to_name FROM expenses e LEFT JOIN vendors v ON v.id=e.vendor_id LEFT JOIN payees p ON p.id=e.payee_id LEFT JOIN payees pt ON pt.id=e.paid_to_id WHERE e.id=?");
        $row->execute([(int)$_GET['single']]);
        $exp = $row->fetch();
        if (!$exp) jsonError('Expense not found', 404);
        jsonOk($exp);
    }

    // Categories list — union of expense_categories table + distinct categories used in expenses
    if (!empty($_GET['categories'])) {
        $u = currentUser();
        $isFullAccess = in_array($u['role'] ?? '', ['admin', 'partner']);
        $userFilter = $isFullAccess ? '' : ' AND created_by = ' . (int)($u['id'] ?? 0);
        $cats = $pdo->query("
            SELECT name FROM expense_categories
            UNION
            SELECT DISTINCT category AS name FROM expenses WHERE category IS NOT NULL AND category != ''{$userFilter}
            ORDER BY name
        ")->fetchAll(PDO::FETCH_ASSOC);
        jsonOk($cats);
    }

    // Expense list
    $where  = ['1=1'];
    $params = [];
    if (!empty($_GET['from']))       { $where[] = 'e.expense_date >= ?'; $params[] = $_GET['from']; }
    if (!empty($_GET['to']))         { $where[] = 'e.expense_date <= ?'; $params[] = $_GET['to']; }
    if (!empty($_GET['category']))   { $where[] = 'e.category = ?';      $params[] = $_GET['category']; }
    if (!empty($_GET['vendor_id']))  { $where[] = 'e.vendor_id = ?';     $params[] = (int)$_GET['vendor_id']; }
    if (!empty($_GET['payee_id']))   { $where[] = 'e.payee_id = ?';      $params[] = (int)$_GET['payee_id']; }
    if (!empty($_GET['paid_to_id'])) { $where[] = 'e.paid_to_id = ?';    $params[] = (int)$_GET['paid_to_id']; }
    // entity_id=all  → no filter (show everything)
    // entity_id=N    → show that specific business
    // entity_id absent → show only RR Expenses (entity_id IS NULL)
    if (!isset($_GET['entity_id'])) {
        $where[] = 'e.entity_id IS NULL'; // default: RR Expenses only
    } elseif ($_GET['entity_id'] !== 'all') {
        $where[] = 'e.entity_id = ?';
        $params[] = (int)$_GET['entity_id'];
    }
    // entity_id=all: no WHERE clause added — show everything

    // Non-admin/partner roles only see their own expenses
    $u = currentUser();
    if (!in_array($u['role'] ?? '', ['admin', 'partner'])) {
        $where[] = 'e.created_by = ?';
        $params[] = (int)$u['id'];
    }

    $sql  = "SELECT e.*, v.name AS vendor_name,
                    p.name AS payee_name, p.type AS payee_type,
                    p.bank_name AS payee_bank, p.account_no AS payee_account,
                    p.ifsc AS payee_ifsc, p.upi_id AS payee_upi,
                    pt.name AS paid_to_name, pt.type AS paid_to_type,
                    ee.name AS entity_name
             FROM expenses e
             LEFT JOIN vendors v   ON v.id  = e.vendor_id
             LEFT JOIN payees  p   ON p.id  = e.payee_id
             LEFT JOIN payees  pt  ON pt.id = e.paid_to_id
             LEFT JOIN expense_entities ee ON ee.id = e.entity_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY e.expense_date DESC, e.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows  = $stmt->fetchAll();

    $total = array_sum(array_column($rows, 'amount'));
    jsonList($rows, count($rows));
}

if ($method === 'POST') {
    requireAuth();
    $b = getBody();
    // Add category only
    if (!empty($_GET['add_category'])) {
        $name = trim($b['category_name'] ?? '');
        if (!$name) jsonError('Category name required');
        try { $pdo->prepare("INSERT IGNORE INTO expense_categories (name) VALUES (?)")->execute([$name]); } catch (Exception $e) {}
        jsonOk([], 'Category added');
    }
    // Rename category
    if (!empty($_GET['rename_category'])) {
        $oldName = trim($b['old_name'] ?? '');
        $newName = trim($b['new_name'] ?? '');
        if (!$oldName || !$newName) jsonError('Old and new name required');
        $pdo->prepare("UPDATE expense_categories SET name=? WHERE name=?")->execute([$newName, $oldName]);
        // Also update existing expense records
        $pdo->prepare("UPDATE expenses SET category=? WHERE category=?")->execute([$newName, $oldName]);
        jsonOk([], 'Category renamed');
    }
    requireFields($b, ['expense_date','amount','category']);
    $createdBy = safeUserId($pdo);
    $pdo->prepare("INSERT INTO expenses (expense_date, category, amount, vendor_id, payee_id, paid_to_id, entity_id, reference_no, notes, created_by)
                   VALUES (?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $b['expense_date'],
            trim($b['category']),
            round((float)$b['amount'], 2),
            !empty($b['vendor_id'])   ? (int)$b['vendor_id']   : null,
            !empty($b['payee_id'])    ? (int)$b['payee_id']    : null,
            !empty($b['paid_to_id'])  ? (int)$b['paid_to_id']  : null,
            !empty($b['entity_id']) ? (int)$b['entity_id'] : null,
            trim($b['reference_no'] ?? ''),
            trim($b['notes'] ?? ''),
            $createdBy,
        ]);
    $id = (int)$pdo->lastInsertId();
    auditLog($pdo, 'create', 'expense', $id, "Expense: {$b['category']} ₹{$b['amount']} on {$b['expense_date']}");
    jsonOk(['id' => $id], 'Expense recorded');
}

if ($method === 'PUT') {
    $b = getBody();
    requireFields($b, ['id']);
    $id = (int)$b['id'];
    $u  = currentUser();

    // ── Audit tick (lightweight action — body is just {id, audited}) ────
    // Ticking marks an expense as reviewed and locks it to admin-only
    // edits from then on. Toggling the tick itself needs the same role
    // as editing, but touching an ALREADY-audited expense (ticking it off
    // again, or re-ticking) needs admin specifically — otherwise a
    // manager could un-audit their own audited expense and edit it,
    // defeating the point of the lock.
    if (array_key_exists('audited', $b) && !isset($b['expense_date'])) {
        requireRole('admin','manager','partner');
        $row = $pdo->prepare("SELECT audited FROM expenses WHERE id=?");
        $row->execute([$id]);
        $exp = $row->fetch();
        if (!$exp) jsonError('Expense not found', 404);
        if ((int)$exp['audited'] === 1 && ($u['role'] ?? '') !== 'admin') {
            jsonError('Only an admin can change an audited expense', 403);
        }
        if (!empty($b['audited'])) {
            $pdo->prepare("UPDATE expenses SET audited=1, audited_by=?, audited_at=NOW() WHERE id=?")
                ->execute([$u['name'] ?? 'Unknown', $id]);
            auditLog($pdo, 'audit_expense', 'expense', $id, "Marked expense #$id audited");
            jsonOk([], 'Expense marked audited');
        } else {
            $pdo->prepare("UPDATE expenses SET audited=0, audited_by=NULL, audited_at=NULL WHERE id=?")
                ->execute([$id]);
            auditLog($pdo, 'unaudit_expense', 'expense', $id, "Un-audited expense #$id");
            jsonOk([], 'Audit removed');
        }
    }

    // ── Full edit ─────────────────────────────────────────────────────
    requireRole('admin','manager','partner');
    requireFields($b, ['id','expense_date','amount','category']);
    // Non-admin/partner can only edit their own expenses
    if (!in_array($u['role'] ?? '', ['admin','partner'])) {
        $owner = $pdo->prepare("SELECT created_by FROM expenses WHERE id=?");
        $owner->execute([$id]);
        $row = $owner->fetch();
        if ($row && $row['created_by'] != $u['id']) jsonError('You can only edit your own expenses', 403);
    }
    // Audited expenses can only be edited by an admin, regardless of
    // ownership — this is the actual "read-only after tick" enforcement.
    $auditRow = $pdo->prepare("SELECT audited FROM expenses WHERE id=?");
    $auditRow->execute([$id]);
    $auditExp = $auditRow->fetch();
    if ($auditExp && (int)$auditExp['audited'] === 1 && ($u['role'] ?? '') !== 'admin') {
        jsonError('This expense has been audited and can only be edited by an admin', 403);
    }
    $pdo->prepare("UPDATE expenses SET expense_date=?, category=?, amount=?, vendor_id=?, payee_id=?, paid_to_id=?, entity_id=?, reference_no=?, notes=? WHERE id=?")
        ->execute([
            $b['expense_date'],
            trim($b['category']),
            round((float)$b['amount'], 2),
            !empty($b['vendor_id'])   ? (int)$b['vendor_id']   : null,
            !empty($b['payee_id'])    ? (int)$b['payee_id']    : null,
            !empty($b['paid_to_id'])  ? (int)$b['paid_to_id']  : null,
            !empty($b['entity_id']) ? (int)$b['entity_id'] : null,
            trim($b['reference_no'] ?? ''),
            trim($b['notes'] ?? ''),
            $id,
        ]);
    auditLog($pdo, 'update', 'expense', $id, "Updated expense #{$id}");
    jsonOk([], 'Expense updated');
}

if ($method === 'DELETE') {
    // Delete category — any authenticated user with CAN_DELETE can do this
    if (!empty($_GET['category'])) {
        $name = trim(urldecode($_GET['category']));
        $pdo->prepare("DELETE FROM expense_categories WHERE name = ?")->execute([$name]);
        $pdo->prepare("UPDATE expenses SET category = 'General' WHERE LOWER(TRIM(category)) = LOWER(TRIM(?))")->execute([$name]);
        jsonOk(['deleted' => $name], 'Category deleted');
    }
    // Delete expense record — admin only
    if (!canDelete()) jsonError('Only admins can delete', 403);
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $pdo->prepare("DELETE FROM expenses WHERE id=?")->execute([$id]);
    auditLog($pdo, 'delete', 'expense', $id, "Deleted expense #$id");
    jsonOk([], 'Expense deleted');
}
