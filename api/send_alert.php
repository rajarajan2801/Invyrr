<?php
/**
 * Low Stock Email Alerts
 *
 * POST /api/send_alert.php              → send low-stock digest email
 *   body: { test: true }               → send a test email to configured address
 *   body: {}                           → send real low-stock alert (also called by cron)
 *
 * This file works in two modes:
 *  1. PHPMailer (if composer vendor/autoload.php exists) — recommended for SMTP
 *  2. PHP mail()  fallback — works on servers with sendmail configured
 *
 * To install PHPMailer:  composer require phpmailer/phpmailer
 */
require __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

// Allow both authenticated web calls AND unauthenticated cron calls (from localhost only)
startSession();
$isCron = (php_sapi_name() === 'cli') || ($_SERVER['REMOTE_ADDR'] ?? '') === '127.0.0.1';
if (!$isCron) {
    requireAuth();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !$isCron) {
    jsonError('Method not allowed', 405);
}

$pdo  = getDB();
$body = getBody();

// ── Load SMTP settings ────────────────────────────────────────────────────
function getSMTPSetting(PDO $pdo, string $key): string
{
    static $cache = null;
    if ($cache === null) {
        $rows  = $pdo->query("SELECT k, v FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $cache = $rows ?: [];
    }
    return (string)($cache[$key] ?? '');
}

$alertEmail = getSMTPSetting($pdo, 'low_stock_email');
$smtpHost   = getSMTPSetting($pdo, 'smtp_host');
$smtpPort   = (int) getSMTPSetting($pdo, 'smtp_port') ?: 587;
$smtpUser   = getSMTPSetting($pdo, 'smtp_user');
$smtpPass   = getSMTPSetting($pdo, 'smtp_pass');
$bizName    = getSMTPSetting($pdo, 'business_name') ?: 'Invyrr';

if (!$alertEmail) {
    jsonError('No alert email configured. Go to Settings → Email Alerts.');
}

// ── Build email content ───────────────────────────────────────────────────
$isTest = !empty($body['test']);

if ($isTest) {
    $subject = "[{$bizName}] Test Email — Invyrr Alerts Working ✅";
    $html    = emailLayout($bizName, '
        <h2 style="margin:0 0 8px">✅ Test Email</h2>
        <p style="color:#8892b0;margin:0 0 20px">If you received this, your email alert configuration is working correctly.</p>
        <table style="width:100%;border-collapse:collapse">
          <tr style="background:#1e2333"><th style="padding:10px;text-align:left;color:#8892b0;font-size:12px">Setting</th><th style="padding:10px;text-align:left;color:#8892b0;font-size:12px">Value</th></tr>
          <tr><td style="padding:10px;border-bottom:1px solid #2a3050">SMTP Host</td><td style="padding:10px;border-bottom:1px solid #2a3050;font-family:monospace">' . htmlspecialchars($smtpHost ?: 'PHP mail()') . '</td></tr>
          <tr><td style="padding:10px;border-bottom:1px solid #2a3050">SMTP Port</td><td style="padding:10px;border-bottom:1px solid #2a3050;font-family:monospace">' . $smtpPort . '</td></tr>
          <tr><td style="padding:10px">Alert Recipient</td><td style="padding:10px;font-family:monospace">' . htmlspecialchars($alertEmail) . '</td></tr>
        </table>
    ');
    $text = "Test email from {$bizName} Invyrr. Email alerts are configured correctly.";
} else {
    // Fetch low stock products
    $lowStock = $pdo->query("
        SELECT p.name, p.sku, p.stock, p.min_stock, p.unit,
               v.name AS vendor_name
        FROM products p
        LEFT JOIN vendors v ON v.id = p.vendor_id
        WHERE p.stock <= p.min_stock AND p.min_stock > 0
        ORDER BY (p.min_stock - p.stock) DESC
    ")->fetchAll();

    if (empty($lowStock)) {
        jsonOk(null, 'No low stock items — no email sent.');
    }

    $count   = count($lowStock);
    $subject = "[{$bizName}] ⚠️ Low Stock Alert — {$count} item" . ($count !== 1 ? 's' : '') . " need restocking";

    $rows = '';
    foreach ($lowStock as $p) {
        $shortage = max(0, $p['min_stock'] - $p['stock']);
        $color    = $p['stock'] <= 0 ? '#ef4444' : '#eab308';
        $status   = $p['stock'] <= 0 ? 'OUT OF STOCK' : 'LOW STOCK';
        $rows    .= '
          <tr>
            <td style="padding:10px;border-bottom:1px solid #2a3050;font-weight:600">' . htmlspecialchars($p['name']) . '</td>
            <td style="padding:10px;border-bottom:1px solid #2a3050;font-family:monospace;color:#8892b0">' . htmlspecialchars($p['sku'] ?? '—') . '</td>
            <td style="padding:10px;border-bottom:1px solid #2a3050;font-family:monospace;color:' . $color . ';font-weight:700">' . $p['stock'] . ' ' . htmlspecialchars($p['unit']) . '</td>
            <td style="padding:10px;border-bottom:1px solid #2a3050;font-family:monospace">' . $p['min_stock'] . '</td>
            <td style="padding:10px;border-bottom:1px solid #2a3050;font-family:monospace;color:#ef4444">' . $shortage . '</td>
            <td style="padding:10px;border-bottom:1px solid #2a3050;color:#8892b0">' . htmlspecialchars($p['vendor_name'] ?? '—') . '</td>
            <td style="padding:10px;border-bottom:1px solid #2a3050"><span style="background:' . ($p['stock'] <= 0 ? 'rgba(239,68,68,.15)' : 'rgba(234,179,8,.15)') . ';color:' . $color . ';padding:3px 8px;border-radius:20px;font-size:11px;font-weight:700">' . $status . '</span></td>
          </tr>';
    }

    $html = emailLayout($bizName, '
        <h2 style="margin:0 0 4px;color:#ef4444">⚠️ Low Stock Alert</h2>
        <p style="color:#8892b0;margin:0 0 20px">' . $count . ' product' . ($count !== 1 ? 's are' : ' is') . ' below minimum stock level and need restocking.</p>
        <table style="width:100%;border-collapse:collapse">
          <thead>
            <tr style="background:#1e2333">
              <th style="padding:10px;text-align:left;color:#8892b0;font-size:11px;text-transform:uppercase">Product</th>
              <th style="padding:10px;text-align:left;color:#8892b0;font-size:11px;text-transform:uppercase">SKU</th>
              <th style="padding:10px;text-align:left;color:#8892b0;font-size:11px;text-transform:uppercase">Current</th>
              <th style="padding:10px;text-align:left;color:#8892b0;font-size:11px;text-transform:uppercase">Min</th>
              <th style="padding:10px;text-align:left;color:#8892b0;font-size:11px;text-transform:uppercase">Shortage</th>
              <th style="padding:10px;text-align:left;color:#8892b0;font-size:11px;text-transform:uppercase">Vendor</th>
              <th style="padding:10px;text-align:left;color:#8892b0;font-size:11px;text-transform:uppercase">Status</th>
            </tr>
          </thead>
          <tbody>' . $rows . '</tbody>
        </table>
        <p style="margin:20px 0 0;font-size:13px;color:#4a5578">This alert was generated by Invyrr. Log in to record stock-in transactions.</p>
    ');

    $text = "LOW STOCK ALERT from {$bizName}\n\n{$count} products need restocking:\n\n";
    foreach ($lowStock as $p) {
        $text .= "- {$p['name']} (SKU: {$p['sku']}): {$p['stock']}{$p['unit']} / min {$p['min_stock']}\n";
    }
}

// ── Send the email ─────────────────────────────────────────────────────────
$sent = false;
$err  = '';

// Try PHPMailer first if available
$mailerPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($mailerPath) && $smtpHost) {
    require $mailerPath;
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = !empty($smtpUser);
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = $smtpPort == 465 ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;
        $mail->setFrom($smtpUser ?: 'noreply@invyrr.local', $bizName);
        $mail->addAddress($alertEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $text;
        $mail->send();
        $sent = true;
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}

// Fallback to PHP mail()
if (!$sent) {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$bizName} <noreply@invyrr.local>\r\n";
    $sent = @mail($alertEmail, $subject, $html, $headers);
    if (!$sent) $err = 'PHP mail() failed. Configure SMTP in Settings for reliable delivery.';
}

if ($sent) {
    if (!$isTest) {
        auditLog($pdo, 'send_alert', 'low_stock', 0, "Sent to {$alertEmail}");
    }
    jsonOk(['recipient' => $alertEmail], $isTest ? 'Test email sent to ' . $alertEmail : 'Alert sent to ' . $alertEmail);
} else {
    jsonError('Failed to send email: ' . $err);
}

// ── Email HTML layout ──────────────────────────────────────────────────────
function emailLayout(string $bizName, string $content): string
{
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#0f1117;font-family:\'Segoe UI\',Arial,sans-serif;color:#e8eaf0">
<div style="max-width:700px;margin:0 auto;padding:24px">
  <div style="background:#181c26;border:1px solid #2a3050;border-radius:12px;overflow:hidden">
    <div style="padding:20px 24px;border-bottom:1px solid #2a3050;display:flex;align-items:center;gap:12px">
      <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#4f8eff,#7c3aed);display:inline-flex;align-items:center;justify-content:center;font-size:18px;vertical-align:middle">📦</div>
      <div style="display:inline-block;vertical-align:middle;margin-left:10px">
        <div style="font-weight:700;font-size:16px">' . htmlspecialchars($bizName) . '</div>
        <div style="font-size:12px;color:#4a5578">Invyrr Inventory</div>
      </div>
    </div>
    <div style="padding:24px">' . $content . '</div>
    <div style="padding:16px 24px;border-top:1px solid #2a3050;font-size:12px;color:#4a5578">
      Sent by Invyrr · ' . date('Y-m-d H:i') . '
    </div>
  </div>
</div></body></html>';
}
