<?php
namespace Middleware;

use Core\Response;

class AuthMiddleware {
    public static function handle() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
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
