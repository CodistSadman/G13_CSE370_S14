<?php
require_once '../config/db.php';
require_once '../config/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$pSSN   = (int)$user['SSN'];
$db     = getDB();

// GET /api/predictions.php - get predictions + suggestions for patient
if ($method === 'GET') {
    $result = $db->query("
        SELECT hp.*, s.suggestion_text
        FROM health_prediction hp
        LEFT JOIN suggestion s ON hp.predicted_issue = s.predicted_issue
        WHERE hp.P_SSN = $pSSN
    ");
    $predictions = [];
    while ($row = $result->fetch_assoc()) $predictions[] = $row;
    respond($predictions);
}

// POST /api/predictions.php - nutritionist creates a prediction
if ($method === 'POST') {
    $data          = getInput();
    $targetPSSN    = (int)($data['P_SSN'] ?? 0);
    $issue         = $db->real_escape_string($data['predicted_issue'] ?? '');
    $risk          = $db->real_escape_string($data['risk_level'] ?? 'Low');

    $db->query("INSERT INTO health_prediction (P_SSN, predicted_issue, risk_level)
                VALUES ($targetPSSN, '$issue', '$risk')");
    if ($db->errno) respondError('Failed to save prediction.');
    respond(['message' => 'Prediction saved.', 'prediction_id' => $db->insert_id], 201);
}

respondError('Method not allowed.', 405);
