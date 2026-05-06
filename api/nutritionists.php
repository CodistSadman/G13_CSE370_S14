<?php
ob_start(); // capture any accidental output before JSON
require_once '../config/helpers.php';
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$db     = getDB();

// GET
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $result = $db->query("
            SELECT n.N_SSN, u.name, u.email, u.gender, n.bio,
                   n.experience_years, n.qualification, n.subscription_fee
            FROM nutritionist n
            JOIN user u ON n.N_SSN = u.SSN
        ");
        $list = [];
        while ($row = $result->fetch_assoc()) $list[] = $row;
        ob_end_clean();
        respond($list);
    }

    if ($action === 'my_subscriptions') {
        $pSSN   = (int)$user['SSN'];
        $result = $db->query("
            SELECT s.*, u.name, n.qualification, n.subscription_fee
            FROM subscribe s
            JOIN nutritionist n ON s.N_SSN = n.N_SSN
            JOIN user u ON n.N_SSN = u.SSN
            WHERE s.P_SSN = $pSSN
        ");
        $subs = [];
        while ($row = $result->fetch_assoc()) $subs[] = $row;
        ob_end_clean();
        respond($subs);
    }
    if ($action === 'my_patients') {
    $nSSN   = (int)$user['SSN'];
    $result = $db->query("
        SELECT u.SSN AS P_SSN, u.name, u.email, u.gender,
               p.goal, s.start_date, s.end_date,
               (SELECT COUNT(*) FROM habit WHERE P_SSN = u.SSN) AS habit_count,
               (SELECT date FROM habit WHERE P_SSN = u.SSN ORDER BY date DESC LIMIT 1) AS last_habit
        FROM subscribe s
        JOIN user u ON s.P_SSN = u.SSN
        JOIN patient p ON s.P_SSN = p.P_SSN
        WHERE s.N_SSN = $nSSN
        ORDER BY s.start_date DESC
    ");
    $patients = [];
    while ($row = $result->fetch_assoc()) $patients[] = $row;
    ob_end_clean();
    respond($patients);
}

    ob_end_clean();
    respond([]);
}

// POST
if ($method === 'POST') {
    $action = $_GET['action'] ?? '';
    $data   = getInput();

    if ($action === 'subscribe') {
        $pSSN      = (int)$user['SSN'];
        $nSSN      = (int)($data['N_SSN']     ?? 0);
        $startDate = $db->real_escape_string($data['start_date'] ?? date('Y-m-d'));
        $endDate   = $db->real_escape_string($data['end_date']   ?? '');

        if (!$nSSN) { ob_end_clean(); respondError('N_SSN is required.'); }

        $db->query("INSERT INTO subscribe (P_SSN, N_SSN, start_date, end_date)
                    VALUES ($pSSN, $nSSN, '$startDate', '$endDate')
                    ON DUPLICATE KEY UPDATE start_date='$startDate', end_date='$endDate'");
        if ($db->errno) { ob_end_clean(); respondError('Subscription failed: ' . $db->error); }

        // Record payment silently — never allowed to crash subscribe
        try { recordPayment($db, $nSSN, $pSSN); } catch (Exception $e) {}

       // Record payment
$paid = recordPayment($db, $nSSN, $pSSN);

ob_end_clean();
respond(['message' => 'Subscribed successfully.', 'payment_recorded' => $paid]);
    }

    ob_end_clean();
    respondError('Unknown action.', 400);
}

// DELETE
if ($method === 'DELETE') {
    $action = $_GET['action'] ?? '';
    if ($action === 'unsubscribe') {
        $pSSN = (int)$user['SSN'];
        $nSSN = (int)($_GET['N_SSN'] ?? 0);
        $db->query("DELETE FROM subscribe WHERE P_SSN = $pSSN AND N_SSN = $nSSN");
        ob_end_clean();
        respond(['message' => 'Unsubscribed.']);
    }
}

ob_end_clean();
respondError('Method not allowed.', 405);

// ── Helper: record payment (silent — never fatal) ─────────
function recordPayment($db, $nSSN, $pSSN) {
    // Get nutritionist's fee
    $r = $db->query("SELECT subscription_fee FROM nutritionist WHERE N_SSN = $nSSN");
    if (!$r || $r->num_rows === 0) return false;

    $fee    = (int)($r->fetch_assoc()['subscription_fee'] ?? 500);
    $devCut = (int)round($fee * 0.25);

    // Ensure P_SSN column exists in payment table
    $col = $db->query("SHOW COLUMNS FROM payment LIKE 'P_SSN'");
    if ($col && $col->num_rows === 0) {
        $db->query("ALTER TABLE payment ADD COLUMN P_SSN INT(11) DEFAULT NULL");
    }

    // Ensure developer record id=1 exists (required by FK constraint)
    $dev = $db->query("SELECT developer_id FROM developer WHERE developer_id = 1");
    if ($dev && $dev->num_rows === 0) {
        $db->query("INSERT INTO developer (developer_id, name, email, bank_account)
                    VALUES (1, 'NutriPhase Platform', 'platform@nutriphase.com', 'PLATFORM-001')");
        if ($db->errno) return false;
    }

    // Next transaction ID
    $t = $db->query("SELECT COALESCE(MAX(transaction_id), 0) + 1 AS nid FROM payment");
    if (!$t) return false;
    $tid = (int)$t->fetch_assoc()['nid'];

    $db->query("INSERT INTO payment (transaction_id, amount, developer_id, N_SSN, P_SSN)
                VALUES ($tid, $devCut, 1, $nSSN, $pSSN)");

    return $db->errno === 0;
}