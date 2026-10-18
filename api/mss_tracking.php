<?php
/**
 * Invyrr API — MSS (Mettur Transports) live tracking proxy
 * (no auth — same deliberate public exception as public_order_status.php;
 * called from track-order.php so a customer sees real MSS tracking status
 * inline instead of having to go open metturtransports.com themselves)
 *
 * GET /api/mss_tracking.php?gc=8113608
 *
 * Mettur Transports' own tracking page (metturtransports.com/tracking/)
 * has no public JSON API -- it's a plain HTML form that POSTs
 * tracking_number + tracking_mobile back to itself and server-renders the
 * result. This endpoint submits that same form on the customer's behalf
 * and scrapes the handful of fields worth showing (status, route, booked
 * date/time, delivery branch) back out as JSON. That makes it inherently
 * fragile -- it depends on MSS's current markup, not a documented
 * contract -- so every field below is extracted independently and a miss
 * just comes back null rather than erroring the whole response (see
 * mssExtract()).
 *
 * Every RR Crackers / SVT / RRA booking with MSS is made against ONE
 * fixed account mobile number, never the customer's own phone (confirmed
 * with Raj against the live MSS tracking page) -- so this endpoint takes
 * only a GC/LR number, never a caller-supplied mobile, which keeps it
 * from being turned into a general "look up anyone's MSS booking" tool.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

const MSS_TRACKING_URL   = 'https://www.metturtransports.com/tracking/';
const MSS_ACCOUNT_MOBILE = '8438001669'; // Fixed booking-account number, not the customer's.
const MSS_CACHE_TTL      = 90; // seconds -- just to spare MSS's site a hit on every page refresh.

$gc = preg_replace('/[^0-9]/', '', (string)($_GET['gc'] ?? ''));
if (strlen($gc) < 3) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid GC number']);
    exit;
}

// Lightweight file cache -- not correctness-critical (a cold/expired
// cache just means one extra live fetch), purely to avoid hammering a
// third party's site if a customer refreshes the tracking page a few
// times in a row.
$cacheFile = sys_get_temp_dir() . '/invyrr_mss_track_' . $gc . '.json';
if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < MSS_CACHE_TTL) {
    $cached = file_get_contents($cacheFile);
    if ($cached !== false) { echo $cached; exit; }
}

function mssFetch(string $gc, string $mobile): ?string {
    $ch = curl_init(MSS_TRACKING_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['tracking_number' => $gc, 'tracking_mobile' => $mobile]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_USERAGENT      => 'Invyrr/1.0 (+https://invyrr.up.railway.app) order-status-lookup',
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($body !== false && $code === 200) ? $body : null;
}

function mssRespond(array $payload, ?string $cacheFile): void {
    $json = json_encode($payload);
    if ($cacheFile !== null) { @file_put_contents($cacheFile, $json); }
    echo $json;
    exit;
}

$html = mssFetch($gc, MSS_ACCOUNT_MOBILE);
if ($html === null) {
    // MSS's site is unreachable/erroring right now -- not "no such
    // booking", a network problem. Don't cache this one; it's worth
    // retrying on the very next request rather than sitting on a
    // transient failure for MSS_CACHE_TTL seconds.
    mssRespond(['success' => true, 'available' => false], null);
}

// A CAPTCHA challenge in the response means MSS's own anti-abuse system
// has flagged this traffic as automated -- which, run at any volume, it
// is. This endpoint must never try to solve or work around that; it just
// reports itself unavailable so the caller falls back to linking the
// customer to MSS's own tracking page instead.
if (stripos($html, 'tracking-captcha') !== false || stripos($html, 'captcha.php') !== false) {
    mssRespond(['success' => true, 'available' => false], null);
}

if (stripos($html, 'empty-result') !== false) {
    mssRespond(['success' => true, 'available' => true, 'found' => false], $cacheFile);
}

function mssExtract(string $pattern, string $html): ?string {
    return preg_match($pattern, $html, $m) ? trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')) : null;
}

$status = mssExtract('/<span class="badge[^"]*">([^<]*)<\/span>/', $html);
$gcLabel = mssExtract('/<h2>(GC\s*[0-9]+)<\/h2>/', $html);

preg_match_all('/<li class="([^"]*)"[^>]*>.*?<b>([^<]*)<\/b><\/li>/s', $html, $stepMatches, PREG_SET_ORDER);
$steps = [];
foreach ($stepMatches as $sm) {
    $steps[] = ['label' => trim(html_entity_decode($sm[2], ENT_QUOTES, 'UTF-8')), 'complete' => strpos($sm[1], 'complete') !== false];
}

$fromCity    = mssExtract('/<small>FROM<\/small><strong>([^<]*)<\/strong>/', $html);
$toCity      = mssExtract('/<small>TO<\/small><strong>([^<]*)<\/strong>/', $html);
$bookedAt    = mssExtract('/<dt>Booked<\/dt><dd>([^<]*)<\/dd>/', $html);
$payment     = mssExtract('/<dt>Payment<\/dt><dd>([^<]*)<\/dd>/', $html);
$branchName  = mssExtract('/<strong class="branch-name">([^<]*)<\/strong>/', $html);
$branchAddr  = mssExtract('/<strong class="branch-name">[^<]*<\/strong><p>([^<]*)<\/p>/', $html);
$branchPhone = mssExtract('/<a href="tel:\+91([0-9]+)"/', $html);

// found=false here (rather than the empty-result short-circuit above)
// would mean MSS's markup changed enough that neither a result card nor
// the not-found block matched -- treat that the same as "no result" so
// the frontend shows the same graceful fallback instead of a half-empty
// card.
if ($status === null && $fromCity === null && $branchName === null) {
    mssRespond(['success' => true, 'available' => true, 'found' => false], $cacheFile);
}

mssRespond([
    'success'               => true,
    'available'             => true,
    'found'                 => true,
    'gc'                    => $gcLabel,
    'status'                => $status,
    'steps'                 => $steps,
    'from'                  => $fromCity,
    'to'                    => $toCity,
    'bookedAt'              => $bookedAt,
    'payment'               => $payment,
    'deliveryBranch'        => $branchName,
    'deliveryBranchAddress' => $branchAddr,
    'deliveryBranchPhone'   => $branchPhone,
], $cacheFile);
