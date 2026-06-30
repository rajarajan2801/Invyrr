<?php
/**
 * Invyrr API — Expense Entities (Businesses)
 * Tracks separate businesses (e.g. SVT, RRA) for expense bucketing.
 * Completely independent of the Locations table (warehouses/stores).
 *
 * GET    /api/expense_entities.php          → list all entities
 * POST   /api/expense_entities.php          → create { name }
 * PUT    /api/expense_entities.php          → rename { id, name }
 * DELETE /api/expense_entities.php?id=X     → delete
 */
require __DIR__ . '/../includes/db.php';
startSession(); requireAuth();

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── Ensure table exists ───────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS expense_entities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($method === 'GET') {
    $rows = $pdo->query("SELECT id, name FROM expense_entities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    jsonOk($rows);
}

if ($method === 'POST') {
    $b = getBody();
    $name = trim($b['name'] ?? '');
    if (!$name) jsonError('Business name is required');
    try {
        $pdo->prepare("INSERT INTO expense_entities (name) VALUES (?)")->execute([$name]);
        $id = (int)$pdo->lastInsertId();
        jsonOk(['id' => $id, 'name' => $name], 'Business added');
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            jsonError('A business with that name already exists');
        }
        jsonError('Could not add business: ' . $e->getMessage());
    }
}

if ($method === 'PUT') {
    $b = getBody();
    $id   = (int)($b['id'] ?? 0);
    $name = trim($b['name'] ?? '');
    if (!$id || !$name) jsonError('ID and name are required');
    try {
        $pdo->prepare("UPDATE expense_entities SET name=? WHERE id=?")->execute([$name, $id]);
        jsonOk([], 'Business renamed');
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            jsonError('A business with that name already exists');
        }
        jsonError('Could not rename business: ' . $e->getMessage());
    }
}

if ($method === 'DELETE') {
    if (!canDelete()) jsonError('Only admins can delete', 403);
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('ID required');
    // Expenses referencing this entity have entity_id set to NULL via FK ON DELETE SET NULL
    $pdo->prepare("DELETE FROM expense_entities WHERE id=?")->execute([$id]);
    jsonOk([], 'Business deleted');
}

jsonError('Unknown action', 400);
