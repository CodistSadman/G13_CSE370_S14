<?php
require_once '../config/helpers.php';
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$pSSN   = (int)$user['SSN'];
$db     = getDB();

// GET - ekhane ami received request dekhte parbo ar hocche friend er most recent habits dekhte pabo
if ($method === 'GET') {
    $requests = [];
    $result = $db->query("
        SELECT fr.request_id, fr.sender_P_SSN, u.name AS sender_name, u.email AS sender_email
        FROM friend_request fr
        JOIN user u ON fr.sender_P_SSN = u.SSN
        WHERE fr.receiver_P_SSN = $pSSN
    ");
    if ($result) while ($row = $result->fetch_assoc()) $requests[] = $row;

    $friendsList = [];
    $fResult = $db->query("
        SELECT u.name, u.email, u.SSN,
               h.calories, h.sleep_hours, h.step_count, h.date AS habit_date
        FROM friends f
        JOIN user u ON (
            (f.patient1_SSN = $pSSN AND u.SSN = f.patient2_SSN)
            OR
            (f.patient2_SSN = $pSSN AND u.SSN = f.patient1_SSN)
        )
        LEFT JOIN habit h ON h.record_id = (
            SELECT record_id FROM habit
            WHERE P_SSN = u.SSN
            ORDER BY date DESC, record_id DESC
            LIMIT 1
        )
        WHERE f.patient1_SSN != 0 AND f.patient2_SSN != 0
    ");
    if ($fResult) while ($row = $fResult->fetch_assoc()) $friendsList[] = $row;

    respond(['requests' => $requests, 'friends' => $friendsList]);
}

// POST - friend request SEND korbe 
if ($method === 'POST') {
    $data        = getInput();
    $receiverSSN = (int)($data['receiver_P_SSN'] ?? 0);

    if (!$receiverSSN)           respondError('Receiver SSN is required.');
    if ($receiverSSN === $pSSN)  respondError('Cannot send request to yourself.');

    // receiver exit kore kina eta check kortesi
    $check = $db->query("SELECT SSN FROM user WHERE SSN = $receiverSSN LIMIT 1");
    if (!$check || $check->num_rows === 0) respondError('User not found.');

    $db->query("INSERT INTO friend_request (sender_P_SSN, receiver_P_SSN)
                VALUES ($pSSN, $receiverSSN)");
    if ($db->errno) respondError('Request already sent.');
    respond(['message' => 'Friend request sent.'], 201);
}

// PATCH - accept a friend request
if ($method === 'PATCH') {
    $data = getInput();
    $id   = (int)($data['request_id'] ?? 0);

    $res = $db->query("SELECT * FROM friend_request WHERE request_id = $id AND receiver_P_SSN = $pSSN");
    if (!$res) respondError('Server error.');
    $req = $res->fetch_assoc();
    if (!$req) respondError('Request not found.');

    $p1 = (int)$req['sender_P_SSN'];
    $p2 = $pSSN;

    $db->query("INSERT IGNORE INTO friends (patient1_SSN, patient2_SSN) VALUES ($p1, $p2)");
    $db->query("DELETE FROM friend_request WHERE request_id = $id");

    respond(['message' => 'Friend request accepted.']);
}

// DELETE - decline or cancel request
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    $db->query("DELETE FROM friend_request WHERE request_id = $id
                AND (sender_P_SSN = $pSSN OR receiver_P_SSN = $pSSN)");
    respond(['message' => 'Request removed.']);
}

respondError('Method not allowed.', 405);
