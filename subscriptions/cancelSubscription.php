<?php
// DELETE /subscriptions/cancelSubscription
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'patient');

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['N_SSN']);

// Set end_date to today (soft cancel — keeps history)
$stmt = $db->prepare(
    "UPDATE SUBSCRIBE SET end_date = CURDATE()
     WHERE P_SSN = ? AND N_SSN = ? AND end_date >= CURDATE()"
);
$stmt->execute([$jwt['ssn'], $body['N_SSN']]);

if ($stmt->rowCount() === 0) sendError("No active subscription found with this nutritionist", 404);
sendResponse(["message" => "Subscription cancelled"]);
