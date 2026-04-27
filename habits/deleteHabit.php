<?php
// DELETE /habits/deleteHabit
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'patient');

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['record_id']);

// Confirm ownership
$stmt = $db->prepare("SELECT record_id FROM HABIT WHERE record_id = ? AND P_SSN = ?");
$stmt->execute([$body['record_id'], $jwt['ssn']]);
if (!$stmt->fetch()) sendError("Record not found or not yours", 404);

// Block delete if a TRACK row references this record
$stmt = $db->prepare("SELECT Track_id FROM TRACK WHERE record_id = ?");
$stmt->execute([$body['record_id']]);
if ($stmt->fetch()) sendError("Cannot delete: a nutritionist has reviewed this record. Remove the track first.", 409);

$stmt = $db->prepare("DELETE FROM HABIT WHERE record_id = ?");
$stmt->execute([$body['record_id']]);

sendResponse(["message" => "Habit record deleted"]);
