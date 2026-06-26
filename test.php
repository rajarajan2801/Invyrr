<?php
// Quick diagnostic - visit /test.php to check if PHP is working
// DELETE THIS FILE AFTER TESTING
header('Content-Type: text/plain');
echo "PHP OK\n";
echo "PHP Version: " . PHP_VERSION . "\n";
require __DIR__ . '/includes/db.php';
echo "db.php loaded OK\n";
try {
    $pdo = getDB();
    echo "DB connected OK\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
} catch (Exception $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
}
startSession();
$user = $_SESSION['user'] ?? null;
echo "Session user: " . ($user ? $user['name'] . ' (' . $user['role'] . ')' : 'not logged in') . "\n";
echo "\nAll good! Delete this file.\n";
