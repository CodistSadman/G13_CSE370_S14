<?php
// POST /groups/joinGroup
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'patient');

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['group_id']);

$stmt = $db->prepare("SELECT access FROM `GROUP` WHERE group_id=?");
$stmt->execute([$body['group_id']]);
$group = $stmt->fetch();
if (!$group) sendError("Group not found", 404);

if ($group['access'] === 'private') sendError("This group is private. You need an invite.", 403);

$stmt = $db->prepare("SELECT P_SSN FROM GROUP_MEMBER WHERE group_id=? AND P_SSN=?");
$stmt->execute([$body['group_id'], $jwt['ssn']]);
if ($stmt->fetch()) sendError("Already a member", 409);

$stmt = $db->prepare("INSERT INTO GROUP_MEMBER (group_id, P_SSN) VALUES (?,?)");
$stmt->execute([$body['group_id'], $jwt['ssn']]);

sendResponse(["message" => "Joined group"], 201);
