<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 1. Check Authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// 2. Check Cart
if (empty($_SESSION['cart'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Cart is empty']);
    exit;
}

// 3. Get Input (Shipping Info)
$input = json_decode(file_get_contents('php://input'), true);
$shippingInfo = $input['shipping_info'] ?? [];
$recipientName = $shippingInfo['name'] ?? 'Unknown';
$recipientAddress = $shippingInfo['address'] ?? 'Unknown Address';
$recipientPhone = $shippingInfo['phone'] ?? 'Unknown Phone';

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    // 4. Calculate Total & Validate Stock
    $cartItems = $_SESSION['cart']; // [prod_id => qty]
    $ids = array_keys($cartItems);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id IN ($placeholders) FOR UPDATE");
    $stmt->execute($ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC); // Key by index, not ID directly yet

    $productMap = [];
    foreach ($products as $p) {
        $productMap[$p['id']] = $p;
    }

    $totalAmount = 0;
    $orderItemsData = [];

    foreach ($cartItems as $pid => $qty) {
        if (!isset($productMap[$pid])) {
            throw new Exception("Product ID $pid not found");
        }
        $product = $productMap[$pid];
        
        if ($product['stock_quantity'] < $qty) {
            throw new Exception("Insufficient stock for {$product['name']}");
        }

        $price = $product['price'];
        $subtotal = $price * $qty;
        $totalAmount += $subtotal;

        $orderItemsData[] = [
            'product_id' => $pid,
            'product_name' => $product['name'],
            'price' => $price,
            'quantity' => $qty,
            'subtotal' => $subtotal
        ];

        // 5. Decrement Stock
        $updateStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $updateStock->execute([$qty, $pid]);
    }

    // 6. Create Order header
    $orderNumber = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);
    // Note: 'status' default is Pending. using created_at default CURRENT_TIMESTAMP.
    // If you haven't added `shipping_address` columns to order, maybe just store it in a text field or ignore for this demo scope if not strictly required by schema. 
    // For now, let's assume standard schema. I will put address in a note or just proceed. 
    // Schema from seed_data: user_id, total_amount, status, order_number, created_at.
    // I can stick to that.
    
    $stmtOrder = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status, order_number) VALUES (?, ?, 'Pending', ?)");
    $stmtOrder->execute([$userId, $totalAmount, $orderNumber]);
    $orderId = $pdo->lastInsertId();

    // 7. Create Order Items
    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($orderItemsData as $item) {
        $stmtItem->execute([
            $orderId,
            $item['product_id'],
            $item['product_name'],
            $item['quantity'],
            $item['price'],
            $item['subtotal']
        ]);
    }

    // 8. Clear Cart
    $_SESSION['cart'] = [];

    $pdo->commit();

    echo json_encode(['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
