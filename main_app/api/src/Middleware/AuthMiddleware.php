<?php
namespace Middleware;

use Core\Response;

class AuthMiddleware {
    public static function handle() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            // Support API Key in Header or Query String (for n8n)
            $headers = array_change_key_case(getallheaders(), CASE_LOWER);
            $apiKey = $headers['x-erp-api-key'] ?? ($_GET['api_key'] ?? '');
            
            if ($apiKey === 'erp_n8n_secret_88d4f20') {
                return; 
            }
            Response::error('Unauthorized', 401);
        }

        // For admin routes
        if (strpos($_SERVER['REQUEST_URI'], '/admin') !== false || strpos($_SERVER['REQUEST_URI'], '/orders') !== false) {
            if (($_SESSION['role'] ?? '') !== 'admin') {
                Response::error('Forbidden: Admin access required', 403);
            }
        }
    }
}
