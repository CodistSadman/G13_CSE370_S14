<?php
ob_start();
require_once '../config/helpers.php';
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$db     = getDB();

if ($method === 'GET') {
    if ($user['role'] !== 'nutritionist') { ob_end_clean(); respondError('Only nutritionists can view payments.', 403); }

    $nSSN = (int)$user['SSN'];

    
    $col = $db->query("SHOW COLUMNS FROM payment LIKE 'P_SSN'"); //column check korbe 
    if ($col && $col->num_rows === 0) {
        $db->query("ALTER TABLE payment ADD COLUMN P_SSN INT(11) DEFAULT NULL");
    }

    $result = $db->query("
        SELECT p.transaction_id, p.amount, p.P_SSN, p.created_at,
               u.name AS patient_name
        FROM payment p
        LEFT JOIN user u ON u.SSN = p.P_SSN
        WHERE p.N_SSN = $nSSN
        ORDER BY p.created_at DESC
    ");

    if (!$result) { ob_end_clean(); respondError('Query failed: ' . $db->error); }

    $list = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    ob_end_clean();
    respond($list);
}

ob_end_clean();
respondError('Method not allowed.', 405);
