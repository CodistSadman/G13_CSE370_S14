<?php
// PUT /social/respondToRequest
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'patient');

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['request_id','status']);

if (!in_array($body['status'], ['accepted','rejected'])) {
    sendError("status must be 'accepted' or 'rejected'");
}

// Confirm this request was sent TO the current user
$stmt = $db->prepare("SELECT * FROM FRIEND_REQ WHERE request_id=? AND P_SSN=? AND status='pending'");
$stmt->execute([$body['request_id'], $jwt['ssn']]);
$req = $stmt->fetch();
if (!$req) sendError("Request not found or already responded", 404);

$stmt = $db->prepare("UPDATE FRIEND_REQ SET status=? WHERE request_id=?");
$stmt->execute([$body['status'], $body['request_id']]);

sendResponse(["message" => "Request " . $body['status']]);
