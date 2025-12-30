<?php
require_once 'api_bootstrap.php';

// Helper to migrate session cart to user cart
function migrateCart($pdo, $userId) {
    if (session_id()) {
        $sid = session_id();
        $rows = $pdo->prepare('SELECT product_id, quantity FROM carts WHERE session_id = :s');
        $rows->execute(['s' => $sid]);
        $items = $rows->fetchAll(PDO::FETCH_ASSOC);

        if ($items) {
            foreach ($items as $r) {
                $p = (int)$r['product_id'];
                $q = (int)$r['quantity'];
                
                // PostgreSQL UPSERT syntax
                if (DB_TYPE === 'pgsql') {
                    $up = $pdo->prepare('INSERT INTO carts (user_id, product_id, quantity) VALUES (:u,:p,:q) 
                                       ON CONFLICT (user_id, product_id) DO UPDATE SET quantity = carts.quantity + EXCLUDED.quantity');
                } else {
                    $up = $pdo->prepare('INSERT INTO carts (user_id, product_id, quantity) VALUES (:u,:p,:q) 
                                       ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)');
                }
                $up->execute(['u' => $userId, 'p' => $p, 'q' => $q]);
            }
            // Clear session cart in DB
            $del = $pdo->prepare('DELETE FROM carts WHERE session_id = :s'); 
            $del->execute(['s' => $sid]);
        }
    }
}

try {
    $pdo = getDB();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (isset($_SESSION['user_id'])) {
                sendResponse([
                    'loggedIn' => true,
                    'user' => [
                        'id' => $_SESSION['user_id'],
                        'email' => $_SESSION['user_email'] ?? '',
                        'role' => $_SESSION['user_role'] ?? 'user'
                    ]
                ]);
            } else {
                sendResponse(['loggedIn' => false]);
            }
            break;

        case 'POST':
            $input = getJsonInput();
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';

            if (!$email || !$password) {
                sendResponse(['error' => 'Email and Password required'], 400);
            }

            // Database Check
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'] ?? 'user';
                
                // Migrate Cart
                migrateCart($pdo, $user['id']);

                // Generate Token
                $payload = [
                    'user_id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role'] ?? 'user'
                ];
                $jwt = JWT::encode($payload);
                
                unset($user['password_hash']);
                sendResponse(['success' => true, 'token' => $jwt, 'user' => $user]);
            } else {
                sendResponse(['success' => false, 'error' => 'Invalid credentials'], 401);
            }
            break;

        case 'DELETE':
            session_unset();
            session_destroy();
            sendResponse(['success' => true, 'message' => 'Logged out']);
            break;

        default:
            sendResponse(['error' => 'Method Not Allowed'], 405);
            break;
    }

} catch (Exception $e) {
    sendResponse(['error' => $e->getMessage()], 500);
}
?>
