<?php
require_once '../config/helpers.php';
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$db     = getDB();

// GET patient sees their own predictions, nutritionist  ekta empty list pabe 
if ($method === 'GET') {
    if ($user['role'] === 'nutritionist') {
        respond([]);
    }
    $pSSN   = (int)$user['SSN'];
    $result = $db->query("
        SELECT prediction_id, P_SSN, predicted_issue, risk_level, suggestion_text
        FROM health_prediction
        WHERE P_SSN = $pSSN
        ORDER BY prediction_id DESC
    ");
    $predictions = [];
    while ($row = $result->fetch_assoc()) $predictions[] = $row;
    respond($predictions);
}

// POST - nutriotinist ekta prediction create korbe for the patient ar patient kono prediction add korte parbena
if ($method === 'POST') {
    if ($user['role'] !== 'nutritionist') respondError('Only nutritionists can add predictions.', 403);
    $data           = getInput();
    $targetPSSN     = (int)($data['P_SSN'] ?? 0);
    $issue          = $db->real_escape_string($data['predicted_issue'] ?? '');
    $risk           = $db->real_escape_string($data['risk_level'] ?? 'Low');
    $suggestionText = $db->real_escape_string($data['suggestion_text'] ?? '');

    if (!$targetPSSN || !$issue) respondError('P_SSN and predicted_issue are required.');

    $check = $db->query("SELECT P_SSN FROM patient WHERE P_SSN = $targetPSSN");
    if (!$check || $check->num_rows === 0) respondError('Patient not found.');

    $idRes  = $db->query("SELECT COALESCE(MAX(prediction_id), 0) + 1 AS nid FROM health_prediction");
    $predId = (int)$idRes->fetch_assoc()['nid'];

    $db->query("INSERT INTO health_prediction (prediction_id, P_SSN, predicted_issue, risk_level, suggestion_text)
                VALUES ($predId, $targetPSSN, '$issue', '$risk', '$suggestionText')");
    if ($db->errno) respondError('Failed to save prediction: ' . $db->error);
    respond(['message' => 'Prediction saved.', 'prediction_id' => $predId], 201);
}

respondError('Method not allowed.', 405);
