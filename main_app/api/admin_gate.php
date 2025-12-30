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

// 3. API Key Auth (for n8n)
if (!$is_authorized) {
    $apiKeyHeader = $headers['X-ERP-API-KEY'] ?? $headers['x-erp-api-key'] ?? '';
    if ($apiKeyHeader) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'erp_api_key'");
        $stmt->execute();
        $storedKey = $stmt->fetchColumn();
        if ($storedKey && $apiKeyHeader === $storedKey) {
            $is_authorized = true;
            $user_role = 'admin';
        }
    }
}

// 3. Final Gate
if (!$is_authorized || $user_role !== 'admin') {
    sendResponse(['success' => false, 'error' => 'Forbidden: Admin access only'], 403);
}
