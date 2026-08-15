<?php
// One-time migration: adds 'Cashier' to users.role ENUM
// (a brand-new role, so unlike migrate_rename_*.php there's no existing
// data to move -- this just widens the allowed value set).
// Visit once, confirm success, then delete this file from the repo.
require __DIR__ . '/includes/db.php';

header('Content-Type: text/plain');

// Simple secret check — same pattern as the other migrate_*.php scripts
$secret = _env('BACKUP_SECRET', '');
if ($secret && ($_GET['secret'] ?? '') !== $secret) {
    http_response_code(403);
    die("Forbidden. Pass ?secret=YOUR_BACKUP_SECRET");
}

$pdo = getDB();

try {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin','manager','Picker','partner','Cashier') NOT NULL DEFAULT 'Picker'");
    echo "SUCCESS: 'Cashier' role added to users table.\n\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Cashier') !== false) {
        echo "Already done: role ENUM already contains 'Cashier'.\n\n";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n\n";
        exit(1);
    }
}

// Verify
$row = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
echo "Current ENUM: " . $row['Type'] . "\n\n";

echo "Delete migrate_add_cashier_role.php from your repo now!\n";
