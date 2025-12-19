<?php
// Disable error output to avoid breaking JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Session Cookie Params: Path=/ to ensure it works across the site and proxy
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '', // Default
    'secure' => false, // Set true if https
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
require_once 'db.php';

// Debug Log
function logCart($msg) {
    file_put_contents('cart_debug.log', date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Log Request
logCart("Request: " . $_SERVER['REQUEST_METHOD'] . " SessionID: " . session_id() . " Payload: " . file_get_contents('php://input'));

// Initialize Cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
    logCart("Session cart initialized. Items: " . json_encode($_SESSION['cart']));
} else {
    logCart("Session cart exists. Items: " . json_encode($_SESSION['cart']));
}

$response = ['items' => [], 'total' => 0, 'count' => 0];

// Helper to calculate totals and fetch details
function refreshCartData($pdo) {
    $cartData = [];
    $total = 0;
    $count = 0;

    if (!empty($_SESSION['cart'])) {
        $ids = array_keys($_SESSION['cart']);
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "SELECT * FROM products WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $p) {
            $pid = $p['id'];
            $qty = $_SESSION['cart'][$pid];
            $subtotal = $p['price'] * $qty;
            
            $cartData[] = [
                'id' => $pid,
                'name' => $p['name'],
                'price' => $p['price'],
                'image_url' => $p['image_url'],
                'qty' => $qty,
                'subtotal' => $subtotal
            ];
            $total += $subtotal;
            $count += $qty;
        }
    }
    return ['items' => $cartData, 'total' => $total, 'count' => $count];
}

try {
    $pdo = getDB();

    // Handle Actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $pid = $input['id'] ?? null;
        if ($action === 'add' && $pid) {
            // Defensive: if $pid is an object/array (due to frontend passing product object)
            if (is_array($pid) && isset($pid['id'])) {
                $pid = $pid['id'];
            }
            logCart("Adding item ID: " . $pid);

            if (isset($_SESSION['cart'][$pid])) {
                $_SESSION['cart'][$pid]++;
            } else {
                $_SESSION['cart'][$pid] = 1;
            }
        } elseif ($action === 'remove' && $pid) {
            unset($_SESSION['cart'][$pid]);
        } elseif ($action === 'clear') {
            $_SESSION['cart'] = [];
        }
    }

    $response = refreshCartData($pdo);
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
