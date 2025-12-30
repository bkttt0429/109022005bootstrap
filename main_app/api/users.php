<?php
require_once 'api_bootstrap.php';

// Helper (Duplicate from auth.php - ideally move to a shared helper file)
function migrateCartOnRegister($pdo, $userId) {
    if (session_id()) {
        $sid = session_id();
        $rows = $pdo->prepare('SELECT product_id, quantity FROM carts WHERE session_id = :s');
        $rows->execute(['s' => $sid]);
        $items = $rows->fetchAll(PDO::FETCH_ASSOC);

        if ($items) {
            foreach ($items as $r) {
                $p = (int)$r['product_id'];
                $q = (int)$r['quantity'];
                
                if (DB_TYPE === 'pgsql') {
                    $up = $pdo->prepare('INSERT INTO carts (user_id, product_id, quantity) VALUES (:u,:p,:q) 
                                       ON CONFLICT (user_id, product_id) DO UPDATE SET quantity = carts.quantity + EXCLUDED.quantity');
                } else {
                    $up = $pdo->prepare('INSERT INTO carts (user_id, product_id, quantity) VALUES (:u,:p,:q) 
                                       ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)');
                }
                $up->execute(['u' => $userId, 'p' => $p, 'q' => $q]);
            }
            $del = $pdo->prepare('DELETE FROM carts WHERE session_id = :s'); 
            $del->execute(['s' => $sid]);
        }
    }
}

try {
    $pdo = getDB();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $input = getJsonInput();
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $name = trim($input['name'] ?? '');

        if (!$email || !$password || strlen($password) < 6) {
            sendResponse(['error' => 'Valid email and password (min 6 chars) required'], 400);
        }

        // Check exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendResponse(['error' => 'Email already exists'], 409);
        }

        // Create
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $email, $hash])) {
            $uid = $pdo->lastInsertId();
            
            // Auto Login
            $_SESSION['user_id'] = $uid;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'user';
            
            migrateCartOnRegister($pdo, $uid);

            $jwt = JWT::encode([
                'user_id' => $uid,
                'email' => $email,
                'role' => 'user'
            ]);

            sendResponse([
                'success' => true, 
                'message' => 'User registered', 
                'token' => $jwt, 
                'user' => ['id'=>$uid, 'name'=>$name, 'email'=>$email]
            ], 201);
        } else {
            throw new Exception("Registration failed");
        }

    } else {
        sendResponse(['error' => 'Method Not Allowed'], 405);
    }

} catch (Exception $e) {
    sendResponse(['error' => $e->getMessage()], 500);
}
?>
