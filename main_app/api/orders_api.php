<?php
require_once 'api_bootstrap.php';
require_once 'admin_gate.php';

function getOrders($pdo) {
    try {
        // ERP Algorithm: Priority Scoring
        // Logic: (Total Amount / 1000) weights value + (Days Pending * 5) weights FIFO urgency
        // Ensure explicit casting for PostgreSQL
        $sql = "SELECT o.*, u.name as user_name,
                (CAST(o.total_amount AS DECIMAL) / 1000) + (EXTRACT(EPOCH FROM (NOW() - o.created_at)) / 86400 * 5) as priority_score
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                ORDER BY o.created_at DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error to a file that user can see or check
        file_put_contents('db_error.log', $e->getMessage());
        return [];
    }
}

try {
    $pdo = getDB();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Debugging: Log every request to this endpoint
    $debugLog = 'debug_orders_api.log';
    $rawInput = file_get_contents('php://input');
    file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Method: $method | URI: " . $_SERVER['REQUEST_URI'] . " | Body: $rawInput\n", FILE_APPEND);

    if ($method === 'GET' && (!isset($_GET['action']) || $_GET['action'] !== 'update_status')) {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id > 0) {
            // Get Single Order with User details
            $stmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email 
                                  FROM orders o 
                                  LEFT JOIN users u ON o.user_id = u.id 
                                  WHERE o.id = ?");
            $stmt->execute([$id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                // Get Items
                $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $stmtItems->execute([$id]);
                $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
                sendResponse($order);
            } else {
                sendResponse(['error' => 'Order not found'], 404);
            }
        } else {
            $orders = getOrders($pdo);
            sendResponse(is_array($orders) ? $orders : []);
        }
    } elseif ($method === 'POST' || ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'update_status')) {
        // Update Status
        $input = ($method === 'GET') ? $_GET : getJsonInput();
        
        // update_logic: // Label for shared update logic - removed as GET update logic is now separate
        if (isset($input['action']) && $input['action'] === 'update_status') {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$input['status'], $input['id']]);

            // ERP+ Webhook Trigger
            require_once 'WebhookService.php';
            $webhook = new WebhookService();
            $webhook->trigger('ORDER_STATUS_UPDATED', $input['id'], [
                'status' => $input['status'],
                'updated_by' => 'admin' // In real app, get from session
            ]);

            // [NEW] System Notification for User (so they see it pop up)
            $stmtOrder = $pdo->prepare("SELECT user_id, order_number FROM orders WHERE id = ?");
            $stmtOrder->execute([$input['id']]);
            $orderInfo = $stmtOrder->fetch(PDO::FETCH_ASSOC);
            
            if ($orderInfo && $orderInfo['user_id']) {
                $msg = "訂單 #{$orderInfo['order_number']} 狀態已更新為: {$input['status']}";
                $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, type, message, is_read) VALUES (?, 'order_update', ?, FALSE)");
                $stmtNotif->execute([$orderInfo['user_id'], $msg]);
            }

            sendResponse(['success' => true]);
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
