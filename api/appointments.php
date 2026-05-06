<?php
// Database connection file include করা হচ্ছে
require_once '../config/db.php';

// Helper function file include করা হচ্ছে
// যেমন: requireAuth() function এখান থেকে আসে
require_once '../config/helpers.php';

// Response JSON format এ পাঠানো হবে
header('Content-Type: application/json');

// Database connection নেওয়া হচ্ছে
$conn = getDB();

// Browser/request থেকে কোন HTTP method এসেছে সেটা নেওয়া হচ্ছে
// যেমন: GET, POST
$method = $_SERVER['REQUEST_METHOD'];

// URL থেকে action নেওয়া হচ্ছে
// যেমন: appointment.php?action=create / accept / reject
$action = $_GET['action'] ?? '';

// User logged in আছে কিনা check করা হচ্ছে
// logged in না থাকলে requireAuth() error দিবে
$user = requireAuth();

// Logged in user এর SSN নেওয়া হচ্ছে
$ssn = $user['SSN'];

// User এর role নেওয়া হচ্ছে
// role ছোট হাতের বা বড় হাতের যেকোন key থেকে আসতে পারে
$role = $user['role'] ?? $user['Role'] ?? '';


// ===============================
// GET request হলে appointment list দেখাবে
// ===============================
if ($method === 'GET') {

    // যদি logged in user patient হয়
    if ($role === 'patient') {

        // Patient তার নিজের appointment list দেখবে
        // এখানে nutritionist এর নামও user table থেকে আনা হচ্ছে
        $sql = "
            SELECT 
                a.appointment_id,
                a.P_SSN,
                a.N_SSN,
                a.appointment_date,
                a.appointment_time,
                a.reason,
                a.status,
                u.name AS nutritionist_name
            FROM appointment a
            JOIN user u ON a.N_SSN = u.SSN
            WHERE a.P_SSN = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ";

        // SQL query prepare করা হচ্ছে
        // prepare করলে SQL injection থেকে protection পাওয়া যায়
        $stmt = $conn->prepare($sql);

        // ? এর জায়গায় logged in patient এর SSN বসানো হচ্ছে
        // "i" মানে integer type
        $stmt->bind_param("i", $ssn);

        // Query execute করা হচ্ছে
        $stmt->execute();

        // Query result নেওয়া হচ্ছে
        $result = $stmt->get_result();

        // Appointment data রাখার জন্য empty array
        $appointments = [];

        // Result থেকে এক এক করে row নেওয়া হচ্ছে
        // fetch_assoc() row কে associative array হিসেবে দেয়
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }

        // Appointment list JSON format এ frontend এ পাঠানো হচ্ছে
        echo json_encode($appointments);
        exit;
    }

    // যদি logged in user nutritionist হয়
    if ($role === 'nutritionist') {

        // Nutritionist তার কাছে আসা appointment requests দেখবে
        // এখানে patient এর নামও user table থেকে আনা হচ্ছে
        $sql = "
            SELECT 
                a.appointment_id,
                a.P_SSN,
                a.N_SSN,
                a.appointment_date,
                a.appointment_time,
                a.reason,
                a.status,
                u.name AS patient_name
            FROM appointment a
            JOIN user u ON a.P_SSN = u.SSN
            WHERE a.N_SSN = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ";

        // SQL query prepare করা হচ্ছে
        $stmt = $conn->prepare($sql);

        // ? এর জায়গায় logged in nutritionist এর SSN বসানো হচ্ছে
        $stmt->bind_param("i", $ssn);

        // Query execute করা হচ্ছে
        $stmt->execute();

        // Query result নেওয়া হচ্ছে
        $result = $stmt->get_result();

        // Appointment data রাখার জন্য empty array
        $appointments = [];

        // Result এর সব row array এর মধ্যে রাখা হচ্ছে
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }

        // Appointment list JSON format এ পাঠানো হচ্ছে
        echo json_encode($appointments);
        exit;
    }

    // যদি user patient বা nutritionist না হয়, তাহলে access denied
    http_response_code(403);
    echo json_encode(['error' => 'Only patients and nutritionists can view appointments']);
    exit;
}


