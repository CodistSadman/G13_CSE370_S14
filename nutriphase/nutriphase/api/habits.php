<?php
require_once '../config/db.php';
require_once '../config/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$pSSN   = (int)$user['SSN'];
$db     = getDB();

// GET /api/habits.php - get all habits for logged-in patient
if ($method === 'GET') {
    $result = $db->query("SELECT * FROM habit WHERE P_SSN = $pSSN ORDER BY date DESC");
    $habits = [];
    while ($row = $result->fetch_assoc()) $habits[] = $row;
    respond($habits);
}

// POST /api/habits.php - log a new habit
if ($method === 'POST') {
    $data     = getInput();
    $date     = $db->real_escape_string($data['date'] ?? date('Y-m-d'));
    $calories = (int)($data['calories'] ?? 0);
    $sleep    = (int)($data['sleep_hours'] ?? 0);

    $db->query("INSERT INTO habit (P_SSN, date, calories, sleep_hours)
                VALUES ($pSSN, '$date', $calories, $sleep)");
    if ($db->errno) respondError('Failed to log habit.');

    respond(['message' => 'Habit logged.', 'record_id' => $db->insert_id], 201);
}

// DELETE /api/habits.php?id=X
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    $db->query("DELETE FROM habit WHERE record_id = $id AND P_SSN = $pSSN");
    respond(['message' => 'Habit deleted.']);
}

respondError('Method not allowed.', 405);
