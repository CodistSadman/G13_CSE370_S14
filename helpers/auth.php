<?php
define('JWT_SECRET', 'CHANGE_THIS_TO_A_LONG_RANDOM_SECRET_KEY');
define('JWT_EXPIRY', 86400); // 24 hours

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}

function generateJWT($payload) {
    $header  = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRY;
    $payload = base64url_encode(json_encode($payload));
    $sig     = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$sig";
}

function verifyJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $payload, $sig] = $parts;
    $expected = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    if (!hash_equals($expected, $sig)) return null;
    $data = json_decode(base64url_decode($payload), true);
    if (!$data || $data['exp'] < time()) return null;
    return $data;
}

function requireAuth() {
    $headers = getallheaders();
    $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
        sendError("Unauthorized – missing token", 401);
    }
    $payload = verifyJWT(trim($m[1]));
    if (!$payload) sendError("Unauthorized – invalid or expired token", 401);
    return $payload; // ['ssn' => ..., 'role' => 'patient'|'nutritionist'|'developer']
}

function requireRole($payload, $role) {
    if ($payload['role'] !== $role) {
        sendError("Forbidden – requires role: $role", 403);
    }
}
