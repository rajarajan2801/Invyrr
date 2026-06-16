<?php
/**
 * Invyrr — New Instance Setup Wizard
 * Visit: http://localhost/YOUR_FOLDER/setup.php
 * DELETE this file after setup is complete.
 */

$dbFile = __DIR__ . '/includes/db.php';
$alreadyConfigured = false;
if (file_exists($dbFile)) {
    $c = file_get_contents($dbFile);
    if (strpos($c, 'your_database') === false && strpos($c, 'DB_NAME') !== false) {
        $alreadyConfigured = true;
    }
}

$step   = 'form';
$result = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dbHost     = trim($_POST['db_host']   ?? 'localhost');
    $dbName     = trim($_POST['db_name']   ?? '');
    $dbUser     = trim($_POST['db_user']   ?? 'root');
    $dbPass     = $_POST['db_pass']        ?? '';
    $bizName    = trim($_POST['biz_name']  ?? 'My Business');
    $adminName  = trim($_POST['admin_name']?? 'Administrator');
    $adminPass  = $_POST['admin_pass']     ?? '';
    $adminPass2 = $_POST['admin_pass2']    ?? '';
    $folder     = trim($_POST['folder']    ?? basename(__DIR__));
    $action     = $_POST['action']         ?? '';

    // ── Test connection only ──────────────────────────────
    if ($action === 'test') {
        try {
            new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $result = ['type' => 'success', 'msg' => '✅ Connected to MySQL successfully!'];
        } catch (PDOException $e) {
            $result = ['type' => 'error', 'msg' => '❌ ' . $e->getMessage()];
        }
    }

    // ── Full install ──────────────────────────────────────
    if ($action === 'install') {
        if (!$dbName)                               $errors[] = 'Database name is required.';
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName ?: '')) $errors[] = 'Database name: letters, numbers and underscores only.';
        if (!$adminName)                            $errors[] = 'Admin username is required.';
        if (strlen($adminPass) < 6)                 $errors[] = 'Password must be at least 6 characters.';
        if ($adminPass !== $adminPass2)             $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            try {
                // Connect without DB
                $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

                // Create DB
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$dbName`");

                // Write db.php — build it line by line to avoid heredoc/quote issues
                $lines = [];
                $lines[] = '<?php';
                $lines[] = "define('DB_HOST', " . var_export($dbHost, true) . ");";
                $lines[] = "define('DB_NAME', " . var_export($dbName, true) . ");";
                $lines[] = "define('DB_USER', " . var_export($dbUser, true) . ");";
                $lines[] = "define('DB_PASS', " . var_export($dbPass, true) . ");";
                $lines[] = "define('DB_CHARSET', 'utf8mb4');";
                $lines[] = '';
                $lines[] = 'function getDB(): PDO {';
                $lines[] = '    static $pdo = null;';
                $lines[] = '    if ($pdo === null) {';
                $lines[] = '        $dsn = sprintf(\'mysql:host=%s;dbname=%s;charset=%s\', DB_HOST, DB_NAME, DB_CHARSET);';
                $lines[] = '        try {';
                $lines[] = '            $pdo = new PDO($dsn, DB_USER, DB_PASS, [';
                $lines[] = '                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,';
                $lines[] = '                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,';
                $lines[] = '                PDO::ATTR_EMULATE_PREPARES   => false,';
                $lines[] = '            ]);';
                $lines[] = '        } catch (PDOException $e) {';
                $lines[] = '            http_response_code(500);';
                $lines[] = '            die(json_encode([\'success\'=>false,\'message\'=>\'DB connection failed: \'.$e->getMessage()]));';
                $lines[] = '        }';
                $lines[] = '    }';
                $lines[] = '    return $pdo;';
                $lines[] = '}';
                $lines[] = '';
                // Copy remaining helpers from original db.php if it exists
                // Otherwise write minimal versions
                $lines[] = 'function jsonOk($data=null, string $message=\'OK\'): void {';
                $lines[] = '    header(\'Content-Type: application/json\');';
                $lines[] = '    echo json_encode([\'success\'=>true,\'message\'=>$message,\'data\'=>$data]);';
                $lines[] = '    exit;';
                $lines[] = '}';
                $lines[] = 'function jsonError(string $message, int $code=400): void {';
                $lines[] = '    http_response_code($code);';
                $lines[] = '    header(\'Content-Type: application/json\');';
                $lines[] = '    echo json_encode([\'success\'=>false,\'message\'=>$message]);';
                $lines[] = '    exit;';
                $lines[] = '}';
                $lines[] = 'function jsonList(array $rows, int $total=-1): void {';
                $lines[] = '    header(\'Content-Type: application/json\');';
                $lines[] = '    echo json_encode([\'success\'=>true,\'data\'=>$rows,\'total\'=>$total>=0?$total:count($rows)]);';
                $lines[] = '    exit;';
                $lines[] = '}';
                $lines[] = 'function getBody(): array { return json_decode(file_get_contents(\'php://input\'), true) ?? $_POST; }';
                $lines[] = 'function requireFields(array $body, array $fields): void {';
                $lines[] = '    foreach ($fields as $f) {';
                $lines[] = '        if (!isset($body[$f]) || (is_string($body[$f]) && trim($body[$f])===\'\'))';
                $lines[] = '            jsonError("Missing required field: $f");';
                $lines[] = '    }';
                $lines[] = '}';
                $lines[] = 'function startSession(): void {';
                $lines[] = '    if (session_status() === PHP_SESSION_NONE) { session_name(\'SM_SESSION\'); session_start(); }';
                $lines[] = '}';
                $lines[] = 'function currentUser(): ?array { startSession(); return $_SESSION[\'user\'] ?? null; }';
                $lines[] = 'function requireAuth(): array {';
                $lines[] = '    $u = currentUser(); if (!$u) jsonError(\'Not authenticated\', 401); return $u;';
                $lines[] = '}';
                $lines[] = 'function requireRole(): array { $roles = func_get_args();';
                $lines[] = '    $u = requireAuth();';
                $lines[] = '    if (!in_array($u[\'role\'], $roles)) jsonError(\'Insufficient permissions\', 403);';
                $lines[] = '    return $u;';
                $lines[] = '}';
                $lines[] = 'function auditLog(PDO $pdo, string $action, string $entity=\'\', int $entityId=0, string $detail=\'\'): void {';
                $lines[] = '    $u = currentUser();';
                $lines[] = '    try { $pdo->prepare("INSERT INTO audit_log (user_id,user_name,action,entity,entity_id,detail,ip) VALUES (?,?,?,?,?,?,?)")';
                $lines[] = '        ->execute([$u[\'id\']??null,$u[\'name\']??\'system\',$action,$entity,$entityId?:null,$detail,$_SERVER[\'REMOTE_ADDR\']??\'\']); } catch (Throwable $e) {}';
                $lines[] = '}';
                $lines[] = 'function getSetting(PDO $pdo, string $key, string $default=\'\'): string {';
                $lines[] = '    $stmt = $pdo->prepare("SELECT v FROM settings WHERE k = ?"); $stmt->execute([$key]);';
                $lines[] = '    $r = $stmt->fetchColumn(); return $r !== false ? (string)$r : $default;';
                $lines[] = '}';

                file_put_contents($dbFile, implode("\n", $lines) . "\n");

                // Run schema from install.php
                $installSrc = file_get_contents(__DIR__ . '/install.php');
                preg_match('/\$tables = <<<SQL(.+?)SQL;/s', $installSrc, $m);
                if (!empty($m[1])) {
                    foreach (array_filter(array_map('trim', explode(';', $m[1]))) as $s) {
                        if (trim($s)) $pdo->exec($s);
                    }
                }

                // Column migrations
                $migs = [
                    ['products','brand',        "ALTER TABLE products ADD COLUMN brand VARCHAR(150) AFTER sku"],
                    ['vendors', 'type',         "ALTER TABLE vendors ADD COLUMN type VARCHAR(50) DEFAULT '' AFTER name"],
                    ['products','item_code',    "ALTER TABLE products ADD COLUMN item_code INT UNSIGNED DEFAULT NULL AFTER sku"],
                    ['products','image',        "ALTER TABLE products ADD COLUMN image VARCHAR(255) AFTER description"],
                    ['products','case_content', "ALTER TABLE products ADD COLUMN case_content INT UNSIGNED DEFAULT NULL AFTER image"],
                    ['products','landing_cost', "ALTER TABLE products ADD COLUMN landing_cost DECIMAL(12,2) DEFAULT NULL AFTER case_content"],
                    ['products','combo',        "ALTER TABLE products ADD COLUMN combo TINYINT(1) NOT NULL DEFAULT 0 AFTER landing_cost"],
                ];
                foreach ($migs as [$tbl, $col, $sql]) {
                    if (empty($pdo->query("SHOW COLUMNS FROM `$tbl` LIKE '$col'")->fetchAll()))
                        $pdo->exec($sql);
                }

                // Categories table
                $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(150) NOT NULL UNIQUE,
                    description VARCHAR(500) DEFAULT '',
                    color VARCHAR(20) DEFAULT '',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // Default location
                if (!(int)$pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn()) {
                    $pdo->exec("INSERT INTO locations (name, is_default) VALUES ('Main Store', 1)");
                }

                // Admin user
                if (!(int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()) {
                    $hash = password_hash($adminPass, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
                    $stmt->execute([$adminName, 'admin@invyrr.local', $hash, 'admin']);
                }

                // Default settings
                $ins = $pdo->prepare("INSERT IGNORE INTO settings (k,v) VALUES (?,?)");
                foreach ([
                    'business_name'   => $bizName,
                    'currency_symbol' => '₹',
                    'invoice_prefix'  => 'INV',
                    'po_prefix'       => 'PO',
                    'tax_rate'        => '0',
                    'smtp_port'       => '587',
                ] as $k => $v) $ins->execute([$k, $v]);

                // Directories
                foreach (['/backups', '/assets/uploads/products'] as $d) {
                    $p = __DIR__ . $d;
                    if (!is_dir($p)) mkdir($p, 0755, true);
                }
                @file_put_contents(__DIR__ . '/backups/.htaccess', "Deny from all\n");
                @file_put_contents(__DIR__ . '/assets/uploads/.htaccess', "php_flag engine off\nOptions -ExecCGI\n");

                $step   = 'done';
                $result = compact('folder', 'dbName', 'adminName', 'bizName');

            } catch (Throwable $e) {
                $step   = 'error';
                $result = ['msg' => $e->getMessage()];
            }
        } else {
            $result = ['type' => 'error', 'msg' => implode('<br>', $errors)];
        }
    }
}

$folder = $_POST['folder'] ?? basename(__DIR__);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Invyrr — Setup Wizard</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:#0f1117;color:#e8eaf0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.card{background:#181c26;border:1px solid #2a3050;border-radius:14px;width:100%;max-width:560px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.5)}
.card-top{padding:28px 32px 20px;border-bottom:1px solid #2a3050}
.logo{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.logo-icon{width:42px;height:42px;border-radius:11px;background:linear-gradient(135deg,#4f8eff,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:1.3rem}
.logo-name{font-size:1.15rem;font-weight:700}
.logo-sub{font-size:.7rem;color:#4a5578;font-family:monospace;margin-top:2px}
h2{font-size:1.05rem;font-weight:700}
.sub{font-size:.81rem;color:#8892b0;margin-top:4px}
.body{padding:24px 32px 28px}
.sec{font-size:.67rem;color:#4a5578;text-transform:uppercase;letter-spacing:1.2px;font-weight:700;margin:20px 0 10px;padding-bottom:6px;border-bottom:1px solid #2a3050}
.sec:first-child{margin-top:0}
.fg{margin-bottom:13px}
label{display:block;font-size:.71rem;color:#8892b0;text-transform:uppercase;letter-spacing:.6px;font-weight:600;margin-bottom:4px}
input,select{width:100%;background:#1e2333;border:1.5px solid #2a3050;color:#e8eaf0;padding:9px 12px;border-radius:8px;font-size:.88rem;outline:none;transition:border .2s;font-family:inherit}
input:focus,select:focus{border-color:#4f8eff;box-shadow:0 0 0 3px rgba(79,142,255,.1)}
.hint{font-size:.71rem;color:#4a5578;margin-top:3px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.btn{display:flex;align-items:center;justify-content:center;gap:7px;padding:10px 20px;border-radius:25px;border:none;font-size:.87rem;font-weight:600;cursor:pointer;width:100%;margin-top:6px;font-family:inherit;transition:all .2s}
.btn-primary{background:#4f8eff;color:#fff;box-shadow:0 2px 12px rgba(79,142,255,.25)}
.btn-primary:hover{background:#3a7ae0}
.btn-primary:disabled{opacity:.5;cursor:not-allowed}
.btn-ghost{background:#1e2333;color:#8892b0;margin-top:8px}
.btn-ghost:hover{color:#e8eaf0}
.alert{padding:11px 14px;border-radius:7px;font-size:.83rem;margin-bottom:14px;border-left:3px solid}
.ok{background:rgba(34,197,94,.1);border-color:#22c55e;color:#22c55e}
.err{background:rgba(239,68,68,.1);border-color:#ef4444;color:#ef4444}
.done-row{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid #2a3050;font-size:.84rem}
.done-row:last-child{border:none}
.dl{color:#8892b0}
.dv{font-family:monospace;color:#e8eaf0;font-weight:600}
.badge{background:rgba(79,142,255,.15);color:#4f8eff;padding:2px 9px;border-radius:20px;font-size:.72rem}
.warn{background:rgba(234,179,8,.08);border:1px solid rgba(234,179,8,.2);border-radius:8px;padding:11px 14px;font-size:.79rem;color:#eab308;margin-top:14px;line-height:1.5}
</style>
</head>
<body>
<div class="card">
  <div class="card-top">
    <div class="logo">
      <div class="logo-icon">&#x1F4E6;</div>
      <div><div class="logo-name">Invyrr</div><div class="logo-sub">Setup Wizard</div></div>
    </div>
    <?php if ($step === 'form'): ?>
      <h2>Create a new instance</h2>
      <p class="sub">Sets up a fresh database and configures the app for a new business.</p>
    <?php elseif ($step === 'done'): ?>
      <h2>&#x2705; Installation Complete!</h2>
      <p class="sub">Your new Invyrr instance is ready to use.</p>
    <?php else: ?>
      <h2>&#x274C; Installation Failed</h2>
      <p class="sub">Something went wrong. See the error below.</p>
    <?php endif; ?>
  </div>

  <div class="body">

  <?php if ($step === 'done' && $result): ?>
    <div style="text-align:center;font-size:2.5rem;margin-bottom:16px">&#x1F389;</div>
    <div class="done-row"><span class="dl">Business</span><span class="dv"><?= htmlspecialchars($result['bizName']) ?></span></div>
    <div class="done-row"><span class="dl">Database</span><span class="dv badge"><?= htmlspecialchars($result['dbName']) ?></span></div>
    <div class="done-row"><span class="dl">Login username</span><span class="dv"><?= htmlspecialchars($result['adminName']) ?></span></div>
    <div class="done-row"><span class="dl">Login password</span><span class="dv">Your chosen password</span></div>
    <div class="done-row"><span class="dl">URL</span><span class="dv">http://localhost/<?= htmlspecialchars($result['folder']) ?>/</span></div>
    <a href="index.php" class="btn btn-primary" style="text-decoration:none;margin-top:18px">&#x1F680; Open App</a>
    <div class="warn">&#x26A0;&#xFE0F; <strong>Delete setup.php now</strong> &mdash; it can overwrite your database if visited again.</div>

  <?php elseif ($step === 'error'): ?>
    <div class="alert err"><?= htmlspecialchars($result['msg'] ?? 'Unknown error') ?></div>
    <a href="setup.php" class="btn btn-ghost" style="text-decoration:none">&#x2190; Try Again</a>

  <?php else: ?>

    <?php if ($alreadyConfigured): ?>
      <div class="alert err">&#x26A0;&#xFE0F; This instance is already installed. Copy the folder to a new location to create another instance.</div>
    <?php endif; ?>

    <?php if ($result): ?>
      <div class="alert <?= $result['type'] === 'success' ? 'ok' : 'err' ?>"><?= $result['msg'] ?></div>
    <?php endif; ?>

    <form method="POST">

      <div class="sec">Business</div>
      <div class="fg">
        <label>Business Name</label>
        <input type="text" name="biz_name" value="<?= htmlspecialchars($_POST['biz_name'] ?? 'My Business') ?>" placeholder="e.g. Raj Fireworks">
      </div>
      <div class="fg">
        <label>Folder Name</label>
        <input type="text" name="folder" value="<?= htmlspecialchars($_POST['folder'] ?? basename(__DIR__)) ?>" placeholder="invyrr2">
        <div class="hint">Your URL: http://localhost/<?= htmlspecialchars($_POST['folder'] ?? basename(__DIR__)) ?>/</div>
      </div>

      <div class="sec">Database</div>
      <div class="row">
        <div class="fg">
          <label>MySQL Host</label>
          <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>">
        </div>
        <div class="fg">
          <label>Database Name *</label>
          <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" placeholder="invyrr2" required>
          <div class="hint">Created automatically if missing</div>
        </div>
      </div>
      <div class="row">
        <div class="fg">
          <label>MySQL Username</label>
          <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>">
        </div>
        <div class="fg">
          <label>MySQL Password</label>
          <input type="password" name="db_pass" placeholder="(blank for XAMPP default)">
          <div class="hint">Leave blank if no password</div>
        </div>
      </div>
      <button type="submit" name="action" value="test" class="btn btn-ghost">&#x1F50C; Test DB Connection</button>

      <div class="sec">Admin Account</div>
      <div class="fg">
        <label>Username *</label>
        <input type="text" name="admin_name" value="<?= htmlspecialchars($_POST['admin_name'] ?? 'Administrator') ?>" placeholder="Administrator">
        <div class="hint">Used to log in — can be any name</div>
      </div>
      <div class="row">
        <div class="fg">
          <label>Password * (min 6 chars)</label>
          <input type="password" name="admin_pass" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
        </div>
        <div class="fg">
          <label>Confirm Password *</label>
          <input type="password" name="admin_pass2" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
        </div>
      </div>

      <button type="submit" name="action" value="install"
        class="btn btn-primary" style="margin-top:14px"
        <?= $alreadyConfigured ? 'disabled' : '' ?>>
        &#x1F680; Install Invyrr
      </button>

    </form>
  <?php endif; ?>
  </div>
</div>
</body>
</html>
