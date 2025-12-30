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
    // Case-insensitive header lookup
    $apiKeyHeader = '';
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'x-erp-api-key') {
            $apiKeyHeader = $value;
            break;
        }
    }

    // Fallback: Direct check in $_SERVER (useful for some Apache/Nginx configs)
    if (!$apiKeyHeader && isset($_SERVER['HTTP_X_ERP_API_KEY'])) {
        $apiKeyHeader = $_SERVER['HTTP_X_ERP_API_KEY'];
    }

    // Fallback 2: Query Parameter (URL) - Most robust for quirky proxies
    if (!$apiKeyHeader && isset($_GET['api_key'])) {
        $apiKeyHeader = $_GET['api_key'];
    }

    if ($apiKeyHeader) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'erp_api_key'");
        $stmt->execute();
        $storedKey = $stmt->fetchColumn();
        
        // Debugging: Success or Fail
        if ($storedKey && trim($apiKeyHeader) === trim($storedKey)) {
            file_put_contents('debug_auth_gate.log', date('Y-m-d H:i:s') . " - Auth SUCCESS. Key matched.\n", FILE_APPEND);
            $is_authorized = true;
            $user_role = 'admin';
        } else {
             file_put_contents('debug_auth_gate.log', date('Y-m-d H:i:s') . " - Auth FAILED. Mismatch. Rec: [$apiKeyHeader] vs Stored: [$storedKey]\n", FILE_APPEND);
        }
    } else {
        // Debugging: Log ALL headers to see what is actually arriving
        $headerDump = print_r($headers, true);
        $serverDump = print_r($_SERVER, true);
        file_put_contents('debug_auth_gate.log', date('Y-m-d H:i:s') . " - NO API KEY FOUND.\nHeaders: $headerDump\nSERVER: $serverDump\n", FILE_APPEND);
    }
}

// 3. Final Gate
if (!$is_authorized || $user_role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden: Admin access only']);
    exit;
}
