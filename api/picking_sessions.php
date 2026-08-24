<?php
/**
 * api/picking_sessions.php — Cross-device sync for Order Picking
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../includes/db.php';
startSession();
requireAuth();

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Auto-create table
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
        created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
        updated_at    DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_date (session_date),
        INDEX idx_code (verify_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    try { $pdo->exec("ALTER TABLE picking_sessions ADD COLUMN status VARCHAR(20) DEFAULT 'pending'"); } catch(Exception $e) {}
    try { $pdo->exec("ALTER TABLE picking_sessions ADD COLUMN address TEXT"); } catch(Exception $e) {}
    // Transport / dispatch details, captured when an order is marked Dispatched
    try { $pdo->exec("ALTER TABLE picking_sessions ADD COLUMN ship_date DATE"); } catch(Exception $e) {}
    try { $pdo->exec("ALTER TABLE picking_sessions ADD COLUMN transport_name VARCHAR(128)"); } catch(Exception $e) {}
    try { $pdo->exec("ALTER TABLE picking_sessions ADD COLUMN box_count INT"); } catch(Exception $e) {}
    // Logged once, the moment an order first leaves 'picking' for
    // 'verification' — kept distinct from verified_at (verification
    // completion), see setPickStatus() in index.php.
    try { $pdo->exec("ALTER TABLE picking_sessions ADD COLUMN picking_completed_at DATETIME"); } catch(Exception $e) {}
    // Which physical location this order is being picked from — orders can
    // now be picked from any location, defaulting client-side to 'RR
    // Crackers'. No FK constraint, matching this table's existing style.
    try { $pdo->exec("ALTER TABLE picking_sessions ADD COLUMN location_id INT"); } catch(Exception $e) {}
    // Packing charge parsed off the source estimate (e.g. a PDF's
    // 'Packing Charges' line) -- kept separate from the item amounts in
    // `data` so the Order Total shown across the app can include it
    // without it being mistaken for a line item.
    try { $pdo->exec("ALTER TABLE picking_sessions ADD COLUMN packing_charges DECIMAL(10,2) DEFAULT 0"); } catch(Exception $e) {}
    // The estimate's own printed 'Overall Total' -- already bakes in
    // packing charges AND any other adjustment the source PDF applies
    // (extra discounts, waivers, etc.), so it's the authoritative figure
    // for Order Total across the app rather than packing_charges alone.
    try { $pdo->exec("ALTER TABLE picking_sessions ADD COLUMN overall_total DECIMAL(10,2) DEFAULT 0"); } catch(Exception $e) {}
} catch (Exception $e) {}

// ── GET ──────────────────────────────────────────────────
if ($method === 'GET') {

    // Debug: show all distinct dates in the table
    if (isset($_GET['debug'])) {
        $rows = $pdo->query("SELECT session_date, COUNT(*) as cnt FROM picking_sessions GROUP BY session_date ORDER BY session_date DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        jsonOk(['server_date' => date('Y-m-d'), 'server_datetime' => date('Y-m-d H:i:s'), 'dates_in_db' => $rows]);
    }

    if (!empty($_GET['code'])) {
        $s = $pdo->prepare("SELECT * FROM picking_sessions WHERE verify_code = ? LIMIT 1");
        $s->execute([trim($_GET['code'])]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if (!$row) { jsonList([]); exit; }
        $row['data'] = json_decode($row['data'] ?? '[]', true);
        jsonOk($row);
    }

    // If no date given, return every session on record, full stop — the
    // dashboard's default view is meant to show the full history. This used
    // to also require session_date <= CURDATE(), which silently hid any
    // order whose session_date was even one day ahead of the SERVER's
    // clock — see the POST handler below for why that could happen on a
    // brand-new order for hours at a time even though nothing was wrong
    // with it. A specific ?date= still narrows to a single day.
    if (empty($_GET['date'])) {
        $s = $pdo->prepare(
            "SELECT ps.id, ps.order_no, ps.customer, ps.phone, ps.address, ps.picker,
                    ps.verify_code, ps.verified, ps.verified_by, ps.verified_at,
                    ps.status, ps.session_date, ps.updated_at, ps.data,
                    ps.ship_date, ps.transport_name, ps.box_count, ps.picking_completed_at,
                    ps.packing_charges, ps.overall_total, ps.location_id, l.name AS location_name
             FROM picking_sessions ps
             LEFT JOIN locations l ON l.id = ps.location_id
             ORDER BY ps.session_date DESC, ps.created_at DESC"
        );
        $s->execute();
    } else {
        $date = preg_replace('/[^0-9\-]/', '', $_GET['date']);
        $s = $pdo->prepare(
            "SELECT ps.id, ps.order_no, ps.customer, ps.phone, ps.address, ps.picker,
                    ps.verify_code, ps.verified, ps.verified_by, ps.verified_at,
                    ps.status, ps.session_date, ps.updated_at, ps.data,
                    ps.ship_date, ps.transport_name, ps.box_count, ps.picking_completed_at,
                    ps.packing_charges, ps.overall_total, ps.location_id, l.name AS location_name
             FROM picking_sessions ps
             LEFT JOIN locations l ON l.id = ps.location_id
             WHERE ps.session_date = ?
             ORDER BY ps.created_at ASC"
        );
        $s->execute([$date]);
    }

    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['data'] = json_decode($row['data'] ?? '[]', true);
    }
    jsonList($rows);
}

// ── POST ─────────────────────────────────────────────────
if ($method === 'POST') {
    $b = json_decode(file_get_contents('php://input'), true);
    if (empty($b['id'])) jsonErr('Missing id');
    if (!empty($b['verified']) && !in_array(currentUser()['role'] ?? '', ['admin','manager','partner'])) {
        jsonError('Only admin, manager, or partner can verify orders', 403);
    }

    // Default the picking location server-side too (not just client-side),
    // but ONLY for a brand-new session — if this id already exists and the
    // client's save call simply didn't include location_id (e.g. a routine
    // status update), leave $locId null so COALESCE(VALUES(...), location_id)
    // below preserves whatever location the order already has. Applying the
    // default unconditionally here would silently reset an existing order's
    // location back to 'RR Crackers' on every ordinary save — the same
    // overwrite-not-merge bug class already fixed for verify_code/
    // verified_at/picking_completed_at.
    $locId = isset($b['location_id']) && $b['location_id'] !== '' ? (int)$b['location_id'] : null;
    if ($locId === null) {
        $exists = $pdo->prepare("SELECT 1 FROM picking_sessions WHERE id = ?");
        $exists->execute([$b['id']]);
        if (!$exists->fetch()) {
            $row = $pdo->query("SELECT id FROM locations WHERE name = 'RR Crackers' LIMIT 1")->fetch();
            if (!$row) $row = $pdo->query("SELECT id FROM locations WHERE is_default=1 LIMIT 1")->fetch();
            if (!$row) $row = $pdo->query("SELECT id FROM locations ORDER BY id LIMIT 1")->fetch();
            $locId = $row ? (int)$row['id'] : null;
        }
    }

    // session_date below is always taken from the server's own clock,
    // never the client's -- it only matters on this INSERT branch since
    // session_date is deliberately absent from the UPDATE SET clause (it
    // never changes after a row is created). This app has no
    // date_default_timezone_set() anywhere, so a client-submitted local
    // date (this shop runs on IST) could land up to a day ahead of the
    // server's own date('Y-m-d') for hours around local midnight,
    // silently hiding brand-new orders from the GET above until the
    // server's date caught up.
    $pdo->prepare(
        "INSERT INTO picking_sessions
            (id, order_no, customer, phone, address, picker,
             verify_code, verified, verified_by, verified_at,
             status, session_date, data, ship_date, transport_name, box_count,
             picking_completed_at, packing_charges, overall_total, location_id)
         VALUES (?,?,?,?,?, ?,?,?,?,?, ?,?,?,?,?,?, ?,?,?,?)
         ON DUPLICATE KEY UPDATE
             order_no             = VALUES(order_no),
             customer             = VALUES(customer),
             phone                = VALUES(phone),
             address              = VALUES(address),
             picker               = VALUES(picker),
             verify_code          = COALESCE(VALUES(verify_code), verify_code),
             verified             = VALUES(verified),
             verified_by          = VALUES(verified_by),
             verified_at          = COALESCE(VALUES(verified_at), verified_at),
             status               = VALUES(status),
             data                 = VALUES(data),
             ship_date            = VALUES(ship_date),
             transport_name       = VALUES(transport_name),
             box_count            = VALUES(box_count),
             picking_completed_at = COALESCE(VALUES(picking_completed_at), picking_completed_at),
             packing_charges      = VALUES(packing_charges),
             overall_total        = VALUES(overall_total),
             location_id          = COALESCE(VALUES(location_id), location_id),
             updated_at           = CURRENT_TIMESTAMP"
    )->execute([
        $b['id'],
        $b['orderNo']    ?? '',
        $b['customer']   ?? '',
        $b['phone']      ?? '',
        $b['address']    ?? '',
        $b['picker']     ?? '',
        $b['verifyCode'] ?? null,
        empty($b['verified']) ? 0 : 1,
        $b['verifiedBy']  ?? null,
        !empty($b['verifiedAt'])
            ? date('Y-m-d H:i:s', intdiv((int)$b['verifiedAt'], 1000))
            : null,
        $b['status'] ?? 'pending',
        date('Y-m-d'), // always the server's clock -- never trust the client's local date, see comment above
        json_encode($b['items'] ?? []),
        !empty($b['shipDate']) ? $b['shipDate'] : null,
        !empty($b['transportName']) ? $b['transportName'] : null,
        (isset($b['boxCount']) && $b['boxCount'] !== '') ? (int)$b['boxCount'] : null,
        !empty($b['pickingCompletedAt'])
            ? date('Y-m-d H:i:s', intdiv((int)$b['pickingCompletedAt'], 1000))
            : null,
        (float)($b['packingCharges'] ?? 0),
        (float)($b['overallTotal'] ?? 0),
        $locId,
    ]);
    jsonOk(null, 'Saved');
}

// ── DELETE ────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    $id = $_GET['id'] ?? '';
    if (!$id) jsonErr('Missing id');
    // Mirrors the client-side check in deleteEstimate() -- a dispatched
    // order is done, and its stock has already been handed to the
    // customer, so deleting it here (and the client reversing stock as
    // if it hadn't been) would be wrong. Real server-side enforcement,
    // not just a hidden button.
    $row = $pdo->prepare("SELECT status FROM picking_sessions WHERE id = ?");
    $row->execute([$id]);
    $status = $row->fetchColumn();
    if ($status === 'dispatched') {
        jsonError('This order has already been dispatched — it can no longer be deleted', 403);
    }
    $pdo->prepare("DELETE FROM picking_sessions WHERE id = ?")
        ->execute([$id]);
    jsonOk(null, 'Deleted');
}

jsonErr('Method not allowed');
