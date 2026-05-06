<?php
require_once '../config/helpers.php';
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$db     = getDB();

// this get is for nutri, eikhan theke track korbe
if ($method === 'GET') {
    $ssn = (int)$user['SSN'];

    if ($user['role'] === 'nutritionist') {
        $result = $db->query("
            SELECT t.*, u.name AS patient_name, h.date, h.calories, h.sleep_hours
            FROM TRACK t
            JOIN USER u ON t.P_SSN = u.SSN
            JOIN HABIT h ON t.record_id = h.record_id
            WHERE t.N_SSN = $ssn
            ORDER BY h.date DESC
        ");
    } else {
        $result = $db->query("
            SELECT t.*, u.name AS nutritionist_name, h.date, h.calories, h.sleep_hours
            FROM TRACK t
            JOIN USER u ON t.N_SSN = u.SSN
            JOIN HABIT h ON t.record_id = h.record_id
            WHERE t.P_SSN = $ssn
            ORDER BY h.date DESC
        ");
    }

    $list = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    respond($list);
}

// 

if ($method === 'POST') {
    if ($user['role'] !== 'nutritionist') respondError('Only nutritionists can track.', 403);
    $data = getInput();
    $pSSN = (int)($data['P_SSN'] ?? 0);
    $nSSN = (int)$user['SSN'];

    if (!$pSSN) respondError('P_SSN is required.');

    // take the latest one
    $recordId = (int)($data['record_id'] ?? 0);
    if (!$recordId) {
        $res = $db->query("SELECT record_id FROM HABIT WHERE P_SSN = $pSSN ORDER BY date DESC LIMIT 1");
        if (!$res || $res->num_rows === 0)
            respondError('No habit records found for this patient.');
        $recordId = (int)$res->fetch_assoc()['record_id'];
    }

    $maxRes  = $db->query("SELECT COALESCE(MAX(track_id),0)+1 AS nid FROM TRACK");
    $trackId = $maxRes->fetch_assoc()['nid'];

    $db->query("INSERT INTO TRACK (track_id, P_SSN, N_SSN, record_id)
                VALUES ($trackId, $pSSN, $nSSN, $recordId)");
    if ($db->errno) respondError('Failed to add tracking.');
    respond(['message' => 'Tracking added.'], 201);
}

// DELETE ?track_id=X
if ($method === 'DELETE') {
    $trackId = (int)($_GET['track_id'] ?? 0);
    $nSSN    = (int)$user['SSN'];
    $db->query("DELETE FROM TRACK WHERE track_id = $trackId AND N_SSN = $nSSN");
    respond(['message' => 'Tracking removed.']);
}

respondError('Method not allowed.', 405);
