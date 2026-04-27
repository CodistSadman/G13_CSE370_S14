<?php
// PUT /habits/updateHabit
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'patient');

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['record_id']);

// Confirm ownership
$stmt = $db->prepare("SELECT * FROM HABIT WHERE record_id = ? AND P_SSN = ?");
$stmt->execute([$body['record_id'], $jwt['ssn']]);
$habit = $stmt->fetch();
if (!$habit) sendError("Record not found or not yours", 404);

$calories    = isset($body['calories'])    ? (int)$body['calories']       : $habit['calories'];
$sleep_hours = isset($body['sleep_hours']) ? (float)$body['sleep_hours']  : $habit['sleep_hours'];
$date        = $body['date'] ?? $habit['date'];

$stmt = $db->prepare("UPDATE HABIT SET calories=?, sleep_hours=?, date=? WHERE record_id=?");
$stmt->execute([$calories, $sleep_hours, $date, $body['record_id']]);

sendResponse(["message" => "Habit updated", "record_id" => $body['record_id']]);
