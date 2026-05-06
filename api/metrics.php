<?php
require_once '../config/helpers.php';
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();

$pSSN   = (int)($user['SSN'] ?? $user['P_SSN'] ?? $user['N_SSN'] ?? 0);
if (!$pSSN) respondError('Could not determine user SSN.', 400);
$db     = getDB();


if ($method === 'GET') { 
    if ($user['role'] === 'nutritionist' && isset($_GET['p_ssn'])) {
    $pSSN = (int)$_GET['p_ssn'];
}
    $result  = $db->query("SELECT * FROM body_metrics WHERE P_SSN = $pSSN ORDER BY date DESC");
    $metrics = [];
    while ($row = $result->fetch_assoc()) $metrics[] = $row;
    respond($metrics);
}


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
