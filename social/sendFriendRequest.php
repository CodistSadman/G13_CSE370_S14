<?php
// POST /social/sendFriendRequest
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'patient');

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['receiver_ssn']);

$sender   = $jwt['ssn'];
$receiver = $body['receiver_ssn'];

if ($sender === $receiver) sendError("Cannot send request to yourself");

// Verify receiver exists and is a patient
$stmt = $db->prepare("SELECT P_SSN FROM PATIENT WHERE P_SSN = ?");
$stmt->execute([$receiver]);
if (!$stmt->fetch()) sendError("Receiver not found or not a patient", 404);

// Check for existing pending request in either direction
$stmt = $db->prepare(
    "SELECT request_id FROM FRIEND_REQ
     WHERE ((sender=? AND P_SSN=?) OR (sender=? AND P_SSN=?))
     AND status = 'pending'"
);
$stmt->execute([$sender, $receiver, $receiver, $sender]);
if ($stmt->fetch()) sendError("A pending request already exists between you two", 409);

$stmt = $db->prepare("INSERT INTO FRIEND_REQ (sender, P_SSN, status) VALUES (?,?,'pending')");
$stmt->execute([$sender, $receiver]);

sendResponse(["message" => "Friend request sent", "request_id" => $db->lastInsertId()], 201);
