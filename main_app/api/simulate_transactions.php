<?php
require_once 'db.php';

header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Simulation Config
$MAX_ORDERS_PER_USER = 5; // Each user will have up to this many orders
$HISTORY_DAYS = 90; // Go back 90 days

$statuses = ['Pending', 'Processing', 'Completed', 'Cancelled', 'Shipped', 'Refunded'];

try {
    $pdo = getDB();

    // 1. Get All Users and Products
    $users = $pdo->query("SELECT id, name FROM users")->fetchAll(PDO::FETCH_ASSOC);
    $products = $pdo->query("SELECT id, name, price FROM products")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users) || empty($products)) {
        throw new Exception("Users or Products table is empty. Please run seed_data.php first.");
    }

    $ordersCreated = 0;
    $stmtOrder = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status, order_number, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($users as $user) {
        // Random usage pattern: 20% heavy users, 50% regular, 30% inactive (just for realism, but user asked to simulate behavior so let's make them active)
        // Let's just give everyone activity for this request.
        $orderCount = rand(1, $MAX_ORDERS_PER_USER); 

        for ($i = 0; $i < $orderCount; $i++) {
            $status = $statuses[array_rand($statuses)];
            
            // weighted status: make more 'Completed'
            if (rand(0, 10) > 3) $status = 'Completed'; 

            $timestamp = time() - rand(0, $HISTORY_DAYS * 24 * 60 * 60);
            $createdAt = date('Y-m-d H:i:s', $timestamp);
            $orderNumber = 'ORD-' . strtoupper(substr($user['name'], 0, 2)) . '-' . date('Ymd', $timestamp) . '-' . rand(1000, 9999);

            // Random Items
            $itemCount = rand(1, 6);
            $orderItems = [];
            $totalAmount = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $qty = rand(1, 3);
                $subtotal = $product['price'] * $qty;
                
                $orderItems[] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $qty,
                    'subtotal' => $subtotal
                ];
                $totalAmount += $subtotal;
            }

            // Insert Order
            $stmtOrder->execute([$user['id'], $totalAmount, $status, $orderNumber, $createdAt]);
            $orderId = $pdo->lastInsertId();

            // Insert Order Items
            foreach ($orderItems as $item) {
                $stmtItem->execute([
                    $orderId, 
                    $item['product_id'], 
                    $item['product_name'], 
                    $item['quantity'], 
                    $item['price'], 
                    $item['subtotal']
                ]);
            }
            $ordersCreated++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Transaction Simulation Complete.",
        'details' => [
            'users_processed' => count($users),
            'new_orders_generated' => $ordersCreated,
            'avg_order_per_user' => round($ordersCreated / count($users), 1)
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
