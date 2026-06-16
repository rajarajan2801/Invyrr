<?php
/**
 * Audit Log API
 * GET /api/audit_log.php              → list entries (admin only)
 *   ?from=YYYY-MM-DD  &to=YYYY-MM-DD  &q=search  &limit=200  &action=  &entity=
 */
require __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
startSession();
requireRole('admin');
$pdo = getDB();

$where  = [];
$params = [];

if (!empty($_GET['from'])) {
    $where[]  = 'DATE(a.created_at) >= ?';
    $params[] = $_GET['from'];
}
if (!empty($_GET['to'])) {
    $where[]  = 'DATE(a.created_at) <= ?';
    $params[] = $_GET['to'];
}
if (!empty($_GET['q'])) {
    $where[]  = '(a.user_name LIKE ? OR a.action LIKE ? OR a.entity LIKE ? OR a.detail LIKE ?)';
    $q = '%' . $_GET['q'] . '%';
    array_push($params, $q, $q, $q, $q);
}
if (!empty($_GET['action'])) {
    $where[]  = 'a.action LIKE ?';
    $params[] = $_GET['action'] . '%';  // prefix match: "create" matches "create_product" etc.
}
if (!empty($_GET['entity'])) {
    $where[]  = 'a.entity = ?';
    $params[] = $_GET['entity'];
}

$sql    = 'SELECT a.* FROM audit_log a';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql   .= ' ORDER BY a.created_at DESC';
$limit  = min((int)($_GET['limit'] ?? 200), 5000);
$sql   .= " LIMIT $limit";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Also fetch total count (without limit) so UI can show "showing X of Y"
$countSql = 'SELECT COUNT(*) FROM audit_log a';
if ($where) $countSql .= ' WHERE ' . implode(' AND ', $where);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

jsonList($rows, $total);
