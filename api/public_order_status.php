<?php
/**
 * Invyrr API — Public Order Status (no auth — same deliberate exception
 * as public_catalog.php/public_checkout.php; this is the third)
 *
 * GET /api/public_order_status.php?phone=9876543210
 * GET /api/public_order_status.php?phone=9876543210&order_no=WEB251014-A1B2
 *
 * Phone is required, order_no narrows it to one order when the customer
 * has it handy. Deliberately narrow about what it exposes: just enough to
 * answer "where is my order" (a friendly stage label, the date, and —
 * only once dispatched — the transport/LR details), never items, prices,
 * address, or anything about a payment issue. Matches are scoped to the
 * last 10 digits of the phone on file, so a customer who dialed in with a
 * leading 0 or +91 still finds their order without that prefix mattering;
 * matching the full table by a bare 10-digit number is already how this
 * business expects phone numbers to work (see public_checkout.php).
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

require __DIR__ . '/../includes/db.php';
// No startSession()/requireAuth() here on purpose -- this endpoint is
// public, same as public_catalog.php/public_checkout.php. Every query
// below is a read against a fixed, customer-safe column list.

$pdo = getDB();

$phone = preg_replace('/[^0-9]/', '', (string)($_GET['phone'] ?? ''));
if (strlen($phone) < 10) jsonError('Enter a valid 10-digit phone number');
$last10 = substr($phone, -10);

// Same control-char/whitespace tolerance syncPickingStatusForOrder() (in
// includes/db.php) already applies when matching order_no elsewhere --
// an order number pasted from a WhatsApp message or a PDF-derived
// estimate occasionally carries stray characters a plain '=' would miss.
$orderNo = trim(preg_replace('/[\r\n\t]/', '', (string)($_GET['order_no'] ?? '')));

$where  = ["RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'+',''),'.',''), 10) = ?"];
$params = [$last10];
if ($orderNo !== '') {
    $where[]  = "TRIM(REPLACE(REPLACE(REPLACE(order_no,'\r',''),'\n',''),'\t','')) = ?";
    $params[] = $orderNo;
}

// Deliberately no CREATE TABLE here (unlike picking_sessions.php/
// public_checkout.php) -- this endpoint only ever reads, and if no order
// has ever been placed the table may not exist yet. Caught rather than
// left to bubble up as a raw SQL error in a public JSON response -- "the
// table isn't there yet" and "no matching order" look identical to a
// customer either way.
try {
    $stmt = $pdo->prepare(
        "SELECT order_no, status, session_date, created_at, on_hold,
                ship_date, transport_name, lr_number, box_count
         FROM picking_sessions
         WHERE " . implode(' AND ', $where) . "
         ORDER BY created_at DESC
         LIMIT 20"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    $rows = [];
}

// Collapses the app's internal picking_sessions.status values down to the
// handful of stages a customer actually cares about -- deliberately
// coarser than the admin-side Fulfillment dashboard (which also shows
// Verification/Flagged), and never surfaces WHY an order is flagged or
// held, only that it's still being worked on. 'pending'/'paid'/'flagged'
// are all pre-picking, so they share one bucket; 'verification' shares
// Picking's bucket (the customer's items are already pulled, just being
// double-checked). 'packed' USED to share Packing's bucket here, which
// read as a discrepancy to a customer watching an order that had already
// been packed still say "Being Packed" -- it now gets its own stage
// between Packing and Dispatched.
function publicOrderStage(string $status): array {
    switch ($status) {
        case 'picking':
        case 'verification':
            return ['picking', 'Being Picked', 2];
        case 'packing':
            return ['packing', 'Being Packed', 3];
        case 'packed':
            return ['packed', 'Packed — Ready to Ship', 4];
        case 'dispatched':
            return ['dispatched', 'Dispatched', 5];
        default: // pending, paid, flagged, or anything future/unrecognized
            return ['received', 'Order Received', 1];
    }
}

$orders = [];
foreach ($rows as $r) {
    [$stage, $label, $step] = publicOrderStage((string)($r['status'] ?? ''));
    $order = [
        'order_no'    => $r['order_no'],
        'date'        => $r['session_date'] ?: substr((string)$r['created_at'], 0, 10),
        'stage'       => $stage,
        'stage_label' => $label,
        'step'        => $step,
        'on_hold'     => (bool)($r['on_hold'] ?? false),
        'dispatch'    => null,
    ];
    if ($stage === 'dispatched') {
        $order['dispatch'] = [
            'ship_date'      => $r['ship_date'],
            'transport_name' => $r['transport_name'],
            'lr_number'      => $r['lr_number'],
            'box_count'      => $r['box_count'],
        ];
    }
    $orders[] = $order;
}

jsonOk(['orders' => $orders]);
