<?php
session_start();
require_once 'db.php';
require_once 'jwt_utils.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

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
                
                // Insert or Update user cart
                $up = $pdo->prepare('INSERT INTO carts (user_id, product_id, quantity) VALUES (:u,:p,:q) ON DUPLICATE KEY UPDATE quantity = quantity + :q2');
                $up->execute(['u' => $userId, 'p' => $p, 'q' => $q, 'q2' => $q]);
            }
            // Clear session cart in DB
            $del = $pdo->prepare('DELETE FROM carts WHERE session_id = :s'); 
            $del->execute(['s' => $sid]);
        }
        
        // Also merge PHP session cart if using session-based storage coupled with DB
        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
             // Logic could be added here to sync $_SESSION['cart'] to DB for the user
             // For now, assuming the DB 'carts' table is the source of truth for persistent carts
        }
    }
}

try {
    switch ($method) {
        case 'GET':
            // Check Login Status
            // Supports both Session and JWT checking logic if needed, but primarily Session for this hybrid app
            if (isset($_SESSION['user_id'])) {
                echo json_encode([
                    'loggedIn' => true,
                    'user' => [
                        'id' => $_SESSION['user_id'],
                        'email' => $_SESSION['user_email'] ?? '',
                        'role' => $_SESSION['user_role'] ?? 'user'
                    ]
                ]);
            } else {
                echo json_encode(['loggedIn' => false]);
            }
            break;

        case 'POST':
            // Login
            $input = json_decode(file_get_contents('php://input'), true);
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';

            if (!$email || !$password) {
                http_response_code(400);
                echo json_encode(['error' => 'Email and Password required']);
                exit;
            }

            // Check hardcoded admin (Legacy support)
            if ($email === 'admin@example.com' && $password === 'admin') {
                 $_SESSION['user_id'] = 1;
                 $_SESSION['user_email'] = $email;
                 $_SESSION['user_role'] = 'admin';
                 
                 $jwt = JWT::encode(['user_id'=>1, 'email'=>$email, 'role'=>'admin']);
                 echo json_encode([
                     'success' => true, 
                     'token' => $jwt, 
                     'user' => ['id'=>1, 'email'=>$email, 'role'=>'admin']
                 ]);
                 exit;
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
                $jwt = JWT::encode([
                    'user_id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role'] ?? 'user'
                ]);
                
                unset($user['password_hash']);
                echo json_encode(['success' => true, 'token' => $jwt, 'user' => $user]);
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
            }
            break;

        case 'DELETE':
            // Logout
            session_unset();
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'Logged out']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
