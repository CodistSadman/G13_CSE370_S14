<?php
require_once '../config/db.php';
require_once '../config/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
requireAuth();
$db = getDB();

if ($method === 'GET') {
    $result = $db->query("SELECT developer_id, name, email FROM DEVELOPER");
    $list = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    respond($list);
}
respondError('Method not allowed.', 405);
