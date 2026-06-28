<?php
// ── One-time Google OAuth token generator ─────────────────
// Visit this page to get a refresh token without OAuth Playground.
// DELETE THIS FILE after getting your token!
//
// Step 1: Visit https://invyrr.up.railway.app/get_token.php
// Step 2: Click "Authorize" — sign in with Google
// Step 3: Copy the refresh_token shown
// Step 4: Add it to Railway env vars as GOOGLE_REFRESH_TOKEN
// Step 5: DELETE this file from your repo immediately

$clientId     = getenv('GOOGLE_CLIENT_ID')     ?: '';
$clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: '';
$redirectUri  = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'invyrr.up.railway.app') . '/get_token.php';

if (!$clientId || !$clientSecret) {
    die('<h2>Error</h2><p>GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET must be set in Railway env vars.</p>');
}

// Step 2: Handle the callback with ?code=...
if (!empty($_GET['code'])) {
    $code = $_GET['code'];
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]),
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($resp, true);

    if (!empty($data['refresh_token'])) {
        $rt = htmlspecialchars($data['refresh_token']);
        echo "<!DOCTYPE html><html><head><title>Token Ready</title>
        <style>body{font-family:Arial;max-width:700px;margin:40px auto;padding:20px}
        .token{background:#1e293b;color:#22c55e;padding:16px;border-radius:8px;word-break:break-all;font-family:monospace;font-size:13px}
        .steps{background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:16px;margin-top:20px}
        h2{color:#16a34a}</style></head><body>
        <h2>✅ Refresh Token Generated!</h2>
        <p><strong>Copy this refresh token:</strong></p>
        <div class='token'>{$rt}</div>
        <div class='steps'>
        <strong>Next steps:</strong><br><br>
        1. Go to <strong>Railway → Web Service → Variables</strong><br>
        2. Update <code>GOOGLE_REFRESH_TOKEN</code> with the value above<br>
        3. <strong style='color:red'>DELETE get_token.php from your GitHub repo immediately!</strong><br>
        4. Test: <code>https://invyrr.up.railway.app/cron_backup.php?secret=YOUR_SECRET</code>
        </div></body></html>";
    } else {
        $err = htmlspecialchars($data['error_description'] ?? $resp);
        echo "<h2>❌ Error</h2><pre>{$err}</pre><a href='/get_token.php'>Try again</a>";
    }
    exit;
}

// Step 1: Show authorize button
$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id'             => $clientId,
    'redirect_uri'          => $redirectUri,
    'response_type'         => 'code',
    'scope'                 => 'https://www.googleapis.com/auth/drive.file',
    'access_type'           => 'offline',
    'prompt'                => 'consent',
]);
?>
<!DOCTYPE html>
<html><head><title>Get Google Token</title>
<style>
body{font-family:Arial;max-width:600px;margin:60px auto;padding:20px;text-align:center}
.btn{display:inline-block;background:#4285f4;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-size:16px;font-weight:bold}
.btn:hover{background:#3367d6}
.warn{background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:12px;margin-top:20px;font-size:13px;text-align:left}
</style></head><body>
<h2>🔑 Invyrr — Google Drive Token Setup</h2>
<p>Click below to authorize Invyrr to upload backups to your Google Drive.</p>
<a href="<?= $authUrl ?>" class="btn">🔑 Authorize with Google</a>
<div class="warn">
⚠️ <strong>Security note:</strong> This page grants access to your Google Drive.<br>
Delete <code>get_token.php</code> from your repo immediately after getting the token.
</div>
</body></html>
<?php
