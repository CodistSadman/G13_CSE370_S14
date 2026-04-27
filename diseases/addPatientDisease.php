<?php
// POST /diseases/addPatientDisease
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'patient');

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['disease_id']);

// Verify disease exists
$stmt = $db->prepare("SELECT disease_id FROM DISEASE WHERE disease_id = ?");
$stmt->execute([$body['disease_id']]);
if (!$stmt->fetch()) sendError("Disease not found", 404);

// Prevent duplicate
$stmt = $db->prepare("SELECT P_SSN FROM PATIENT_DISEASE WHERE P_SSN=? AND disease_id=?");
$stmt->execute([$jwt['ssn'], $body['disease_id']]);
if ($stmt->fetch()) sendError("Disease already linked to this patient", 409);

$stmt = $db->prepare("INSERT INTO PATIENT_DISEASE (P_SSN, disease_id) VALUES (?,?)");
$stmt->execute([$jwt['ssn'], $body['disease_id']]);

sendResponse(["message" => "Disease linked to patient"], 201);
