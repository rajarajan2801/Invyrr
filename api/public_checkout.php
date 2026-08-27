<?php
/**
 * Invyrr API — Public Checkout (no auth — see public_catalog.php's header
 * comment for why this is the exception to every other api/*.php file)
 *
 * POST /api/public_checkout.php
 *   body: { customer, phone, address, items:[{product_id, qty}], website (honeypot, must stay empty) }
 *
 * Never trusts client-submitted prices or names — every item is looked
 * up fresh from `products` by id, and only published (publish_web=1)
 * products can be ordered. On success, inserts a normal picking_sessions
 * row with status='pending', so the order shows up in Fulfillment's
 * dashboard exactly like any other order (Payment Due), ready for the
 * team to contact the customer and take it from there — nothing else in
 * Fulfillment (payments, WhatsApp updates, picking, dispatch...) needed
 * to change to support this; it was all already built to work off this
 * one table.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

require __DIR__ . '/../includes/db.php';
$pdo = getDB();

// Same guard api/picking_sessions.php runs -- kept here too so a web
// order placed before anyone has ever opened the authenticated
// Fulfillment page (brand-new install) still has a table to land in.
// Matches that file's column list; the two ALTER-guarded columns this
// endpoint actually writes (packing_charges, overall_total, location_id)
// are covered by the CREATE TABLE itself here since this always runs
// after picking_sessions.php was written, so no separate ALTER guards
// are needed for those specifically -- but included anyway in case this
// endpoint is ever deployed to a database older than that.
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS picking_sessions (
        id            VARCHAR(64)  PRIMARY KEY,
        order_no      VARCHAR(64),
        customer      VARCHAR(255),
        phone         VARCHAR(20),
        address       TEXT,
        picker        VARCHAR(128),
        verify_code   VARCHAR(20),
        verified      TINYINT(1)   DEFAULT 0,
        verified_by   VARCHAR(128),
        verified_at   DATETIME,
        status        VARCHAR(20)  DEFAULT 'pending',
        session_date  DATE         NOT NULL,
        data          LONGTEXT     NOT NULL,
        ship_date       DATE,
        transport_name  VARCHAR(128),
        box_count       INT,
        picking_completed_at DATETIME,
        packing_charges DECIMAL(10,2) DEFAULT 0,
        overall_total DECIMAL(10,2) DEFAULT 0,
        location_id   INT,
        created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
        updated_at    DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_date (session_date),
        INDEX idx_code (verify_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    try { $pdo->exec("ALTER TABLE picking_sessions ADD COLUMN overall_total DECIMAL(10,2) DEFAULT 0"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE picking_sessions ADD COLUMN location_id INT"); } catch (Exception $e) {}
} catch (Exception $e) {}

$b = json_decode(file_get_contents('php://input'), true);
if (!is_array($b)) jsonError('Invalid request body');

// Honeypot — a genuine visitor never sees or fills this field (hidden
// off-screen in the checkout form); a bot filling every input on the
// form will. Pretend success without writing anything, so the bot
// doesn't learn to look for a different signal.
if (!empty($b['website'])) {
    jsonOk(['order_no' => null], 'Order received');
}

$customer = trim((string)($b['customer'] ?? ''));
$phone    = preg_replace('/[^0-9]/', '', (string)($b['phone'] ?? ''));
$address  = trim((string)($b['address'] ?? ''));
$items    = is_array($b['items'] ?? null) ? $b['items'] : [];

if ($customer === '') jsonError('Name is required');
if (strlen($phone) < 10 || strlen($phone) > 13) jsonError('Enter a valid phone number');
if ($address === '') jsonError('Delivery address is required');
if (!count($items)) jsonError('Your cart is empty');
if (count($items) > 100) jsonError('Too many items in one order — please contact us directly for bulk orders');

function publicShopLocationId(PDO $pdo): ?int {
    $row = $pdo->query("SELECT id, name FROM locations WHERE LOWER(TRIM(name))='rr crackers' LIMIT 1")->fetch();
    if (!$row) $row = $pdo->query("SELECT id, name FROM locations WHERE is_default=1 LIMIT 1")->fetch();
    if (!$row) $row = $pdo->query("SELECT id, name FROM locations ORDER BY id LIMIT 1")->fetch();
    return $row ? (int)$row['id'] : null;
}
$locId = publicShopLocationId($pdo);

// Re-price and re-validate every line against the DB — the cart the
// browser sent is just a list of product ids/quantities, never trusted
// for name or price.
$stmt = $pdo->prepare("SELECT id, name, sku, brand, sell FROM products WHERE id=? AND COALESCE(publish_web,0)=1");
$built = [];
$total = 0.0;
foreach ($items as $it) {
    $pid = (int)($it['product_id'] ?? 0);
    $qty = (int)($it['qty'] ?? 0);
    if ($pid <= 0 || $qty <= 0) continue;
    $qty = min($qty, 999);
    $stmt->execute([$pid]);
    $p = $stmt->fetch();
    if (!$p) continue; // no longer published/doesn't exist -- silently drop rather than fail the whole order
    $rate   = (float)$p['sell'];
    $amount = round($rate * $qty, 2);
    $built[] = [
        'code'         => $p['sku'] ?: '',
        'name'         => $p['name'],
        'matched_name' => $p['name'],
        'brand'        => $p['brand'] ?: '',
        'qty'          => $qty,
        'picked'       => 0,
        'rate'         => $rate,
        'amount'       => $amount,
        'unavailable'  => false,
        'substitutes'  => [],
        'matched_id'   => $p['id'],
        'isGift'       => false,
    ];
    $total += $amount;
}
if (!count($built)) jsonError('None of the items in your cart are available anymore — please refresh and try again');

// Order number: human-readable, date-stamped, short random suffix.
// Retried a handful of times against a genuine collision rather than
// relying on a DB-level unique constraint -- order_no isn't this
// table's key (id is), matching how manually-added estimates already
// work here.
$orderNo = null;
for ($i = 0; $i < 5; $i++) {
    $candidate = 'WEB' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
    $exists = $pdo->prepare("SELECT 1 FROM picking_sessions WHERE order_no=?");
    $exists->execute([$candidate]);
    if (!$exists->fetch()) { $orderNo = $candidate; break; }
}
if (!$orderNo) jsonError('Could not generate an order number — please try again', 500);

$id = 'web_' . time() . '_' . bin2hex(random_bytes(4));

$pdo->prepare(
    "INSERT INTO picking_sessions
        (id, order_no, customer, phone, address, picker, status, session_date, data,
         packing_charges, overall_total, location_id)
     VALUES (?,?,?,?,?, '', 'pending', ?, ?, 0, 0, ?)"
)->execute([
    $id, $orderNo, $customer, $phone, $address,
    date('Y-m-d'), json_encode($built), $locId,
]);

// entity_id is an INT column and this table's id is a string ("web_..."),
// so the id goes in the detail text instead rather than passing a
// non-numeric string into auditLog()'s int-typed parameter.
auditLog($pdo, 'public_web_order', 'picking_session', 0, "Web order $orderNo ($id) from $customer (₹".number_format($total,2).")");

jsonOk(['order_no' => $orderNo, 'total' => round($total,2), 'item_count' => count($built)], 'Order received');
