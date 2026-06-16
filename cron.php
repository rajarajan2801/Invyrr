<?php
/**
 * Invyrr Cron Script
 * Run this from the command line or a cron job to send daily low-stock alerts.
 *
 * Usage:
 *   php cron.php                    → send low-stock alert email
 *   php cron.php --dry-run          → show what would be sent, no email
 *
 * Recommended cron (daily at 8am):
 *   0 8 * * * php /path/to/invyrr/cron.php >> /var/log/invyrr_cron.log 2>&1
 */

define('CRON_RUN', true);

// Simulate a POST request to send_alert.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';

$dryRun = in_array('--dry-run', $argv ?? [], true);

if ($dryRun) {
    require __DIR__ . '/includes/db.php';
    $pdo      = getDB();
    $lowStock = $pdo->query("
        SELECT p.name, p.sku, p.stock, p.min_stock, p.unit
        FROM products p
        WHERE p.stock <= p.min_stock AND p.min_stock > 0
        ORDER BY (p.min_stock - p.stock) DESC
    ")->fetchAll();

    if (empty($lowStock)) {
        echo date('Y-m-d H:i:s') . " [DRY RUN] No low-stock items. No email would be sent.\n";
    } else {
        echo date('Y-m-d H:i:s') . " [DRY RUN] Would send alert for " . count($lowStock) . " items:\n";
        foreach ($lowStock as $p) {
            $shortage = max(0, $p['min_stock'] - $p['stock']);
            echo "  - {$p['name']} (SKU:{$p['sku']}) stock={$p['stock']} min={$p['min_stock']} shortage={$shortage}\n";
        }
    }
    exit(0);
}

// Capture output from send_alert.php
ob_start();
$_POST = [];
file_put_contents('php://stdin', '{}');
require __DIR__ . '/api/send_alert.php';
$output = ob_get_clean();

$decoded = json_decode($output, true);
$ts      = date('Y-m-d H:i:s');

if ($decoded && $decoded['success']) {
    echo "{$ts} [OK] {$decoded['message']}\n";
    exit(0);
} else {
    $msg = $decoded['message'] ?? $output;
    echo "{$ts} [ERROR] {$msg}\n";
    exit(1);
}
