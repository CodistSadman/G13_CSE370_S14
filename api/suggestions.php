<?php
require_once '../config/helpers.php';
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$db     = getDB();

// GET → suggestions for patient's predicted issues
if ($method === 'GET') {
    $pSSN = (int)$user['SSN'];
    $result = $db->query("
        SELECT hp.predicted_issue, hp.risk_level, s.suggestion_text
        FROM HEALTH_PREDICTION hp
        LEFT JOIN SUGGESTION s ON hp.predicted_issue = s.predicted_issue
        WHERE hp.P_SSN = $pSSN
    ");
    $list = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    respond($list);
}

// POST → nutritionist/admin adds a suggestion for an issue
if ($method === 'POST') {
    $data    = getInput();
    $issue   = $db->real_escape_string($data['predicted_issue'] ?? '');
    $text    = $db->real_escape_string($data['suggestion_text'] ?? '');
    if (!$issue || !$text) respondError('predicted_issue and suggestion_text required.');

    // Use next available ID
    $maxRes = $db->query("SELECT COALESCE(MAX(suggestion_id),0)+1 AS nid FROM SUGGESTION");
    $nid    = $maxRes->fetch_assoc()['nid'];

    $db->query("INSERT INTO SUGGESTION (suggestion_id, predicted_issue, suggestion_text)
                VALUES ($nid, '$issue', '$text')");
    if ($db->errno) respondError('Failed to save suggestion.');
    respond(['message' => 'Suggestion saved.'], 201);
}

respondError('Method not allowed.', 405);
