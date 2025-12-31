<?php
require_once 'api_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 1. Check Session first
    if (isset($_SESSION['user_id'])) {
        sendResponse([
            'loggedIn' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role'] ?? 'user'
            ]
        ]);
    } 
    
    // 2. If no session, try checking JWT from Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
        $payload = JWT::decode($token);
        
        if ($payload && isset($payload['user_id'])) {
            // Found valid token, sync back to session for this request
            sendResponse([
                'loggedIn' => true,
                'user' => [
                    'id' => $payload['user_id'],
                    'email' => $payload['email'],
                    'role' => $payload['role'] ?? 'user'
                ]
            ]);
        }
    }

    sendResponse(['loggedIn' => false]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Login
    $input = getJsonInput();
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    try {
        $pdo = getDB();
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            
            // Generate JWT
            $payload = [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'user'
            ];
            $jwt = JWT::encode($payload);
            
            // Don't send hash back
            unset($user['password_hash']);
            
            sendResponse(['success' => true, 'user' => $user, 'token' => $jwt]);
        } else {
            // Also check for the hardcoded admin (legacy support)
            if ($email === 'admin@example.com' && $password === 'admin') {
                $_SESSION['user_id'] = 1; 
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'admin';
                
                // Generate JWT for Admin
                $payload = [
                    'user_id' => 1,
                    'email' => $email,
                    'role' => 'admin'
                ];
                $jwt = JWT::encode($payload);

                sendResponse(['success' => true, 'user' => ['id' => 1, 'email' => $email, 'name' => 'Admin', 'role' => 'admin'], 'token' => $jwt]);
            } else {
                sendResponse(['success' => false, 'error' => 'Invalid credentials'], 401);
            }
        }
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Database error'], 500);
    }
}

