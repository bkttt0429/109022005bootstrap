<?php
require_once 'api_bootstrap.php';

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 4;
$type = isset($_GET['type']) ? $_GET['type'] : 'auto';

try {
    $pdo = getDB();
    $recommendations = [];

    $randomFunc = (DB_TYPE === 'pgsql') ? 'RANDOM()' : 'RAND()';

    if ($type !== 'top' && $userId > 0) {
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
            $cat = $topCategory['category'];
            $recStmt = $pdo->prepare("
                SELECT p.* 
                FROM products p
                WHERE p.category = ?
                AND NOT EXISTS (
                    SELECT 1 FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.user_id = ? AND oi.product_id = p.id
                )
                ORDER BY $randomFunc
                LIMIT ?
            ");
            $recStmt->bindValue(1, $cat, PDO::PARAM_STR);
            $recStmt->bindValue(2, $userId, PDO::PARAM_INT);
            $recStmt->bindValue(3, $limit, PDO::PARAM_INT);
            $recStmt->execute();
            $recommendations = $recStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    if (empty($recommendations) || $type === 'top') {
        $fallbackStmt = $pdo->prepare("
            SELECT p.*, COALESCE(SUM(oi.quantity), 0) as sold
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            GROUP BY p.id
            ORDER BY sold DESC, p.id DESC
            LIMIT ?
        ");
        $fallbackStmt->bindValue(1, $limit, PDO::PARAM_INT);
        $fallbackStmt->execute();
        $recommendations = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    sendResponse(is_array($recommendations) ? $recommendations : []);

} catch (Exception $e) {
    sendResponse(['error' => $e->getMessage()], 500);
}
?>
