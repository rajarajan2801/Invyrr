<?php
/**
 * Invyrr API — Public Catalog (no auth — this is the only api/*.php file
 * meant to be reachable without a session, since it powers the public
 * storefront at /shop.php)
 *
 * Deliberately read-only and deliberately narrow about what it exposes:
 * only products with publish_web=1 and stock>0 at the shop's fulfillment
 * location, and only customer-safe fields (no cost/vendor/margin/
 * wholesale_price — those stay behind the authenticated Products API).
 *
 * GET /api/public_catalog.php               → products (all published, in-stock)
 * GET /api/public_catalog.php?q=sparkler     → products matching a search term
 * GET /api/public_catalog.php?category=Rockets
 * GET /api/public_catalog.php?meta=1         → {categories:[...], locationName}
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

require __DIR__ . '/../includes/db.php';
// No startSession()/requireAuth() here on purpose — this endpoint is
// public. Every query below is a read against a fixed, safe column list.

$pdo = getDB();

// Same "RR Crackers, else default, else first" resolution
// getPickLocationChoiceAsync() uses client-side for the internal
// Fulfillment flow — the public shop always sells against this one
// location's stock, matching how a freshly-added order already behaves
// before anyone picks a different location for it.
function publicShopLocationId(PDO $pdo): ?int {
    $row = $pdo->query("SELECT id, name FROM locations WHERE LOWER(TRIM(name))='rr crackers' LIMIT 1")->fetch();
    if (!$row) $row = $pdo->query("SELECT id, name FROM locations WHERE is_default=1 LIMIT 1")->fetch();
    if (!$row) $row = $pdo->query("SELECT id, name FROM locations ORDER BY id LIMIT 1")->fetch();
    return $row ? (int)$row['id'] : null;
}

$locId = publicShopLocationId($pdo);

if (isset($_GET['meta'])) {
    // Only categories that currently have at least one published,
    // in-stock product — an empty category tile is just dead weight on
    // a customer-facing page.
    if ($locId) {
        $cats = $pdo->prepare(
            "SELECT c.name, c.color, COUNT(p.id) AS product_count
             FROM categories c
             JOIN products p ON p.category = c.name
             JOIN product_locations pl ON pl.product_id = p.id AND pl.location_id = ? AND pl.stock > 0
             WHERE COALESCE(p.publish_web,0)=1
             GROUP BY c.id
             HAVING product_count > 0
             ORDER BY c.name"
        );
        $cats->execute([$locId]);
    } else {
        $cats = $pdo->query(
            "SELECT c.name, c.color, COUNT(p.id) AS product_count
             FROM categories c
             JOIN products p ON p.category = c.name
             WHERE COALESCE(p.publish_web,0)=1 AND p.stock > 0
             GROUP BY c.id
             HAVING product_count > 0
             ORDER BY c.name"
        );
    }
    $locRow = $locId ? $pdo->query("SELECT name FROM locations WHERE id=$locId")->fetch() : null;
    jsonOk([
        'categories'   => $cats->fetchAll(),
        'locationName' => $locRow ? $locRow['name'] : null,
    ]);
}

// ── Product listing ──────────────────────────────────────────────────
$where = ['COALESCE(p.publish_web,0)=1'];
$params = [];

if (!empty($_GET['q'])) {
    $like = '%'.$_GET['q'].'%';
    $where[] = '(p.name LIKE ? OR p.brand LIKE ? OR p.category LIKE ?)';
    $params = array_merge($params, [$like, $like, $like]);
}
if (!empty($_GET['category'])) {
    $where[] = 'p.category = ?';
    $params[] = $_GET['category'];
}

if ($locId) {
    $sql = "SELECT p.id, p.name, p.sku, p.brand, p.category, p.unit, p.image,
                   p.sell, p.list_price, pl.stock AS stock
            FROM products p
            JOIN product_locations pl ON pl.product_id = p.id AND pl.location_id = ?
            WHERE ".implode(' AND ', $where)." AND pl.stock > 0
            ORDER BY p.category, p.name";
    $s = $pdo->prepare($sql);
    $s->execute(array_merge([$locId], $params));
} else {
    // No locations configured at all (fresh install) — fall back to the
    // product's own base stock rather than showing an empty shop.
    $sql = "SELECT p.id, p.name, p.sku, p.brand, p.category, p.unit, p.image,
                   p.sell, p.list_price, p.stock AS stock
            FROM products p
            WHERE ".implode(' AND ', $where)." AND p.stock > 0
            ORDER BY p.category, p.name";
    $s = $pdo->prepare($sql);
    $s->execute($params);
}

$rows = $s->fetchAll();
foreach ($rows as &$r) {
    $r['stock']      = (int)$r['stock'];
    $r['sell']        = (float)$r['sell'];
    $r['list_price']  = $r['list_price'] !== null ? (float)$r['list_price'] : null;
    $r['image_url']   = $r['image'] ? 'assets/images/products/'.$r['image'] : null;
    unset($r['image']);
}
jsonList($rows);