// ===============================
// POST request + create action হলে appointment request create করবে
// ===============================
if ($method === 'POST' && $action === 'create') {

    // শুধু patient appointment request করতে পারবে
    if ($role !== 'patient') {
        http_response_code(403);
        echo json_encode(['error' => 'Only patients can request appointments']);
        exit;
    }

    // Frontend থেকে পাঠানো JSON data read করা হচ্ছে
    $data = json_decode(file_get_contents('php://input'), true);

    // JSON data থেকে nutritionist SSN নেওয়া হচ্ছে
    $N_SSN = $data['N_SSN'] ?? null;

    // JSON data থেকে appointment date নেওয়া হচ্ছে
    $appointment_date = $data['appointment_date'] ?? null;

    // JSON data থেকে appointment time নেওয়া হচ্ছে
    $appointment_time = $data['appointment_time'] ?? null;

    // JSON data থেকে reason নেওয়া হচ্ছে
    // reason না থাকলে empty string হবে
    $reason = $data['reason'] ?? '';

    // Required field missing কিনা check করা হচ্ছে
    if (!$N_SSN || !$appointment_date || !$appointment_time) {
        http_response_code(400);
        echo json_encode(['error' => 'Nutritionist, date and time are required']);
        exit;
    }

    // Appointment table এ নতুন appointment request insert করা হবে
    // status initially pending থাকবে
    $sql = "
        INSERT INTO appointment 
        (P_SSN, N_SSN, appointment_date, appointment_time, reason, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ";

    // Insert query prepare করা হচ্ছে
    $stmt = $conn->prepare($sql);

    // Values bind করা হচ্ছে
    // iisss মানে: integer, integer, string, string, string
    $stmt->bind_param("iisss", $ssn, $N_SSN, $appointment_date, $appointment_time, $reason);

    // Query execute successful হলে success message পাঠাবে
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Appointment request sent successfully']);
        exit;
    }

    // Query fail করলে server error দেখাবে
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send appointment request']);
    exit;
}


// ===============================
// POST request + accept action হলে appointment accept করবে
// ===============================
if ($method === 'POST' && $action === 'accept') {

    // শুধু nutritionist appointment accept করতে পারবে
    if ($role !== 'nutritionist') {
        http_response_code(403);
        echo json_encode(['error' => 'Only nutritionists can accept appointments']);
        exit;
    }

    // Frontend থেকে পাঠানো JSON data read করা হচ্ছে
    $data = json_decode(file_get_contents('php://input'), true);

    // JSON data থেকে appointment_id নেওয়া হচ্ছে
    $appointment_id = $data['appointment_id'] ?? null;

    // appointment_id না থাকলে error দেখাবে
    if (!$appointment_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Appointment ID is required']);
        exit;
    }

    // Appointment status accepted করা হচ্ছে
    // AND N_SSN = ? দেওয়া হয়েছে যেন nutritionist শুধু নিজের appointment accept করতে পারে
    $sql = "
        UPDATE appointment
        SET status = 'accepted'
        WHERE appointment_id = ? AND N_SSN = ?
    ";

    // Update query prepare করা হচ্ছে
    $stmt = $conn->prepare($sql);

    // appointment_id এবং nutritionist SSN bind করা হচ্ছে
    // ii মানে দুইটাই integer
    $stmt->bind_param("ii", $appointment_id, $ssn);

    // Query execute successful হলে success message পাঠাবে
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Appointment accepted']);
        exit;
    }

    // Query fail করলে error message পাঠাবে
    http_response_code(500);
    echo json_encode(['error' => 'Failed to accept appointment']);
    exit;
}


// ===============================
// POST request + reject action হলে appointment reject করবে
// ===============================
if ($method === 'POST' && $action === 'reject') {

    // শুধু nutritionist appointment reject করতে পারবে
    if ($role !== 'nutritionist') {
        http_response_code(403);
        echo json_encode(['error' => 'Only nutritionists can reject appointments']);
        exit;
    }

    // Frontend থেকে পাঠানো JSON data read করা হচ্ছে
    $data = json_decode(file_get_contents('php://input'), true);

    // JSON data থেকে appointment_id নেওয়া হচ্ছে
    $appointment_id = $data['appointment_id'] ?? null;

    // appointment_id না থাকলে error দেখাবে
    if (!$appointment_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Appointment ID is required']);
        exit;
    }

    // Appointment status rejected করা হচ্ছে
    // AND N_SSN = ? দেওয়া হয়েছে যেন nutritionist শুধু নিজের appointment reject করতে পারে
    $sql = "
        UPDATE appointment
        SET status = 'rejected'
        WHERE appointment_id = ? AND N_SSN = ?
    ";

    // Update query prepare করা হচ্ছে
    $stmt = $conn->prepare($sql);

    // appointment_id এবং nutritionist SSN bind করা হচ্ছে
    $stmt->bind_param("ii", $appointment_id, $ssn);

    // Query execute successful হলে success message পাঠাবে
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Appointment rejected']);
        exit;
    }

    // Query fail করলে error message পাঠাবে
    http_response_code(500);
    echo json_encode(['error' => 'Failed to reject appointment']);
    exit;
}


// উপরের কোনো condition match না করলে invalid request দেখাবে
http_response_code(405);
echo json_encode(['error' => 'Invalid request']);
exit;
