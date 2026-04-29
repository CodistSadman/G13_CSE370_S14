<?php
require_once '../config/db.php';
require_once '../config/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$pSSN   = (int)$user['SSN'];
$db     = getDB();

// GET ?action=all → all diseases in system
// GET             → patient's own diseases
if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'all') {
        $result = $db->query("SELECT * FROM DISEASE ORDER BY disease_name");
        $list = [];
        while ($row = $result->fetch_assoc()) $list[] = $row;
        respond($list);
    }

    $result = $db->query("
        SELECT d.disease_id, d.disease_name
        FROM PATIENT_DISEASE pd
        JOIN DISEASE d ON pd.disease_id = d.disease_id
        WHERE pd.P_SSN = $pSSN
    ");
    $list = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    respond($list);
}

// POST → add disease to patient
if ($method === 'POST') {
    $data      = getInput();
    $diseaseId = (int)($data['disease_id'] ?? 0);
    if (!$diseaseId) respondError('disease_id required.');
    $db->query("INSERT IGNORE INTO PATIENT_DISEASE (P_SSN, disease_id) VALUES ($pSSN, $diseaseId)");
    if ($db->errno) respondError('Failed to add disease.');
    respond(['message' => 'Disease added.'], 201);
}

// DELETE ?disease_id=X → remove disease from patient
if ($method === 'DELETE') {
    $diseaseId = (int)($_GET['disease_id'] ?? 0);
    $db->query("DELETE FROM PATIENT_DISEASE WHERE P_SSN = $pSSN AND disease_id = $diseaseId");
    respond(['message' => 'Disease removed.']);
}

respondError('Method not allowed.', 405);
