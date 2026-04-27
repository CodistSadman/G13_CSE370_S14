<?php
// GET /groups/getGroups?access=public
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError("Method not allowed", 405);

$jwt = requireAuth();
$db  = (new Database())->getConnection();

$access = $_GET['access'] ?? null;

if ($access === 'public') {
    $stmt = $db->query("SELECT group_id, group_name, access FROM `GROUP` WHERE access='public' ORDER BY group_name");
} else {
    // Return public groups + groups the user is a member of
    $stmt = $db->prepare(
        "SELECT g.group_id, g.group_name, g.access
         FROM `GROUP` g
         WHERE g.access = 'public'
         OR g.group_id IN (SELECT group_id FROM GROUP_MEMBER WHERE P_SSN = ?)
         ORDER BY g.group_name"
    );
    $stmt->execute([$jwt['ssn']]);
}

sendResponse(["data" => $stmt->fetchAll()]);
