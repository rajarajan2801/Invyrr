<?php
// TEMPORARY DEBUG FILE — DELETE AFTER FIXING CONNECTION
// Visit: https://your-app.railway.app/dbcheck.php
require_once __DIR__ . '/includes/db.php';

echo "<pre>";
echo "DB_HOST:  " . $_DB_HOST . "\n";
echo "DB_PORT:  " . $_DB_PORT . "\n";
echo "DB_NAME:  " . $_DB_NAME . "\n";
echo "DB_USER:  " . $_DB_USER . "\n";
echo "DB_PASS:  " . (empty($_DB_PASS) ? '(empty)' : '(set)') . "\n\n";

try {
    $pdo = getDB();
    echo "✅ Connection successful!\n";
    $r = $pdo->query("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");
    echo "Tables in DB: " . $r->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "</pre>";
