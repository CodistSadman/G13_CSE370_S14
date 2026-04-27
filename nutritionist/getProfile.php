<?php
// GET /nutritionist/getProfile?n_ssn=...
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError("Method not allowed", 405);

$jwt = requireAuth();
$db  = (new Database())->getConnection();

$nSSN = $_GET['n_ssn'] ?? ($jwt['role'] === 'nutritionist' ? $jwt['ssn'] : null);
if (!$nSSN) sendError("Provide n_ssn", 422);

$stmt = $db->prepare(
    "SELECT u.name, u.gender, n.N_SSN, n.bio, n.qualification, n.experience_year
     FROM NUTRIOTIONIST n
     JOIN USER u ON n.N_SSN = u.SSN
     WHERE n.N_SSN = ?"
);
$stmt->execute([$nSSN]);
$profile = $stmt->fetch();
if (!$profile) sendError("Nutritionist not found", 404);

sendResponse($profile);
