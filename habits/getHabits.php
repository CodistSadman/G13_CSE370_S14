<?php
// GET /habits/getHabits?from=YYYY-MM-DD&to=YYYY-MM-DD&page=1&limit=20
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError("Method not allowed", 405);

$jwt  = requireAuth();
$db   = (new Database())->getConnection();
$pSSN = $jwt['ssn'];

// Nutritionists can pass ?patient_ssn=... if subscribed
if ($jwt['role'] === 'nutritionist') {
    if (empty($_GET['patient_ssn'])) sendError("Provide patient_ssn as query param", 422);
    $pSSN   = $_GET['patient_ssn'];
    $nSSN   = $jwt['ssn'];
    // Verify subscription
    $stmt = $db->prepare("SELECT P_SSN FROM SUBSCRIBE WHERE P_SSN=? AND N_SSN=? AND end_date >= CURDATE()");
    $stmt->execute([$pSSN, $nSSN]);
    if (!$stmt->fetch()) sendError("Patient is not subscribed to you", 403);
}

$from  = $_GET['from']  ?? '2000-01-01';
$to    = $_GET['to']    ?? date('Y-m-d');
$page  = max(1, (int)($_GET['page']  ?? 1));
$limit = min(100, (int)($_GET['limit'] ?? 20));
$offset = ($page - 1) * $limit;

$stmt = $db->prepare(
    "SELECT record_id, date, calories, sleep_hours
     FROM HABIT
     WHERE P_SSN = ? AND date BETWEEN ? AND ?
     ORDER BY date DESC
     LIMIT ? OFFSET ?"
);
$stmt->execute([$pSSN, $from, $to, $limit, $offset]);
$records = $stmt->fetchAll();

// Total count for pagination
$count = $db->prepare("SELECT COUNT(*) FROM HABIT WHERE P_SSN=? AND date BETWEEN ? AND ?");
$count->execute([$pSSN, $from, $to]);
$total = (int)$count->fetchColumn();

sendResponse([
    "data"       => $records,
    "total"      => $total,
    "page"       => $page,
    "limit"      => $limit,
    "total_pages"=> ceil($total / $limit)
]);
