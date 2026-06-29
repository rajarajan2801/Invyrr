<?php
// One-time migration: adds 'partner' to users.role ENUM
// Visit once, then delete this file immediately.
require __DIR__ . '/includes/db.php';

header('Content-Type: text/plain');

// Simple secret check
$secret = _env('BACKUP_SECRET', '');
if ($secret && ($_GET['secret'] ?? '') !== $secret) {
    http_response_code(403);
    die("Forbidden. Pass ?secret=YOUR_BACKUP_SECRET");
}

$pdo = getDB();

try {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin','manager','cashier','partner') NOT NULL DEFAULT 'cashier'");
    echo "✅ SUCCESS: 'partner' role added to users table.\n\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'partner') !== false || $e->getCode() === '01000') {
        echo "✅ Already done: role ENUM already contains 'partner'.\n\n";
    } else {
        echo "❌ ERROR: " . $e->getMessage() . "\n\n";
        exit(1);
    }
}

// Verify
$row = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
echo "Current ENUM: " . $row['Type'] . "\n\n";
echo "⚠️  Delete migrate_roles.php from your repo now!\n";
