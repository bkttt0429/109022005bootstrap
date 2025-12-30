<?php
require_once 'api_bootstrap.php';

// Ensure we only handle GET requests for notifications
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['error' => 'Method not allowed'], 405);
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
// Fallback to 1 minute ago if no timestamp provided
$lastChecked = $_GET['last_checked'] ?? date('Y-m-d H:i:s', strtotime('-1 minute'));

if (!$userId) {
    sendResponse(['notifications' => []]);
}

try {
    $pdo = getDB();
    
    // PostgreSQL handles string dates well, but we should ensure format matching
    // If the input is ISO (from JS toISOString), we handle it.
    // PG is smart with '2025-12-30T07:12:34.567Z'
    
    $stmt = $pdo->prepare("
        SELECT id, order_number, status, updated_at 
        FROM orders 
        WHERE user_id = ? 
        AND updated_at > ? 
        ORDER BY updated_at ASC
    ");
    $stmt->execute([$userId, $lastChecked]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the latest updated_at or current server time to avoid repeats
    $latestTime = !empty($notifications) 
        ? end($notifications)['updated_at'] 
        : date('Y-m-d H:i:s');

    sendResponse([
        'notifications' => $notifications, 
        'timestamp' => $latestTime
    ]);

} catch (Exception $e) {
    sendResponse(['error' => $e->getMessage()], 500);
}
