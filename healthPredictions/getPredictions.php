<?php
// GET /healthPredictions/getPredictions
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError("Method not allowed", 405);

$jwt  = requireAuth();
$db   = (new Database())->getConnection();
$pSSN = $jwt['role'] === 'patient' ? $jwt['ssn'] : ($_GET['patient_ssn'] ?? null);

if (!$pSSN) sendError("Provide patient_ssn", 422);

$stmt = $db->prepare(
    "SELECT hp.prediction_id, hp.predicted_issue, hp.risk_level,
            s.suggestion, s.test
     FROM HEALTH_PREDICTION hp
     LEFT JOIN SUGGESTION s ON hp.predicted_issue = s.predicted_issue
     WHERE hp.P_SSN = ?
     ORDER BY hp.prediction_id DESC"
);
$stmt->execute([$pSSN]);

sendResponse(["data" => $stmt->fetchAll()]);
