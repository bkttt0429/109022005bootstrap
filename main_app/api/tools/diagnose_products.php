<?php
// Bypass routing and controller, test Service and DB directly
require_once dirname(__DIR__) . '/api_bootstrap.php';
require_once dirname(__DIR__) . '/src/Services/ProductService.php';

use Services\ProductService;

header('Content-Type: application/json');

try {
    $pdo = getDB();
    $service = new ProductService($pdo);
    
    // Test 1: Count
    $count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $debug['count'] = $count;
    
    // Test 2: Service getAllProducts
    $products = $service->getAllProducts();
    $debug['products_raw'] = $products;
    
    echo json_encode([
        'status' => 'success',
        'debug' => $debug
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
