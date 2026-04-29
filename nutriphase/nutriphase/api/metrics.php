<?php
require_once '../config/db.php';
require_once '../config/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$pSSN   = (int)$user['SSN'];
$db     = getDB();

// GET /api/metrics.php
if ($method === 'GET') {
    $result  = $db->query("SELECT * FROM body_metrics WHERE P_SSN = $pSSN ORDER BY date DESC");
    $metrics = [];
    while ($row = $result->fetch_assoc()) $metrics[] = $row;
    respond($metrics);
}

// POST /api/metrics.php
if ($method === 'POST') {
    $data   = getInput();
    $date   = $db->real_escape_string($data['date'] ?? date('Y-m-d'));
    $height = (float)($data['height'] ?? 0);
    $weight = (float)($data['weight'] ?? 0);

    $db->query("INSERT INTO body_metrics (P_SSN, date, height, weight)
                VALUES ($pSSN, '$date', $height, $weight)");
    if ($db->errno) respondError('Failed to save metrics.');

    respond(['message' => 'Metrics saved.', 'metric_id' => $db->insert_id], 201);
}

respondError('Method not allowed.', 405);
