<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Ensure session coordinates with frontend
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
require_once 'db.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Initialize Cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$pdo = getDB();

function getCartData($pdo) {
    $cartData = [];
    $total = 0;
    $count = 0;

    if (!empty($_SESSION['cart'])) {
        $ids = array_keys($_SESSION['cart']);
        // Sanitize IDs to be integers
        $ids = array_map('intval', $ids);
        if (empty($ids)) return ['items' => [], 'total' => 0, 'count' => 0];

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "SELECT * FROM products WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $p) {
            $pid = $p['id'];
            if (!isset($_SESSION['cart'][$pid])) continue;
            
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
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'GET':
            echo json_encode(getCartData($pdo));
            break;

        case 'POST':
            // Add Item: { "product_id": 123, "quantity": 1 } (Default 1)
            $pid = $input['product_id'] ?? ($input['id'] ?? null); // Handle both formats
            if (is_array($pid)) $pid = $pid['id'] ?? null;
            
            $qty = (int)($input['quantity'] ?? 1);

            if (!$pid) {
                http_response_code(400);
                echo json_encode(['error' => 'Product ID required']);
                exit;
            }

            if (isset($_SESSION['cart'][$pid])) {
                $_SESSION['cart'][$pid] += $qty;
            } else {
                $_SESSION['cart'][$pid] = $qty;
            }
            
            echo json_encode(getCartData($pdo));
            break;

        case 'PUT':
            // Update Quantity: { "product_id": 123, "quantity": 5 }
            // OR PUT /cart/items?id=123 (Check query param or body)
            $pid = $input['product_id'] ?? ($input['id'] ?? null);
            $qty = $input['quantity'] ?? null;

            if (!$pid || $qty === null) {
                http_response_code(400);
                echo json_encode(['error' => 'Product ID and Quantity required']);
                exit;
            }

            if ($qty <= 0) {
                unset($_SESSION['cart'][$pid]);
            } else {
                $_SESSION['cart'][$pid] = (int)$qty;
            }

            echo json_encode(getCartData($pdo));
            break;

        case 'DELETE':
            // DELETE /cart?id=123 (Remove one) OR DELETE /cart (Clear all)
            $pid = $_GET['id'] ?? null;

            if ($pid) {
                unset($_SESSION['cart'][$pid]);
                echo json_encode(['message' => 'Item removed', 'cart' => getCartData($pdo)]);
            } else {
                $_SESSION['cart'] = [];
                echo json_encode(['message' => 'Cart cleared', 'cart' => getCartData($pdo)]);
            }
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
