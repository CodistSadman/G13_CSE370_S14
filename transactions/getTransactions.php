<?php
// GET /transactions/getTransactions
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError("Method not allowed", 405);

$jwt = requireAuth();
$db  = (new Database())->getConnection();

if ($jwt['role'] === 'nutritionist') {
    $stmt = $db->prepare(
        "SELECT t.TRANSACTION_ID, t.amount, t.DEVELOPER_ID, d.name AS developer_name
         FROM TRANSACTION t
         JOIN DEVELOPER d ON t.DEVELOPER_ID = d.DEVELOPER_ID
         WHERE t.N_SSN = ?
         ORDER BY t.TRANSACTION_ID DESC"
    );
    $stmt->execute([$jwt['ssn']]);

} elseif ($jwt['role'] === 'developer') {
    // Match developer by SSN/email
    $stmt = $db->prepare("SELECT DEVELOPER_ID FROM DEVELOPER WHERE email=(SELECT email FROM USER WHERE SSN=?)");
    $stmt->execute([$jwt['ssn']]);
    $dev = $stmt->fetch();
    if (!$dev) sendError("Developer record not found", 404);

    $stmt = $db->prepare(
        "SELECT t.TRANSACTION_ID, t.amount, t.N_SSN, u.name AS nutritionist_name
         FROM TRANSACTION t
         JOIN NUTRIOTIONIST n ON t.N_SSN = n.N_SSN
         JOIN USER u           ON n.N_SSN = u.SSN
         WHERE t.DEVELOPER_ID = ?
         ORDER BY t.TRANSACTION_ID DESC"
    );
    $stmt->execute([$dev['DEVELOPER_ID']]);

} else {
    sendError("Forbidden", 403);
}

$rows  = $stmt->fetchAll();
$total = array_sum(array_column($rows, 'amount'));
sendResponse(["data" => $rows, "total_amount" => round($total, 2)]);
