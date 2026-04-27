<?php
// GET /social/getFriendRequests?type=incoming|outgoing|all
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError("Method not allowed", 405);

$jwt  = requireAuth();
requireRole($jwt, 'patient');
$db   = (new Database())->getConnection();
$type = $_GET['type'] ?? 'incoming';

if ($type === 'incoming') {
    $stmt = $db->prepare(
        "SELECT fr.request_id, fr.sender, u.name AS sender_name, fr.status
         FROM FRIEND_REQ fr
         JOIN USER u ON fr.sender = u.SSN
         WHERE fr.P_SSN = ? AND fr.status = 'pending'
         ORDER BY fr.request_id DESC"
    );
    $stmt->execute([$jwt['ssn']]);
} elseif ($type === 'outgoing') {
    $stmt = $db->prepare(
        "SELECT fr.request_id, fr.P_SSN AS receiver, u.name AS receiver_name, fr.status
         FROM FRIEND_REQ fr
         JOIN USER u ON fr.P_SSN = u.SSN
         WHERE fr.sender = ?
         ORDER BY fr.request_id DESC"
    );
    $stmt->execute([$jwt['ssn']]);
} else {
    // All (accepted friends)
    $stmt = $db->prepare(
        "SELECT fr.request_id,
                IF(fr.sender=?, fr.P_SSN, fr.sender) AS friend_ssn,
                u.name AS friend_name, fr.status
         FROM FRIEND_REQ fr
         JOIN USER u ON u.SSN = IF(fr.sender=?, fr.P_SSN, fr.sender)
         WHERE (fr.sender=? OR fr.P_SSN=?) AND fr.status='accepted'
         ORDER BY u.name"
    );
    $stmt->execute([$jwt['ssn'], $jwt['ssn'], $jwt['ssn'], $jwt['ssn']]);
}

sendResponse(["data" => $stmt->fetchAll()]);
