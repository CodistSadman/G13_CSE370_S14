<?php
// POST /auth/login
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError("Method not allowed", 405);

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['email','password']);

$stmt = $db->prepare("SELECT * FROM USER WHERE email = ?");
$stmt->execute([trim($body['email'])]);
$user = $stmt->fetch();

if (!$user || !password_verify($body['password'], $user['password'])) {
    sendError("Invalid email or password", 401);
}

// Determine role
$stmt = $db->prepare("SELECT P_SSN FROM PATIENT WHERE P_SSN = ?");
$stmt->execute([$user['SSN']]);
$role = $stmt->fetch() ? 'patient' : 'nutritionist';

// Check developer override
$stmt = $db->prepare("SELECT DEVELOPER_ID FROM DEVELOPER WHERE email = ?");
$stmt->execute([$user['email']]);
if ($stmt->fetch()) $role = 'developer';

$token = generateJWT(['ssn' => $user['SSN'], 'role' => $role]);

unset($user['password']);
sendResponse(["message" => "Login successful", "token" => $token, "user" => $user, "role" => $role]);
