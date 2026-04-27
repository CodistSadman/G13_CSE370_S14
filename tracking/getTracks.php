<?php
// GET /tracking/getTracks?patient_ssn=...
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'nutritionist');
$db  = (new Database())->getConnection();

$pSSN = $_GET['patient_ssn'] ?? null;
if (!$pSSN) sendError("Provide patient_ssn", 422);

$stmt = $db->prepare(
    "SELECT t.Track_id, t.date, t.record_id,
            h.calories, h.sleep_hours, h.date AS habit_date
     FROM TRACK t
     JOIN HABIT h ON t.record_id = h.record_id
     WHERE t.P_SSN = ? AND t.N_SSN = ?
     ORDER BY t.date DESC"
);
$stmt->execute([$pSSN, $jwt['ssn']]);
sendResponse(["data" => $stmt->fetchAll()]);
