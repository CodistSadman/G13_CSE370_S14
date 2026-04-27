<?php
// POST /auth/register
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError("Method not allowed", 405);

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['SSN','name','email','password','gender','role']);

$ssn      = trim($body['SSN']);
$name     = trim($body['name']);
$email    = trim($body['email']);
$password = password_hash($body['password'], PASSWORD_BCRYPT);
$gender   = $body['gender'];
$role     = $body['role']; // 'patient' or 'nutritionist'

if (!in_array($role, ['patient','nutritionist'])) sendError("role must be 'patient' or 'nutritionist'");
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) sendError("Invalid email format");

// Check duplicate SSN or email
$stmt = $db->prepare("SELECT SSN FROM USER WHERE SSN = ? OR email = ?");
$stmt->execute([$ssn, $email]);
if ($stmt->fetch()) sendError("SSN or email already registered", 409);

$db->beginTransaction();
try {
    // Insert USER
    $stmt = $db->prepare("INSERT INTO USER (SSN, name, email, password, gender) VALUES (?,?,?,?,?)");
    $stmt->execute([$ssn, $name, $email, $password, $gender]);

    if ($role === 'patient') {
        requireFields($body, ['goal']);
        $stmt = $db->prepare("INSERT INTO PATIENT (P_SSN, goal) VALUES (?,?)");
        $stmt->execute([$ssn, $body['goal']]);

    } elseif ($role === 'nutritionist') {
        requireFields($body, ['bio','qualification','experience_year']);
        $stmt = $db->prepare("INSERT INTO NUTRIOTIONIST (N_SSN, bio, qualification, experience_year) VALUES (?,?,?,?)");
        $stmt->execute([$ssn, $body['bio'], $body['qualification'], (int)$body['experience_year']]);
    }

    $db->commit();
    $token = generateJWT(['ssn' => $ssn, 'role' => $role]);
    sendResponse(["message" => "Registered successfully", "token" => $token], 201);

} catch (Exception $e) {
    $db->rollBack();
    sendError("Registration failed: " . $e->getMessage(), 500);
}
