<?php
// POST /transactions/createTransaction
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError("Method not allowed", 405);

$jwt = requireAuth();
// Only nutritionists or developers can log transactions
if (!in_array($jwt['role'], ['nutritionist','developer'])) sendError("Forbidden", 403);

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['amount','N_SSN','DEVELOPER_ID']);

// Validate references
$stmt = $db->prepare("SELECT N_SSN FROM NUTRIOTIONIST WHERE N_SSN=?");
$stmt->execute([$body['N_SSN']]);
if (!$stmt->fetch()) sendError("Nutritionist not found", 404);

$stmt = $db->prepare("SELECT DEVELOPER_ID FROM DEVELOPER WHERE DEVELOPER_ID=?");
$stmt->execute([$body['DEVELOPER_ID']]);
if (!$stmt->fetch()) sendError("Developer not found", 404);

$stmt = $db->prepare("INSERT INTO TRANSACTION (amount, N_SSN, DEVELOPER_ID) VALUES (?,?,?)");
$stmt->execute([(float)$body['amount'], $body['N_SSN'], $body['DEVELOPER_ID']]);

sendResponse(["message" => "Transaction recorded", "TRANSACTION_ID" => $db->lastInsertId()], 201);
