<?php
/**
 * Auth API
 * POST /api/auth.php?action=login   → {email, password}
 * POST /api/auth.php?action=logout
 * GET  /api/auth.php?action=me      → current user
 */
require __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
startSession();

$action = $_GET['action'] ?? 'me';
$pdo    = getDB();

if ($action === 'me') {
    $u = currentUser();
    if (!$u) jsonError('Not authenticated', 401);
    jsonOk($u);
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    jsonOk(null, 'Logged out');
}

// Ensure theme column exists on users table
try { $pdo->exec("ALTER TABLE users ADD COLUMN theme VARCHAR(30) DEFAULT 'midnight'"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN font VARCHAR(30) DEFAULT 'inter'"); } catch (Exception $e) {}

if ($action === 'login') {
    $b = getBody();
    requireFields($b, ['username','password']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE name=? AND is_active=1");
    $stmt->execute([trim($b['username'])]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($b['password'], $user['password']))
        jsonError('Invalid username or password', 401);
    $pdo->prepare("UPDATE users SET last_login=NOW() WHERE id=?")->execute([$user['id']]);
    unset($user['password']);
    $_SESSION['user'] = $user;
    auditLog($pdo, 'login', 'user', $user['id']);
    jsonOk($user, 'Welcome, '.$user['name']);
}

if ($action === 'set_theme') {
    $u = requireAuth();
    $b = getBody();
    $theme = trim($b['theme'] ?? '');
    $allowedThemes = ['midnight','emerald','crimson','amber','violet','teal'];
    if (!in_array($theme, $allowedThemes, true)) jsonError('Invalid theme');
    $pdo->prepare("UPDATE users SET theme=? WHERE id=?")->execute([$theme, $u['id']]);
    $_SESSION['user']['theme'] = $theme;
    jsonOk(['theme'=>$theme], 'Theme updated');
}

if ($action === 'set_font') {
    $u = requireAuth();
    $b = getBody();
    $font = trim($b['font'] ?? '');
    $allowedFonts = ['inter','outfit','jakarta','manrope','lexend'];
    if (!in_array($font, $allowedFonts, true)) jsonError('Invalid font');
    $pdo->prepare("UPDATE users SET font=? WHERE id=?")->execute([$font, $u['id']]);
    $_SESSION['user']['font'] = $font;
    jsonOk(['font'=>$font], 'Font updated');
}
