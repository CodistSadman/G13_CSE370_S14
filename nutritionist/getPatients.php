<?php
// GET /nutritionist/getPatients
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'nutritionist');
$db  = (new Database())->getConnection();

$stmt = $db->prepare(
    "SELECT u.SSN, u.name, u.email, u.gender, p.goal,
            s.start_date, s.end_date
     FROM SUBSCRIBE s
     JOIN PATIENT p  ON s.P_SSN = p.P_SSN
     JOIN USER u     ON p.P_SSN = u.SSN
     WHERE s.N_SSN = ? AND s.end_date >= CURDATE()
     ORDER BY u.name"
);
$stmt->execute([$jwt['ssn']]);
sendResponse(["data" => $stmt->fetchAll()]);
