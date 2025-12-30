<?php
require_once 'api_bootstrap.php';

try {
    $pdo = getDB();
    
    // PostgreSQL strict GROUP BY requires all columns or rely on PK functional dependency
    // PG 15+ supports p.id functional dependency for p.*
    $sql = "
        SELECT p.*, COALESCE(SUM(oi.quantity), 0) as sales_volume 
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        GROUP BY p.id
        ORDER BY sales_volume DESC, p.id DESC
    ";
    
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ensure array for frontend .map()
    sendResponse(is_array($products) ? $products : []);

} catch (Exception $e) {
    sendResponse(['error' => $e->getMessage()], 500);
}
