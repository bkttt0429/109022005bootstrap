<?php
require_once 'api_bootstrap.php';

header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fake Data Arrays
$firstNames = ['James', 'Mary', 'John', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda', 'William', 'Elizabeth', 'Thomas', 'Jessica', 'David', 'Sarah', 'Richard', 'Karen', 'Joseph', 'Nancy', 'Charles', 'Lisa'];
$lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin'];
$domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'example.com'];
$statuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];

// Fake Product Data
$productAdjectives = ['Premium', 'Luxury', 'Basic', 'Pro', 'Ultra', 'Smart', 'Eco', 'Digital', 'Analog', 'Wireless'];
$productNouns = ['Watch', 'Phone', 'Laptop', 'Headphones', 'Speaker', 'Camera', 'Tablet', 'Monitor', 'Mouse', 'Keyboard'];

function getRandomName() {
    global $firstNames, $lastNames;
    return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
}

function getRandomEmail($name) {
    global $domains;
    $slug = strtolower(str_replace(' ', '.', $name));
    return $slug . rand(100, 999) . '@' . $domains[array_rand($domains)];
}

function getRandomProduct() {
    global $productAdjectives, $productNouns;
    return $productAdjectives[array_rand($productAdjectives)] . ' ' . $productNouns[array_rand($productNouns)] . ' ' . rand(2023, 2025);
}

try {
    $pdo = getDB();

    // 1. Ensure Tables Exist (Skip if using PostgreSQL as it's handled by schema_pg.sql)
    if (DB_TYPE === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'user',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            category VARCHAR(50),
            image_url VARCHAR(255),
            stock_quantity INT DEFAULT 100,
            sku VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    // 2. Generate 50 Users (Total)
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    $targetUsers = 50;
    
    $accounts = [];
    $defaultPass = 'password123';
    $defaultHash = password_hash($defaultPass, PASSWORD_BCRYPT);

    if ($userCount < $targetUsers) {
        $needed = $targetUsers - $userCount;
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
        
        for ($i = 0; $i < $needed; $i++) {
            $name = getRandomName();
            $email = getRandomEmail($name);
            
            try {
                $stmt->execute([$name, $email, $defaultHash]);
                $accounts[] = ['name' => $name, 'email' => $email, 'password' => $defaultPass];
            } catch (Exception $e) {
                // Ignore duplicates
            }
        }
    }

    // 3. Generate 50 Products (Total)
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $prodCount = $stmt->fetchColumn();
    $targetProducts = 50;
    
    if ($prodCount < $targetProducts) {
        $needed = $targetProducts - $prodCount;
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, stock_quantity, image_url, sku) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < $needed; $i++) {
            $name = getRandomProduct();
            $desc = "This is a high-quality " . $name;
            $price = rand(100, 50000);
            $cat = ['Electronics', 'Home', 'Clothing', 'Accessories'][rand(0, 3)];
            $stock = rand(10, 500);
            $img = "https://placehold.co/300?text=" . urlencode($name);
            $sku = strtoupper(substr($name, 0, 3)) . '-' . rand(1000, 9999);

            $stmt->execute([$name, $desc, $price, $cat, $stock, $img, $sku]);
        }
    }

    // 4. Get Data
    $products = $pdo->query("SELECT id, name, price FROM products")->fetchAll(PDO::FETCH_ASSOC);
    $userIds = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);

    // 5. Simulate Orders (Ensure each new user has activity)
    $newOrdersCount = 0;
    $stmtOrder = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status, order_number, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");

    // Create 50 Transactions
    for ($i = 0; $i < 50; $i++) {
        $userId = $userIds[array_rand($userIds)];
        $status = $statuses[array_rand($statuses)];
        $timestamp = time() - rand(0, 60 * 24 * 60 * 60); // Last 60 days
        $createdAt = date('Y-m-d H:i:s', $timestamp);
        $orderNumber = 'ORD-' . date('YmdHis', $timestamp) . '-' . rand(1000, 9999);

        // Random Items
        $itemCount = rand(1, 5);
        $orderItems = [];
        $totalAmount = 0;

        for ($j = 0; $j < $itemCount; $j++) {
            $product = $products[array_rand($products)];
            $qty = rand(1, 4);
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

        $stmtOrder->execute([$userId, $totalAmount, $status, $orderNumber, $createdAt]);
        $orderId = $pdo->lastInsertId();

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
        $newOrdersCount++;
    }

    echo json_encode([
        'success' => true,
        'message' => "Enhanced Simulation complete.",
        'details' => [
            'users_added' => count($accounts),
            'products_total' => count($products),
            'orders_created' => $newOrdersCount
        ],
        'accounts' => $accounts
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
