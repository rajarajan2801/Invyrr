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
$pdo->exec("INSERT IGNORE INTO expense_categories (name) VALUES
    ('Transport'),('Labour'),('Rent'),('Utilities'),('Salaries'),
    ('Packaging'),('Marketing'),('Office Supplies'),('Maintenance'),('Other')");

// Create expenses table without FKs first, then add them safely
$pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    expense_date DATE NOT NULL,
    category     VARCHAR(100) NOT NULL DEFAULT 'General',
    amount       DECIMAL(12,2) NOT NULL,
    vendor_id    INT DEFAULT NULL,
    payee_id     INT DEFAULT NULL,
    reference_no VARCHAR(100) DEFAULT '',
    notes        TEXT DEFAULT '',
    created_by   INT DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Add FKs separately — silently ignore if already exist or referenced table missing
// Ensure columns exist (safe on existing tables)
try { $pdo->exec("ALTER TABLE expenses ADD COLUMN vendor_id INT DEFAULT NULL AFTER amount"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE expenses ADD COLUMN payee_id  INT DEFAULT NULL AFTER vendor_id"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE expenses ADD CONSTRAINT fk_exp_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE expenses ADD CONSTRAINT fk_exp_payee  FOREIGN KEY (payee_id)  REFERENCES payees(id)  ON DELETE SET NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE expenses ADD CONSTRAINT fk_exp_user   FOREIGN KEY (created_by) REFERENCES users(id)   ON DELETE SET NULL"); } catch (Exception $e) {}

if ($method === 'GET') {
    // Single expense fetch
    if (!empty($_GET['single'])) {
        $row = $pdo->prepare("SELECT e.*, v.name AS vendor_name, p.name AS payee_name FROM expenses e LEFT JOIN vendors v ON v.id=e.vendor_id LEFT JOIN payees p ON p.id=e.payee_id WHERE e.id=?");
        $row->execute([(int)$_GET['single']]);
        $exp = $row->fetch();
        if (!$exp) jsonError('Expense not found', 404);
        jsonOk($exp);
    }

    // Categories list
    if (!empty($_GET['categories'])) {
        $cats = $pdo->query("SELECT id, name FROM expense_categories ORDER BY name")->fetchAll();
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

    $sql  = "SELECT e.*, v.name AS vendor_name, p.name AS payee_name, p.type AS payee_type
             FROM expenses e
             LEFT JOIN vendors v ON v.id = e.vendor_id
             LEFT JOIN payees  p ON p.id = e.payee_id
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
    $pdo->prepare("INSERT INTO expenses (expense_date, category, amount, vendor_id, payee_id, reference_no, notes, created_by)
                   VALUES (?,?,?,?,?,?,?,?)")
        ->execute([
            $b['expense_date'],
            trim($b['category']),
            round((float)$b['amount'], 2),
            !empty($b['vendor_id'])  ? (int)$b['vendor_id']  : null,
            !empty($b['payee_id'])   ? (int)$b['payee_id']   : null,
            trim($b['reference_no'] ?? ''),
            trim($b['notes'] ?? ''),
            $createdBy,
        ]);
    $id = (int)$pdo->lastInsertId();
    auditLog($pdo, 'create', 'expense', $id, "Expense: {$b['category']} ₹{$b['amount']} on {$b['expense_date']}");
    jsonOk(['id' => $id], 'Expense recorded');
}

if ($method === 'PUT') {
    requireRole('admin');
    $b = getBody();
    requireFields($b, ['id','expense_date','amount','category']);
    $pdo->prepare("UPDATE expenses SET expense_date=?, category=?, amount=?, vendor_id=?, payee_id=?, reference_no=?, notes=? WHERE id=?")
        ->execute([
            $b['expense_date'],
            trim($b['category']),
            round((float)$b['amount'], 2),
            !empty($b['vendor_id'])  ? (int)$b['vendor_id']  : null,
            !empty($b['payee_id'])   ? (int)$b['payee_id']   : null,
            trim($b['reference_no'] ?? ''),
            trim($b['notes'] ?? ''),
            (int)$b['id'],
        ]);
    auditLog($pdo, 'update', 'expense', (int)$b['id'], "Updated expense #{$b['id']}");
    jsonOk([], 'Expense updated');
}

if ($method === 'DELETE') {
    requireRole('admin');
    // Delete category
    if (!empty($_GET['category'])) {
        $name = trim($_GET['category']);
        $pdo->prepare("DELETE FROM expense_categories WHERE name=?")->execute([$name]);
        jsonOk([], 'Category deleted');
    }
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('ID required');
    $pdo->prepare("DELETE FROM expenses WHERE id=?")->execute([$id]);
    auditLog($pdo, 'delete', 'expense', $id, "Deleted expense #$id");
    jsonOk([], 'Expense deleted');
}
