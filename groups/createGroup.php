<?php
// POST /groups/createGroup
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError("Method not allowed", 405);

$jwt = requireAuth();
requireRole($jwt, 'patient');

$db   = (new Database())->getConnection();
$body = getRequestBody();
requireFields($body, ['group_name','access']);

if (!in_array($body['access'], ['public','private'])) sendError("access must be 'public' or 'private'");

$db->beginTransaction();
try {
    $stmt = $db->prepare("INSERT INTO `GROUP` (group_name, access) VALUES (?,?)");
    $stmt->execute([trim($body['group_name']), $body['access']]);
    $groupId = $db->lastInsertId();

    // Auto-add creator as first member
    $stmt = $db->prepare("INSERT INTO GROUP_MEMBER (group_id, P_SSN) VALUES (?,?)");
    $stmt->execute([$groupId, $jwt['ssn']]);

    $db->commit();
    sendResponse(["message" => "Group created", "group_id" => $groupId], 201);
} catch (Exception $e) {
    $db->rollBack();
    sendError("Failed: " . $e->getMessage(), 500);
}
