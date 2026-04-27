<?php
// POST /healthPredictions/createPrediction
// Called by nutritionist or internal service
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError("Method not allowed", 405);

$jwt = requireAuth();
if (!in_array($jwt['role'], ['nutritionist','developer'])) sendError("Forbidden", 403);

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['P_SSN','predicted_issue','risk_level']);

if (!in_array($body['risk_level'], ['low','medium','high'])) {
    sendError("risk_level must be low, medium, or high");
}

$stmt = $db->prepare("INSERT INTO HEALTH_PREDICTION (P_SSN, predicted_issue, risk_level) VALUES (?,?,?)");
$stmt->execute([$body['P_SSN'], $body['predicted_issue'], $body['risk_level']]);

sendResponse([
    "message"       => "Prediction created",
    "prediction_id" => $db->lastInsertId()
], 201);
