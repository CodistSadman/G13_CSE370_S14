<?php
// POST /tracking/createTrack
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'nutritionist');

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['P_SSN','record_id','date']);

$nSSN = $jwt['ssn'];

// Verify patient is subscribed to this nutritionist
$stmt = $db->prepare("SELECT P_SSN FROM SUBSCRIBE WHERE P_SSN=? AND N_SSN=? AND end_date >= CURDATE()");
$stmt->execute([$body['P_SSN'], $nSSN]);
if (!$stmt->fetch()) sendError("This patient is not subscribed to you", 403);

// Verify the habit record belongs to this patient
$stmt = $db->prepare("SELECT record_id FROM HABIT WHERE record_id=? AND P_SSN=?");
$stmt->execute([$body['record_id'], $body['P_SSN']]);
if (!$stmt->fetch()) sendError("Habit record not found for this patient", 404);

$stmt = $db->prepare("INSERT INTO TRACK (P_SSN, record_id, N_SSN, date) VALUES (?,?,?,?)");
$stmt->execute([$body['P_SSN'], $body['record_id'], $nSSN, $body['date']]);

sendResponse(["message" => "Track created", "Track_id" => $db->lastInsertId()], 201);
