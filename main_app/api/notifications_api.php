<?php
require_once 'db.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $userId = $_GET['user_id'] ?? 0;
    // Client sends the last timestamp they checked
    $lastChecked = $_GET['last_checked'] ?? date('Y-m-d H:i:s', strtotime('-1 minute'));

    if (!$userId) {
        echo json_encode(['notifications' => []]);
        exit;
    }

    try {
        $pdo = getDB();
        
        // Find orders updated AFTER the last check
        $stmt = $pdo->prepare("
            SELECT id, order_number, status, updated_at 
            FROM orders 
            WHERE user_id = ? 
            AND updated_at > ? 
            ORDER BY updated_at DESC
        ");
        $stmt->execute([$userId, $lastChecked]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'notifications' => $notifications, 
            'timestamp' => date('Y-m-d H:i:s') // Server time for next check
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
