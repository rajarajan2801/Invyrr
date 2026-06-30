<?php
// One-time migration: adds entity_id column to expenses table
// Visit once, then delete this file.
require __DIR__ . '/includes/db.php';
startSession(); requireAuth();
header('Content-Type: text/plain');

$pdo = getDB();

// 1. Create expense_entities table
$pdo->exec("CREATE TABLE IF NOT EXISTS expense_entities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "✅ expense_entities table ready\n";

// 2. Add entity_id column to expenses
try {
    $pdo->exec("ALTER TABLE expenses ADD COLUMN entity_id INT DEFAULT NULL");
    echo "✅ entity_id column added to expenses\n";
} catch (PDOException $e) {
    echo "ℹ️  entity_id: " . $e->getMessage() . "\n";
}

// 3. Add paid_to_id column (in case it's also missing)
try {
    $pdo->exec("ALTER TABLE expenses ADD COLUMN paid_to_id INT DEFAULT NULL");
    echo "✅ paid_to_id column added\n";
} catch (PDOException $e) {
    echo "ℹ️  paid_to_id: " . $e->getMessage() . "\n";
}

// 4. Add FK (silently ignore if fails — Railway may restrict cross-table FKs)
try {
    $pdo->exec("ALTER TABLE expenses ADD CONSTRAINT fk_exp_entity
        FOREIGN KEY (entity_id) REFERENCES expense_entities(id) ON DELETE SET NULL");
    echo "✅ FK fk_exp_entity added\n";
} catch (PDOException $e) {
    echo "ℹ️  FK skipped: " . $e->getMessage() . "\n";
}

// 5. Verify columns
$cols = $pdo->query("SHOW COLUMNS FROM expenses")->fetchAll(PDO::FETCH_COLUMN);
echo "\nCurrent expenses columns:\n" . implode(', ', $cols) . "\n\n";
echo "⚠️  Delete migrate_entity.php now!\n";
