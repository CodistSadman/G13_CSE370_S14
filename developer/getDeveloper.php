<?php
// GET /developer/getDeveloper?developer_id=...
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError("Method not allowed", 405);

$jwt = requireAuth();
$db  = (new Database())->getConnection();

$devId = $_GET['developer_id'] ?? null;

// Only admin/developer can see other profiles; others can only see their own
if ($jwt['role'] !== 'developer' && !$devId) sendError("Forbidden", 403);

if ($devId) {
    $stmt = $db->prepare("SELECT DEVELOPER_ID, name, email, CONCAT('****', RIGHT(bank_account,4)) AS bank_account FROM DEVELOPER WHERE DEVELOPER_ID=?");
    $stmt->execute([$devId]);
} else {
    // Developer viewing own full record — still mask bank account
    $stmt = $db->prepare(
        "SELECT d.DEVELOPER_ID, d.name, d.email, CONCAT('****', RIGHT(d.bank_account,4)) AS bank_account
         FROM DEVELOPER d
         JOIN USER u ON d.email = u.email
         WHERE u.SSN = ?"
    );
    $stmt->execute([$jwt['ssn']]);
}

$dev = $stmt->fetch();
if (!$dev) sendError("Developer not found", 404);

sendResponse($dev);
