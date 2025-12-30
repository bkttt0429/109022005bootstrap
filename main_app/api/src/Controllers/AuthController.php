<?php
namespace Controllers;

use Core\Response;
use Services\AuthService;

class AuthController {
    private $service;

    public function __construct() {
        $this->service = new AuthService(getDB());
    }

    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        $user = $this->service->login($email, $password);
        if ($user) {
            Response::json([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            Response::error('Invalid credentials', 401);
        }
    }

    public function logout() {
        $this->service->logout();
        Response::json(['success' => true]);
    }

    public function me() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['user_id'])) {
            Response::json([
                'authenticated' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'name' => $_SESSION['name'],
                    'role' => $_SESSION['role']
                ]
            ]);
        } else {
            Response::json(['authenticated' => false], 401);
        }
    }
}
