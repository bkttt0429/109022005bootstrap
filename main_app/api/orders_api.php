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

    if ($method === 'GET') {
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
    } elseif ($method === 'POST') {
        // Update Status
        $input = getJsonInput();
        if (isset($input['action']) && $input['action'] === 'update_status') {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$input['status'], $input['id']]);
            sendResponse(['success' => true]);
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
