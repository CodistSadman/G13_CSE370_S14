<?php
require_once '../config/helpers.php';
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$pSSN   = (int)($user['SSN'] ?? $user['P_SSN'] ?? 0);
$db     = getDB();

// Ensure join request table exists
$db->query("CREATE TABLE IF NOT EXISTS GROUP_JOIN_REQUEST (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    P_SSN INT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_request (group_id, P_SSN)
)");

// ── GET ──────────────────────────────────────────────────
if ($method === 'GET') {
    $action  = $_GET['action'] ?? '';
    $groupId = (int)($_GET['group_id'] ?? 0);

    // All groups list with current user's membership/request status
    if ($action === 'all') {
        $result = $db->query("
            SELECT 
                ug.*,
                gm.status AS member_status,
                gjr.status AS request_status
            FROM user_group ug
            LEFT JOIN group_member gm 
                ON ug.group_id = gm.group_id 
               AND gm.P_SSN = $pSSN
            LEFT JOIN GROUP_JOIN_REQUEST gjr 
                ON ug.group_id = gjr.group_id 
               AND gjr.P_SSN = $pSSN
            ORDER BY ug.group_name
        ");

        $list = [];

        while ($row = $result->fetch_assoc()) {
            if ($row['member_status'] === 'accepted') {
                $row['join_status'] = 'accepted';
            } elseif ($row['request_status'] === 'pending') {
                $row['join_status'] = 'pending';
            } else {
                $row['join_status'] = 'none';
            }

            $list[] = $row;
        }

        respond($list);
    }

    // Pending join requests for groups I admin
    if ($action === 'requests') {
        $result = $db->query("
            SELECT r.request_id, r.group_id, r.P_SSN, r.status,
                   ug.group_name, u.name AS patient_name
            FROM GROUP_JOIN_REQUEST r
            JOIN user_group ug ON r.group_id = ug.group_id
            JOIN user u ON r.P_SSN = u.SSN
            WHERE ug.owner_ssn = $pSSN AND r.status = 'pending'
            ORDER BY r.requested_at DESC
        ");

        $list = [];
        while ($row = $result->fetch_assoc()) $list[] = $row;
        respond($list);
    }

    // Leaderboard — only for accepted group members
    if ($action === 'leaderboard') {
        if (!$groupId) respondError('group_id required.');

        $check = $db->query("
            SELECT 1 
            FROM group_member 
            WHERE group_id = $groupId 
              AND P_SSN = $pSSN 
              AND status = 'accepted'
            LIMIT 1
        ");

        if (!$check || $check->num_rows === 0) respondError('Access denied.', 403);

        $result = $db->query("
            SELECT u.name,
                   COALESCE(ROUND(AVG(h.step_count), 0), 0)  AS avg_steps,
                   COALESCE(ROUND(AVG(h.calories), 0), 0)     AS avg_calories,
                   COALESCE(ROUND(AVG(h.sleep_hours), 1), 0)  AS avg_sleep
            FROM group_member gm
            JOIN user u ON gm.P_SSN = u.SSN
            LEFT JOIN habit h ON h.P_SSN = gm.P_SSN
            WHERE gm.group_id = $groupId
              AND gm.status = 'accepted'
            GROUP BY gm.P_SSN, u.name
            ORDER BY avg_steps DESC
        ");

        $board = [];
        while ($row = $result->fetch_assoc()) $board[] = $row;
        respond($board);
    }

    // My groups — only accepted groups
    $result = $db->query("
        SELECT ug.group_id, ug.group_name, ug.access,
               gm.role, gm.status,
               CASE WHEN ug.owner_ssn = $pSSN THEN 1 ELSE 0 END AS is_admin
        FROM group_member gm
        JOIN user_group ug ON gm.group_id = ug.group_id
        WHERE gm.P_SSN = $pSSN
          AND gm.status = 'accepted'
        ORDER BY ug.group_name
    ");

    $list = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    respond($list);
}

// ── POST ─────────────────────────────────────────────────
if ($method === 'POST') {
    $action = $_GET['action'] ?? '';
    $data   = getInput();

    // Create group — creator becomes admin
    if ($action === 'create') {
        $name   = $db->real_escape_string($data['group_name'] ?? '');
        $access = $db->real_escape_string($data['access'] ?? 'public');

        if (!$name) respondError('group_name required.');

        $maxRes  = $db->query("SELECT COALESCE(MAX(group_id), 0) + 1 AS nid FROM user_group");
        $groupId = (int)$maxRes->fetch_assoc()['nid'];

        $db->query("INSERT INTO user_group (group_id, group_name, access, owner_ssn)
                    VALUES ($groupId, '$name', '$access', $pSSN)");

        if ($db->errno) respondError('Failed to create group.');

        $db->query("INSERT IGNORE INTO group_member (group_id, P_SSN, role, status) 
                    VALUES ($groupId, $pSSN, 'admin', 'accepted')");

        respond(['message' => 'Group created.', 'group_id' => $groupId], 201);
    }

    // Join group — public: direct join, private: send request
    if ($action === 'join') {
        $groupId = (int)($data['group_id'] ?? 0);

        if (!$groupId) respondError('group_id required.');

        $res = $db->query("SELECT access, owner_ssn FROM user_group WHERE group_id = $groupId LIMIT 1");

        if (!$res || $res->num_rows === 0) respondError('Group not found.');

        $group = $res->fetch_assoc();

        // Check already accepted member
        $already = $db->query("
            SELECT 1 
            FROM group_member 
            WHERE group_id = $groupId 
              AND P_SSN = $pSSN 
              AND status = 'accepted'
            LIMIT 1
        ");

        if ($already && $already->num_rows > 0) {
            respondError('You are already a member.');
        }

        // Private group: create or renew pending request
        if ($group['access'] === 'private') {
            $db->query("
                INSERT INTO GROUP_JOIN_REQUEST (group_id, P_SSN, status)
                VALUES ($groupId, $pSSN, 'pending')
                ON DUPLICATE KEY UPDATE
                    status = 'pending',
                    requested_at = CURRENT_TIMESTAMP
            ");

            if ($db->errno) respondError('Request failed.');

            respond(['message' => 'Join request sent. Waiting for admin approval.']);
        }

        // Public group: direct join
        $db->query("INSERT IGNORE INTO group_member (group_id, P_SSN, role, status) 
                    VALUES ($groupId, $pSSN, 'member', 'accepted')");

        if ($db->errno) respondError('Failed to join group.');

        respond(['message' => 'Joined group.']);
    }

    // Approve join request — only group admin
    if ($action === 'approve_request') {
        $requestId = (int)($data['request_id'] ?? 0);

        if (!$requestId) respondError('request_id required.');

        $res = $db->query("
            SELECT r.group_id, r.P_SSN
            FROM GROUP_JOIN_REQUEST r
            JOIN user_group ug ON r.group_id = ug.group_id
            WHERE r.request_id = $requestId
              AND ug.owner_ssn = $pSSN
              AND r.status = 'pending'
            LIMIT 1
        ");

        if (!$res || $res->num_rows === 0) respondError('Request not found or not authorised.');

        $req       = $res->fetch_assoc();
        $gid       = (int)$req['group_id'];
        $memberSSN = (int)$req['P_SSN'];

        $db->query("INSERT IGNORE INTO group_member (group_id, P_SSN, role, status) 
                    VALUES ($gid, $memberSSN, 'member', 'accepted')");

        $db->query("UPDATE GROUP_JOIN_REQUEST SET status = 'approved' WHERE request_id = $requestId");

        respond(['message' => 'Request approved.']);
    }

    // Reject join request — only group admin
    if ($action === 'reject_request') {
        $requestId = (int)($data['request_id'] ?? 0);

        if (!$requestId) respondError('request_id required.');

        $db->query("
            UPDATE GROUP_JOIN_REQUEST r
            JOIN user_group ug ON r.group_id = ug.group_id
            SET r.status = 'rejected'
            WHERE r.request_id = $requestId
              AND ug.owner_ssn = $pSSN
              AND r.status = 'pending'
        ");

        respond(['message' => 'Request rejected.']);
    }
}

// ── DELETE ────────────────────────────────────────────────
if ($method === 'DELETE') {
    $groupId = (int)($_GET['group_id'] ?? 0);
    $action  = $_GET['action'] ?? '';

    if (!$groupId) respondError('group_id required.');

    // Delete group — admin only
    if ($action === 'delete') {
        $res = $db->query("SELECT owner_ssn FROM user_group WHERE group_id = $groupId LIMIT 1");

        if (!$res || $res->num_rows === 0) respondError('Group not found.');

        $g = $res->fetch_assoc();

        if ((int)$g['owner_ssn'] !== $pSSN) {
            respondError('Only the group admin can delete this group.', 403);
        }

        $db->query("DELETE FROM GROUP_JOIN_REQUEST WHERE group_id = $groupId");
        $db->query("DELETE FROM group_member WHERE group_id = $groupId");
        $db->query("DELETE FROM user_group WHERE group_id = $groupId");

        respond(['message' => 'Group deleted.']);
    }

    // Leave group — non-admins only
    $res = $db->query("SELECT owner_ssn FROM user_group WHERE group_id = $groupId LIMIT 1");

    if ($res && $res->num_rows > 0) {
        $g = $res->fetch_assoc();

        if ((int)$g['owner_ssn'] === $pSSN) {
            respondError('You are the admin. Delete the group instead of leaving.');
        }
    }

    $db->query("DELETE FROM group_member WHERE group_id = $groupId AND P_SSN = $pSSN");
    respond(['message' => 'Left group.']);
}

respondError('Method not allowed.', 405);