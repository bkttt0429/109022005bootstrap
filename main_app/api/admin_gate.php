<?php
require_once 'jwt_utils.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_authorized = false;
$user_role = null;

// 1. Check for JWT 
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
    $decoded = JWT::decode($token);
    if ($decoded && isset($decoded['role'])) {
        $is_authorized = true;
        // Sync to legacy session if needed by other non-API parts
        // $_SESSION['user_id'] = $decoded['user_id'];
        $user_role = $decoded['role'];
    }
}

// 2. Fallback to Session (Legacy support)
if (!$is_authorized && isset($_SESSION['user_role'])) {
    $is_authorized = true;
    $user_role = $_SESSION['user_role'];
}

// 3. Final Gate
if (!$is_authorized || $user_role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden: Admin access only']);
    exit;
}
?>
