<?php
// POST /subscriptions/subscribe
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'patient');

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['N_SSN','start_date','end_date']);

// Verify nutritionist exists
$stmt = $db->prepare("SELECT N_SSN FROM NUTRIOTIONIST WHERE N_SSN = ?");
$stmt->execute([$body['N_SSN']]);
if (!$stmt->fetch()) sendError("Nutritionist not found", 404);

// Check for existing active subscription with same nutritionist
$stmt = $db->prepare("SELECT P_SSN FROM SUBSCRIBE WHERE P_SSN=? AND N_SSN=? AND end_date >= CURDATE()");
$stmt->execute([$jwt['ssn'], $body['N_SSN']]);
if ($stmt->fetch()) sendError("Already subscribed to this nutritionist", 409);

if ($body['end_date'] <= $body['start_date']) sendError("end_date must be after start_date");

$stmt = $db->prepare("INSERT INTO SUBSCRIBE (P_SSN, N_SSN, start_date, end_date) VALUES (?,?,?,?)");
$stmt->execute([$jwt['ssn'], $body['N_SSN'], $body['start_date'], $body['end_date']]);

sendResponse(["message" => "Subscribed successfully"], 201);
