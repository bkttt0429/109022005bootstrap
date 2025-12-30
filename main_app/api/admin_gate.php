<?php
require_once 'api_bootstrap.php';

$is_authorized = false;
$user_role = null;

// 1. Check for JWT 
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
    $decoded = JWT::decode($token);
    if ($decoded && isset($decoded['role'])) {
        $is_authorized = true;
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
    sendResponse(['success' => false, 'error' => 'Forbidden: Admin access only'], 403);
}
