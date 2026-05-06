<?php
// Database connection file include করা হচ্ছে
require_once '../config/db.php';


require_once '../config/helpers.php';


header('Content-Type: application/json');


$conn = getDB();

$method = $_SERVER['REQUEST_METHOD'];


$action = $_GET['action'] ?? '';

// logged in no requireAuth() error 
$user = requireAuth();


$ssn = $user['SSN'];
$role = $user['role'] ?? $user['Role'] ?? '';



if ($method === 'GET') {

    // যদি logged in user patient হয়
    if ($role === 'patient') {


        //nutritionist = user table
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

        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ssn);

        $stmt->execute();

    ে
        $result = $stmt->get_result();

        $appointments = [];

        
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }

     
        echo json_encode($appointments);
        exit;
    }

   
    if ($role === 'nutritionist') {


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

    
        $stmt = $conn->prepare($sql);

 
        $stmt->bind_param("i", $ssn);


        $stmt->execute();


        $result = $stmt->get_result();

        
        $appointments = [];

        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }

   
        echo json_encode($appointments);
        exit;
    }

    
    http_response_code(403);
    echo json_encode(['error' => 'Only patients and nutritionists can view appointments']);
    exit;
}



if ($method === 'POST' && $action === 'create') {


    if ($role !== 'patient') {
        http_response_code(403);
        echo json_encode(['error' => 'Only patients can request appointments']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

 ে
    $N_SSN = $data['N_SSN'] ?? null;


    $appointment_date = $data['appointment_date'] ?? null;

 
    $appointment_time = $data['appointment_time'] ?? null;


    $reason = $data['reason'] ?? '';

    if (!$N_SSN || !$appointment_date || !$appointment_time) {
        http_response_code(400);
        echo json_encode(['error' => 'Nutritionist, date and time are required']);
        exit;
    }


    $sql = "
        INSERT INTO appointment 
        (P_SSN, N_SSN, appointment_date, appointment_time, reason, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ";

    // Insert query prepare করা হচ্ছে
    $stmt = $conn->prepare($sql);


  
    $stmt->bind_param("iisss", $ssn, $N_SSN, $appointment_date, $appointment_time, $reason);

   
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Appointment request sent successfully']);
        exit;
    }


    http_response_code(500);
    echo json_encode(['error' => 'Failed to send appointment request']);
    exit;
}

if ($method === 'POST' && $action === 'accept') {

    
    if ($role !== 'nutritionist') {
        http_response_code(403);
        echo json_encode(['error' => 'Only nutritionists can accept appointments']);
        exit;
    }


    $data = json_decode(file_get_contents('php://input'), true);

 
    $appointment_id = $data['appointment_id'] ?? null;


    if (!$appointment_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Appointment ID is required']);
        exit;
    }

    $sql = "
        UPDATE appointment
        SET status = 'accepted'
        WHERE appointment_id = ? AND N_SSN = ?
    ";

    // Update query prepare
    $stmt = $conn->prepare($sql);

  
    // ii= integer
    $stmt->bind_param("ii", $appointment_id, $ssn);

    
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Appointment accepted']);
        exit;
    }


    http_response_code(500);
    echo json_encode(['error' => 'Failed to accept appointment']);
    exit;
}
if ($method === 'POST' && $action === 'reject') {

    
    if ($role !== 'nutritionist') {
        http_response_code(403);
        echo json_encode(['error' => 'Only nutritionists can reject appointments']);
        exit;
    }

 
    $data = json_decode(file_get_contents('php://input'), true);


    $appointment_id = $data['appointment_id'] ?? null;


    if (!$appointment_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Appointment ID is required']);
        exit;
    }


    $sql = "
        UPDATE appointment
        SET status = 'rejected'
        WHERE appointment_id = ? AND N_SSN = ?
    ";

    // Update query prepare 
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $appointment_id, $ssn);


    if ($stmt->execute()) {
        echo json_encode(['message' => 'Appointment rejected']);
        exit;
    }

    http_response_code(500);
    echo json_encode(['error' => 'Failed to reject appointment']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Invalid request']);
exit;
