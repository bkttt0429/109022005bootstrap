<?php
require_once 'api_bootstrap.php';

try {
    $pdo = getDB();
    $sql = "SELECT o.*, u.name as user_name,
            (o.total_amount / 1000) + (EXTRACT(EPOCH FROM (NOW() - o.created_at)) / 86400 * 5) as priority_score
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY priority_score DESC, o.created_at DESC";
    $stmt = $pdo->query($sql);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Count: " . count($orders) . "\n";
    print_r($orders[0] ?? 'No orders');
} catch (PDOException $e) {
    echo "SQL Error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
