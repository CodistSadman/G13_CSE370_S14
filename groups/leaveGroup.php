<?php
// DELETE /groups/leaveGroup
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'patient');

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['group_id']);

$stmt = $db->prepare("DELETE FROM GROUP_MEMBER WHERE group_id=? AND P_SSN=?");
$stmt->execute([$body['group_id'], $jwt['ssn']]);

if ($stmt->rowCount() === 0) sendError("You are not a member of this group", 404);
sendResponse(["message" => "Left group"]);
