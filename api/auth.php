<?php
require_once '../config/helpers.php';
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// POST /api/auth.php?action=register
if ($method === 'POST' && $action === 'register') {
    $data = getInput();
    $required = ['SSN', 'name', 'email', 'password', 'gender', 'role'];
    foreach ($required as $field) {
        if (empty($data[$field])) respondError("Field '$field' is required.");
    }

    $db = getDB();
    $ssn      = (int)$data['SSN'];
    $name     = $db->real_escape_string($data['name']);
    $email    = $db->real_escape_string($data['email']);
    $password = password_hash($data['password'], PASSWORD_BCRYPT);
    $gender   = $db->real_escape_string($data['gender']);
    $role     = $data['role']; // 'patient' or 'nutritionist'
    $age  = (int)($data['age'] ?? 0);

$db->query("INSERT INTO user (SSN, name, email, password, gender, age)
            VALUES ($ssn, '$name', '$email', '$password', '$gender', $age)");
    if ($db->errno) respondError('Email or SSN already exists.', 409);

    // Insert into role-specific table
    if ($role === 'patient') {
        $goal = $db->real_escape_string($data['goal'] ?? '');
        $db->query("INSERT INTO patient (P_SSN, goal) VALUES ($ssn, '$goal')");
    } elseif ($role === 'nutritionist') {
        $bio  = $db->real_escape_string($data['bio'] ?? '');
        $exp  = (int)($data['experience_years'] ?? 0);
        $qual = $db->real_escape_string($data['qualification'] ?? '');
        $db->query("INSERT INTO nutritionist (N_SSN, bio, experience_years, qualification)
                    VALUES ($ssn, '$bio', $exp, '$qual')");
    }

    respond(['message' => 'Registered successfully.', 'SSN' => $ssn], 201);
}

// POST /api/auth.php?action=login
if ($method === 'POST' && $action === 'login') {
    $data  = getInput();
    $email = $data['email'] ?? '';
    $pass  = $data['password'] ?? '';

    if (!$email || !$pass) respondError('Email and password required.');

    $db = getDB();
    $email_safe = $db->real_escape_string($email);

    $result = $db->query("SELECT * FROM user WHERE email = '$email_safe'");
    $user   = $result->fetch_assoc();

    if (!$user || !password_verify($pass, $user['password'])) {
        respondError('Invalid credentials.', 401);
    }

    // Determine role
    $ssn  = $user['SSN'];
    $role = 'user';
    $extra = [];

    $p = $db->query("SELECT * FROM patient WHERE P_SSN = $ssn")->fetch_assoc();
    if ($p) { $role = 'patient'; $extra = $p; }

    $n = $db->query("SELECT * FROM nutritionist WHERE N_SSN = $ssn")->fetch_assoc();
    if ($n) { $role = 'nutritionist'; $extra = $n; }

    unset($user['password']);
    $_SESSION['user'] = array_merge($user, ['role' => $role], $extra);

    respond(['message' => 'Login successful.', 'user' => $_SESSION['user']]);
}

// POST /api/auth.php?action=logout
if ($method === 'POST' && $action === 'logout') {
    session_destroy();
    respond(['message' => 'Logged out.']);
}

respondError('Invalid action.', 404);
