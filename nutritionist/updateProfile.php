<?php
// PUT /nutritionist/updateProfile
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'nutritionist');

$db   = (new Database())->getConnection();
$body = getRequestBody();

$stmt = $db->prepare("SELECT * FROM NUTRIOTIONIST WHERE N_SSN = ?");
$stmt->execute([$jwt['ssn']]);
$current = $stmt->fetch();
if (!$current) sendError("Nutritionist not found", 404);

$bio         = $body['bio']             ?? $current['bio'];
$qual        = $body['qualification']   ?? $current['qualification'];
$exp         = $body['experience_year'] ?? $current['experience_year'];

$stmt = $db->prepare("UPDATE NUTRIOTIONIST SET bio=?, qualification=?, experience_year=? WHERE N_SSN=?");
$stmt->execute([$bio, $qual, (int)$exp, $jwt['ssn']]);

sendResponse(["message" => "Profile updated"]);
