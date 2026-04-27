<?php
// GET /diseases/listDiseases
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError("Method not allowed", 405);

requireAuth();
$db = (new Database())->getConnection();

$stmt = $db->query("SELECT disease_id, disease_name FROM DISEASE ORDER BY disease_name");
sendResponse(["data" => $stmt->fetchAll()]);
