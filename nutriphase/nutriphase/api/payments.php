<?php
require_once '../config/db.php';
require_once '../config/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$db     = getDB();

// GET → list payments (nutritionist sees own payments)
if ($method === 'GET') {
    $nSSN   = (int)$user['SSN'];
    $result = $db->query("
        SELECT p.*, s.P_SSN
        FROM PAYMENT p
        LEFT JOIN subscribe s ON p.N_SSN = s.N_SSN
        WHERE p.N_SSN = $nSSN
        GROUP BY p.transaction_id
    ");
    $list = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    respond($list);
}

// POST → record a payment
if ($method === 'POST') {
    if ($user['role'] !== 'nutritionist') respondError('Only nutritionists can make payments.', 403);
    $data        = getInput();
    $amount      = (int)($data['amount'] ?? 0);
    $developerId = (int)($data['developer_id'] ?? 0);
    $nSSN        = (int)$user['SSN'];

    if (!$amount || !$developerId) respondError('amount and developer_id required.');

    $maxRes = $db->query("SELECT COALESCE(MAX(transaction_id),0)+1 AS nid FROM PAYMENT");
    $tid    = $maxRes->fetch_assoc()['nid'];

    $db->query("INSERT INTO PAYMENT (transaction_id, amount, developer_id, N_SSN)
                VALUES ($tid, $amount, $developerId, $nSSN)");
    if ($db->errno) respondError('Payment failed.');
    respond(['message' => 'Payment recorded.', 'transaction_id' => $tid], 201);
}

respondError('Method not allowed.', 405);
