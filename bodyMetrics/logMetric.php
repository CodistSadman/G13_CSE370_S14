<?php
// POST /bodyMetrics/logMetric
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'patient');

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['date','height','weight']);

$stmt = $db->prepare("INSERT INTO BODY_METRICS (P_SSN, date, height, weight) VALUES (?,?,?,?)");
$stmt->execute([$jwt['ssn'], $body['date'], (float)$body['height'], (float)$body['weight']]);

$bmi = round((float)$body['weight'] / (((float)$body['height'] / 100) ** 2), 1);

sendResponse([
    "message"   => "Metrics logged",
    "metric_id" => $db->lastInsertId(),
    "bmi"       => $bmi
], 201);
