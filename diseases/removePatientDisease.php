<?php
// DELETE /diseases/removePatientDisease
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'patient');

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['disease_id']);

$stmt = $db->prepare("DELETE FROM PATIENT_DISEASE WHERE P_SSN=? AND disease_id=?");
$stmt->execute([$jwt['ssn'], $body['disease_id']]);

if ($stmt->rowCount() === 0) sendError("Link not found", 404);
sendResponse(["message" => "Disease removed from patient"]);
