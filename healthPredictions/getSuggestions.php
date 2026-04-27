<?php
// GET /healthPredictions/getSuggestions?predicted_issue=diabetes
require_once '../config/database.php';
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError("Method not allowed", 405);

requireAuth();
$db = (new Database())->getConnection();

if (!empty($_GET['predicted_issue'])) {
    $stmt = $db->prepare("SELECT * FROM SUGGESTION WHERE predicted_issue = ?");
    $stmt->execute([$_GET['predicted_issue']]);
    $result = $stmt->fetch();
    if (!$result) sendError("No suggestion found for that issue", 404);
    sendResponse($result);
}

// Return all suggestions if no filter
$stmt = $db->query("SELECT * FROM SUGGESTION ORDER BY predicted_issue");
sendResponse(["data" => $stmt->fetchAll()]);
