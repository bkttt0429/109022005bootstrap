<?php
session_start();
require_once 'db.php';
require_once 'jwt_utils.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Helper (Duplicate from auth.php - ideally move to a shared helper file)
function migrateCartOnRegister($pdo, $userId) {
    if (session_id()) {
        $sid = session_id();
        $rows = $pdo->prepare('SELECT product_id, quantity FROM carts WHERE session_id = :s');
        $rows->execute(['s' => $sid]);
        $items = $rows->fetchAll(PDO::FETCH_ASSOC);

        if ($items) {
            foreach ($items as $r) {
                // Determine if we merge or replace. Merging is safer.
                $p = (int)$r['product_id'];
                $q = (int)$r['quantity'];
                $up = $pdo->prepare('INSERT INTO carts (user_id, product_id, quantity) VALUES (:u,:p,:q) ON DUPLICATE KEY UPDATE quantity = quantity + :q2');
                $up->execute(['u' => $userId, 'p' => $p, 'q' => $q, 'q2' => $q]);
            }
            $del = $pdo->prepare('DELETE FROM carts WHERE session_id = :s'); 
            $del->execute(['s' => $sid]);
        }
    }
}

try {
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $name = trim($input['name'] ?? '');

        if (!$email || !$password || strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid email and password (min 6 chars) required']);
            exit;
        }

        // Check exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => 'Email already exists']);
            exit;
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

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'User registered', 'token' => $jwt, 'user' => ['id'=>$uid, 'name'=>$name, 'email'=>$email]]);
        } else {
            throw new Exception("Registration failed");
        }

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
