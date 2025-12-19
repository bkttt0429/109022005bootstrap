<?php
require_once 'db.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 4;
$type = isset($_GET['type']) ? $_GET['type'] : 'auto'; // 'auto' (default) or 'top'

try {
    $pdo = getDB();
    $recommendations = [];

    // If type is NOT 'top', try user-based recommendation first
    if ($type !== 'top' && $userId > 0) {
        // 1. User Logic: Find top purchased categories
        // Get user's most frequently bought category
        $stmt = $pdo->prepare("
            SELECT p.category, COUNT(*) as count 
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = ?
            GROUP BY p.category
            ORDER BY count DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $topCategory = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($topCategory) {
            // Recommend products from this category, excluding what they bought
            $cat = $topCategory['category'];
            $recStmt = $pdo->prepare("
                SELECT DISTINCT p.* 
                FROM products p
                WHERE p.category = ?
                AND p.id NOT IN (
                    SELECT product_id FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.user_id = ?
                )
                ORDER BY RAND()
                LIMIT ?
            ");
            $recStmt->bindValue(1, $cat, PDO::PARAM_STR);
            $recStmt->bindValue(2, $userId, PDO::PARAM_INT);
            $recStmt->bindValue(3, $limit, PDO::PARAM_INT);
            $recStmt->execute();
            $recommendations = $recStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // 2. Fallback or Forced 'top': Popular Products
    if (empty($recommendations) || $type === 'top') {
        // Get top selling products overall
        $fallbackStmt = $pdo->prepare("
            SELECT p.*, COALESCE(SUM(oi.quantity), 0) as sold
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            GROUP BY p.id
            ORDER BY sold DESC
            LIMIT ?
        ");
        $fallbackStmt->bindValue(1, $limit, PDO::PARAM_INT);
        $fallbackStmt->execute();
        $recommendations = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($recommendations);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
