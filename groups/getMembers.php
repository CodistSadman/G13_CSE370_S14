<?php
// GET /groups/getMembers?group_id=1
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError("Method not allowed", 405);

$jwt = requireAuth();
$db  = (new Database())->getConnection();

if (empty($_GET['group_id'])) sendError("Provide group_id", 422);
$groupId = (int)$_GET['group_id'];

// For private groups, requester must be a member
$stmt = $db->prepare("SELECT access FROM `GROUP` WHERE group_id=?");
$stmt->execute([$groupId]);
$group = $stmt->fetch();
if (!$group) sendError("Group not found", 404);

if ($group['access'] === 'private') {
    $stmt = $db->prepare("SELECT P_SSN FROM GROUP_MEMBER WHERE group_id=? AND P_SSN=?");
    $stmt->execute([$groupId, $jwt['ssn']]);
    if (!$stmt->fetch()) sendError("Access denied – private group", 403);
}

$stmt = $db->prepare(
    "SELECT u.SSN, u.name, u.gender
     FROM GROUP_MEMBER gm
     JOIN USER u ON gm.P_SSN = u.SSN
     WHERE gm.group_id = ?
     ORDER BY u.name"
);
$stmt->execute([$groupId]);
sendResponse(["group_id" => $groupId, "members" => $stmt->fetchAll()]);
