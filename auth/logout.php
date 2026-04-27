<?php
// POST /auth/logout
// Since JWTs are stateless, logout is handled client-side by deleting the token.
// This endpoint is a convenience confirmation.
require_once '../helpers/response.php';
require_once '../helpers/auth.php';

setCORSHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError("Method not allowed", 405);

requireAuth(); // Validates token is still good
sendResponse(["message" => "Logged out. Delete the token on your client."]);
