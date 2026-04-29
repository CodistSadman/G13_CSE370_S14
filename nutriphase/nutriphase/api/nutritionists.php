<?php
require_once '../config/db.php';
require_once '../config/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$db     = getDB();

// GET /api/nutritionists.php - list all nutritionists
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $result = $db->query("
            SELECT n.N_SSN, u.name, u.email, u.gender, n.bio, n.experience_years, n.qualification
            FROM nutritionist n
            JOIN user u ON n.N_SSN = u.SSN
        ");
        $list = [];
        while ($row = $result->fetch_assoc()) $list[] = $row;
        respond($list);
    }

    if ($action === 'my_subscriptions') {
        $pSSN   = (int)$user['SSN'];
        $result = $db->query("
            SELECT s.*, u.name, n.qualification
            FROM subscribe s
            JOIN nutritionist n ON s.N_SSN = n.N_SSN
            JOIN user u ON n.N_SSN = u.SSN
            WHERE s.P_SSN = $pSSN
        ");
        $subs = [];
        while ($row = $result->fetch_assoc()) $subs[] = $row;
        respond($subs);
    }
}

// POST /api/nutritionists.php?action=subscribe
if ($method === 'POST') {
    $action = $_GET['action'] ?? '';
    $data   = getInput();

    if ($action === 'subscribe') {
        $pSSN      = (int)$user['SSN'];
        $nSSN      = (int)($data['N_SSN'] ?? 0);
        $startDate = $db->real_escape_string($data['start_date'] ?? date('Y-m-d'));
        $endDate   = $db->real_escape_string($data['end_date'] ?? '');

        $db->query("INSERT INTO subscribe (P_SSN, N_SSN, start_date, end_date)
                    VALUES ($pSSN, $nSSN, '$startDate', '$endDate')
                    ON DUPLICATE KEY UPDATE start_date='$startDate', end_date='$endDate'");
        if ($db->errno) respondError('Subscription failed.');
        respond(['message' => 'Subscribed successfully.']);
    }
}

// DELETE /api/nutritionists.php?action=unsubscribe&N_SSN=X
if ($method === 'DELETE') {
    $action = $_GET['action'] ?? '';
    if ($action === 'unsubscribe') {
        $pSSN = (int)$user['SSN'];
        $nSSN = (int)($_GET['N_SSN'] ?? 0);
        $db->query("DELETE FROM subscribe WHERE P_SSN = $pSSN AND N_SSN = $nSSN");
        respond(['message' => 'Unsubscribed.']);
    }
}

respondError('Method not allowed.', 405);
