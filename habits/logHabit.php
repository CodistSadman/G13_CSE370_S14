<?php
// POST /habits/logHabit
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'patient');

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['date','calories','sleep_hours']);

$pSSN        = $jwt['ssn'];
$date        = $body['date'];
$calories    = (int)$body['calories'];
$sleep_hours = (float)$body['sleep_hours'];

// Prevent duplicate log for same day
$stmt = $db->prepare("SELECT record_id FROM HABIT WHERE P_SSN = ? AND date = ?");
$stmt->execute([$pSSN, $date]);
if ($stmt->fetch()) sendError("Habit already logged for this date. Use update instead.", 409);

$stmt = $db->prepare("INSERT INTO HABIT (P_SSN, date, calories, sleep_hours) VALUES (?,?,?,?)");
$stmt->execute([$pSSN, $date, $calories, $sleep_hours]);

sendResponse([
    "message"    => "Habit logged",
    "record_id"  => $db->lastInsertId(),
    "P_SSN"      => $pSSN,
    "date"       => $date,
    "calories"   => $calories,
    "sleep_hours"=> $sleep_hours
], 201);
