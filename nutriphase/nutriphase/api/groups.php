<?php
require_once '../config/db.php';
require_once '../config/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$pSSN   = (int)$user['SSN'];
$db     = getDB();

// GET ?action=all  → all groups
// GET              → groups the patient belongs to
if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'all') {
        $result = $db->query("SELECT * FROM USER_GROUP ORDER BY group_name");
        $list = [];
        while ($row = $result->fetch_assoc()) $list[] = $row;
        respond($list);
    }

    $result = $db->query("
        SELECT ug.group_id, ug.group_name, ug.access
        FROM GROUP_MEMBER gm
        JOIN USER_GROUP ug ON gm.group_id = ug.group_id
        WHERE gm.P_SSN = $pSSN
    ");
    $list = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    respond($list);
}

// POST ?action=create → create a new group
// POST ?action=join   → join a group
if ($method === 'POST') {
    $action = $_GET['action'] ?? '';
    $data   = getInput();

    if ($action === 'create') {
        $name   = $db->real_escape_string($data['group_name'] ?? '');
        $access = $db->real_escape_string($data['access'] ?? 'public');
        if (!$name) respondError('group_name required.');

        $maxRes  = $db->query("SELECT COALESCE(MAX(group_id),0)+1 AS nid FROM USER_GROUP");
        $groupId = $maxRes->fetch_assoc()['nid'];

        $db->query("INSERT INTO USER_GROUP (group_id, group_name, access) VALUES ($groupId, '$name', '$access')");
        if ($db->errno) respondError('Failed to create group.');

        // Creator auto-joins
        $db->query("INSERT IGNORE INTO GROUP_MEMBER (group_id, P_SSN) VALUES ($groupId, $pSSN)");
        respond(['message' => 'Group created.', 'group_id' => $groupId], 201);
    }

    if ($action === 'join') {
        $groupId = (int)($data['group_id'] ?? 0);
        if (!$groupId) respondError('group_id required.');
        $db->query("INSERT IGNORE INTO GROUP_MEMBER (group_id, P_SSN) VALUES ($groupId, $pSSN)");
        if ($db->errno) respondError('Failed to join group.');
        respond(['message' => 'Joined group.']);
    }
}

// DELETE ?group_id=X → leave a group
if ($method === 'DELETE') {
    $groupId = (int)($_GET['group_id'] ?? 0);
    $db->query("DELETE FROM GROUP_MEMBER WHERE group_id = $groupId AND P_SSN = $pSSN");
    respond(['message' => 'Left group.']);
}

respondError('Method not allowed.', 405);
