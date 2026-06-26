<?php
header('Content-Type: text/plain');
require __DIR__ . '/includes/db.php';
startSession();
echo "PHP: OK\n";
echo "Session: " . (isset($_SESSION['user']) ? $_SESSION['user']['name'] : 'not logged in') . "\n";
try { $pdo=getDB(); echo "DB: OK\n"; } catch(Exception $e){ echo "DB ERROR: ".$e->getMessage()."\n"; }