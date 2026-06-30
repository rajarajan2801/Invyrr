<?php
// ── One-time fixup: re-link expenses to the correct Payee by name+type ───
// Run once after deploying the import.php fix, then delete this file.
require __DIR__ . '/includes/db.php';
startSession(); requireAuth();
header('Content-Type: text/plain');

$pdo = getDB();

echo "Scanning expenses for mismatched payee links...\n\n";

// Get all distinct payee names with multiple payee records (different types)
$dupNames = $pdo->query("
    SELECT LOWER(TRIM(name)) AS name_key, COUNT(*) AS cnt
    FROM payees
    GROUP BY LOWER(TRIM(name))
    HAVING COUNT(*) > 1
")->fetchAll(PDO::FETCH_ASSOC);

if (!$dupNames) {
    echo "No duplicate payee names found. Nothing to fix.\n";
    exit;
}

echo "Found " . count($dupNames) . " payee name(s) with multiple type records:\n";
foreach ($dupNames as $d) {
    echo "  - {$d['name_key']} ({$d['cnt']} records)\n";
}
echo "\nThis script cannot automatically guess which expense belongs to which\n";
echo "payee type, since the original CSV data isn't stored with the expense.\n\n";
echo "RECOMMENDATION: Manually review and re-assign affected expenses via\n";
echo "Expenses page → Edit (pencil icon) → change the Paid By dropdown to the\n";
echo "correct payee (e.g. select 'Rajarajan (GPAY)' instead of 'Rajarajan (Cash)').\n\n";

// List the affected expenses for easy reference
$rows = $pdo->query("
    SELECT e.id, e.expense_date, e.category, e.amount, p.name, p.type
    FROM expenses e
    JOIN payees p ON p.id = e.payee_id
    WHERE LOWER(TRIM(p.name)) IN (" . implode(',', array_fill(0, count($dupNames), '?')) . ")
    ORDER BY p.name, e.expense_date DESC
");
$rows->execute(array_column($dupNames, 'name_key'));
$expenses = $rows->fetchAll(PDO::FETCH_ASSOC);

echo "Affected expense records (" . count($expenses) . "):\n";
foreach ($expenses as $e) {
    echo "  #{$e['id']}: {$e['expense_date']} | {$e['category']} | ₹{$e['amount']} | {$e['name']} ({$e['type']})\n";
}
