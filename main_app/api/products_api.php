<?php
require_once 'db.php';

header('Content-Type: application/json');

// Allow CORS for development (Vite)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

try {
    $pdo = getDB();
    $stmt = $pdo->query("
        SELECT p.*, COALESCE(SUM(oi.quantity), 0) as sales_volume 
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        GROUP BY p.id
        ORDER BY sales_volume DESC, p.id DESC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
