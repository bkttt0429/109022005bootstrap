<?php
// CORS Configuration
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400");
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check Status
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'loggedIn' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role'] ?? 'user'
            ]
        ]);
    } else {
        echo json_encode(['loggedIn' => false]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Login
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    try {
        require_once 'db.php';
        require_once 'jwt_utils.php'; // JWT Import
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
            
            echo json_encode(['success' => true, 'user' => $user, 'token' => $jwt]);
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

                echo json_encode(['success' => true, 'user' => ['id' => 1, 'email' => $email, 'name' => 'Admin', 'role' => 'admin'], 'token' => $jwt]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
            }
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

