<?php
require_once __DIR__ . '/../api_bootstrap.php';
require_once __DIR__ . '/../src/Services/ProductService.php';

use Services\ProductService;

try {
    $pdo = getDB();
    $service = new ProductService($pdo);
    
    echo "Testing getAllProducts()...\n";
    $products = $service->getAllProducts();
    
    echo "Successfully fetched " . count($products) . " products.\n";
    print_r(array_slice($products, 0, 1)); // Show first product

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
