<?php
// GET /subscriptions/getSubscription
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'patient');
$db  = (new Database())->getConnection();

$stmt = $db->prepare(
    "SELECT s.N_SSN, u.name AS nutritionist_name, n.qualification,
            n.experience_year, s.start_date, s.end_date,
            IF(s.end_date >= CURDATE(), 'active', 'expired') AS status
     FROM SUBSCRIBE s
     JOIN NUTRIOTIONIST n ON s.N_SSN = n.N_SSN
     JOIN USER u           ON n.N_SSN = u.SSN
     WHERE s.P_SSN = ?
     ORDER BY s.end_date DESC"
);
$stmt->execute([$jwt['ssn']]);
sendResponse(["data" => $stmt->fetchAll()]);
