<?php
/**
 * API Bootstrap File
 * Centralizes CORS, Session, Database, and Common utilities.
 */

// Simple .env loader
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!$line || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Strip UTF-8 BOM if present
        if (strpos($name, "\xef\xbb\xbf") === 0) {
            $name = substr($name, 3);
        }
        
        // Always override for local development .env consistency
        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
loadEnv(__DIR__ . '/.env');

// Fallback for getallheaders if not using Apache module
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// 1. CORS Configuration
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400");
} else {
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-ERP-API-KEY");

// Handle Preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Session Start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Set Response Type
header('Content-Type: application/json');

// 4. Load Core Files
require_once 'db.php';
require_once 'jwt_utils.php';

/**
 * Utility: Send JSON response and exit
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Utility: Get JSON input
 */
function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}
