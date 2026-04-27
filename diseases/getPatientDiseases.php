<?php
// GET /diseases/getPatientDiseases
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
    "SELECT d.disease_id, d.disease_name
     FROM PATIENT_DISEASE pd
     JOIN DISEASE d ON pd.disease_id = d.disease_id
     WHERE pd.P_SSN = ?"
);
$stmt->execute([$pSSN]);
sendResponse(["data" => $stmt->fetchAll()]);
