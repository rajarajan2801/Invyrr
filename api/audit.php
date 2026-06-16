<?php
require __DIR__.'/../includes/db.php';
header('Access-Control-Allow-Origin: *');
startSession();
requireRole('admin');
$pdo=getDB();
$limit=min((int)($_GET['limit']??100),500);
$rows=$pdo->query("SELECT * FROM audit_log ORDER BY created_at DESC LIMIT $limit")->fetchAll();
jsonList($rows);
