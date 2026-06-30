<?php
// One-time migration for entity_id + paid_to_id columns
// Visit: /migrate_entity.php?secret=YOUR_BACKUP_SECRET
require __DIR__ . '/includes/db.php';
$secret = _env('BACKUP_SECRET','');
if($secret && ($_GET['secret']??'')!==$secret){ http_response_code(403); die("Forbidden"); }
header('Content-Type: text/plain');
$pdo = getDB();

// 1. Create expense_entities table
$pdo->exec("CREATE TABLE IF NOT EXISTS expense_entities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "✅ expense_entities table OK\n";

// 2. Add missing columns one by one — each in its own try/catch
$cols_to_add = [
    'paid_to_id' => 'INT DEFAULT NULL',
    'entity_id'  => 'INT DEFAULT NULL',
];
foreach ($cols_to_add as $col => $def) {
    // Check if column already exists
    $exists = $pdo->query("SHOW COLUMNS FROM expenses LIKE '$col'")->rowCount() > 0;
    if ($exists) {
        echo "ℹ️  $col already exists\n";
    } else {
        try {
            $pdo->exec("ALTER TABLE expenses ADD COLUMN $col $def");
            echo "✅ $col column ADDED\n";
        } catch (PDOException $e) {
            echo "❌ $col failed: " . $e->getMessage() . "\n";
        }
    }
}

// 3. Show current columns
$cols = array_column($pdo->query("SHOW COLUMNS FROM expenses")->fetchAll(PDO::FETCH_ASSOC), 'Field');
echo "\nCurrent expenses columns:\n  " . implode(", ", $cols) . "\n\n";

// 4. Check if any expenses have entity_id set
$count = $pdo->query("SELECT COUNT(*) FROM expenses WHERE entity_id IS NOT NULL")->fetchColumn();
echo "Expenses with entity_id set: $count\n\n";

echo "✅ Done. Delete this file now!\n";
