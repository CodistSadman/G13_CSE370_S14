<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

function respondError($message, $code = 400) {
    respond(['error' => $message], $code);
}

function getInput() {
    $input = json_decode(file_get_contents('php://input'), true);
    return $input ?? $_POST;
}

session_start();

function requireAuth() {
    if (!isset($_SESSION['user'])) {
        respondError('Unauthorized', 401);
    }
    return $_SESSION['user'];
}
