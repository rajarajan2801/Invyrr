<?php
// One-time migration: renames the 'cashier' role to 'RRC-Staff'
// everywhere it's used on the users table.
// Visit once (e.g. https://your-app/migrate_rename_cashier_role.php?secret=YOUR_BACKUP_SECRET),
// confirm it reports success, then delete this file from the repo.
require __DIR__ . '/includes/db.php';

header('Content-Type: text/plain');

// Simple secret check — same pattern as migrate_roles.php
$secret = _env('BACKUP_SECRET', '');
if ($secret && ($_GET['secret'] ?? '') !== $secret) {
    http_response_code(403);
    die("Forbidden. Pass ?secret=YOUR_BACKUP_SECRET");
}

$pdo = getDB();

try {
    // Step 1: widen the ENUM so both the old and new values are valid at
    // once — needed before we can UPDATE existing 'cashier' rows without
    // MySQL truncating them to ''.
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin','manager','cashier','partner','RRC-Staff') NOT NULL DEFAULT 'RRC-Staff'");
    echo "Step 1/3: ENUM widened to include both 'cashier' and 'RRC-Staff'.\n";

    // Step 2: migrate existing rows.
    $n = $pdo->exec("UPDATE users SET role = 'RRC-Staff' WHERE role = 'cashier'");
    echo "Step 2/3: Updated $n user(s) from 'cashier' to 'RRC-Staff'.\n";

    // Step 3: narrow the ENUM back down now that no row uses 'cashier'.
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin','manager','RRC-Staff','partner') NOT NULL DEFAULT 'RRC-Staff'");
    echo "Step 3/3: ENUM narrowed to final set (admin, manager, RRC-Staff, partner).\n\n";

    echo "SUCCESS.\n\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Verify
$row = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
echo "Current ENUM: " . $row['Type'] . "\n\n";

$counts = $pdo->query("SELECT role, COUNT(*) cnt FROM users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);
echo "Current role counts:\n";
foreach ($counts as $c) {
    echo "  {$c['role']}: {$c['cnt']}\n";
}

echo "\nDelete migrate_rename_cashier_role.php from your repo now!\n";
