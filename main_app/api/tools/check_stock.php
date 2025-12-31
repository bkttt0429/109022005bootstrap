<?php
require_once __DIR__ . '/../api_bootstrap.php';

try {
    $pdo = getDB();
    
    $productId = 45;
    $stmt = $pdo->prepare("SELECT id, name, stock_quantity FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Product Info (ID: $productId):\n";
    print_r($product);
    
    $stmt = $pdo->query("SELECT * FROM inventory_movements ORDER BY created_at DESC LIMIT 5");
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nRecent Movements:\n";
    print_r($movements);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
