<?php
// GET /bodyMetrics/getMetrics?limit=30
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError("Method not allowed", 405);

$jwt  = requireAuth();
$db   = (new Database())->getConnection();
$pSSN = $jwt['ssn'];

if ($jwt['role'] === 'nutritionist') {
    if (empty($_GET['patient_ssn'])) sendError("Provide patient_ssn", 422);
    $pSSN = $_GET['patient_ssn'];
    $stmt = $db->prepare("SELECT P_SSN FROM SUBSCRIBE WHERE P_SSN=? AND N_SSN=? AND end_date >= CURDATE()");
    $stmt->execute([$pSSN, $jwt['ssn']]);
    if (!$stmt->fetch()) sendError("Not subscribed to this patient", 403);
}

$limit = min(100, (int)($_GET['limit'] ?? 30));

$stmt = $db->prepare(
    "SELECT metric_id, date, height, weight,
            ROUND(weight / POW(height/100, 2), 1) AS bmi
     FROM BODY_METRICS
     WHERE P_SSN = ?
     ORDER BY date DESC
     LIMIT ?"
);
$stmt->execute([$pSSN, $limit]);

sendResponse(["data" => $stmt->fetchAll()]);
