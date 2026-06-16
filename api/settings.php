<?php
/**
 * Settings API
 * GET /api/settings.php              → all settings (public ones only)
 * PUT /api/settings.php              → update (admin only)
 */
require __DIR__.'/../includes/db.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,PUT,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}
startSession();
$pdo=getDB();
if ($_SERVER['REQUEST_METHOD']==='GET') {
    $rows=$pdo->query("SELECT k,v FROM settings WHERE k NOT IN ('smtp_pass')")->fetchAll(PDO::FETCH_KEY_PAIR);
    jsonOk($rows);
}
if ($_SERVER['REQUEST_METHOD']==='PUT') {
    requireRole('admin');
    $b=getBody();
    $allowed=['business_name','business_address','business_phone','business_email','business_gst','sidebar_tagline','currency_symbol','invoice_prefix','po_prefix','tax_rate','case_margin','low_stock_email','smtp_host','smtp_port','smtp_user','smtp_pass'];
    $stmt=$pdo->prepare("INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)");
    foreach ($allowed as $key) {
        if (array_key_exists($key,$b)) $stmt->execute([$key,$b[$key]]);
    }
    auditLog($pdo,'update_settings','settings',0);
    jsonOk(null,'Settings saved');
}
