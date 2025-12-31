<?php
namespace Services;

use PDO;
use Exception;

class OrderService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllOrders() {
        $sql = "SELECT o.*, u.name as user_name,
                (CAST(o.total_amount AS DECIMAL) / 1000) + (EXTRACT(EPOCH FROM (NOW() - o.created_at)) / 86400 * 5) as priority_score
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                ORDER BY o.created_at DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrderById($id) {
        $stmt = $this->pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email 
                                    FROM orders o 
                                    LEFT JOIN users u ON o.user_id = u.id 
                                    WHERE o.id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $stmtItems = $this->pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmtItems->execute([$id]);
            $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        }
        return $order;
    }

    public function updateStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        // If status is Shipped, deduct stock
        if ($status === 'Shipped') {
            $this->deductStock($id);
        }

        // Integration logic (Webhooks, Notifications)
        $this->triggerPostUpdateActions($id, $status);
        
        return true;
    }

    private function deductStock($orderId) {
        // Fetch order items
        $stmt = $this->pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            // Deduct stock
            $updateStmt = $this->pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
            $updateStmt->execute([$item['quantity'], $item['product_id']]);

            // Log movement
            $logStmt = $this->pdo->prepare("INSERT INTO inventory_movements 
                (product_id, change_amount, type, reason, created_at) 
                VALUES (?, ?, 'outbound', 'Order Shipped #$orderId', NOW())");
            $logStmt->execute([$item['product_id'], -$item['quantity']]);
        }
    }

    private function triggerPostUpdateActions($id, $status) {
        // ERP+ Webhook Trigger
        if (file_exists(dirname(__DIR__, 2) . '/WebhookService.php')) {
            require_once dirname(__DIR__, 2) . '/WebhookService.php';
            $webhook = new \WebhookService();
            $webhook->trigger('ORDER_STATUS_UPDATED', $id, [
                'status' => $status,
                'updated_by' => 'admin'
            ]);
        }

        // System Notification
        $stmtOrder = $this->pdo->prepare("SELECT user_id, order_number FROM orders WHERE id = ?");
        $stmtOrder->execute([$id]);
        $orderInfo = $stmtOrder->fetch(PDO::FETCH_ASSOC);
        
        if ($orderInfo && $orderInfo['user_id']) {
            $msg = "訂單 #{$orderInfo['order_number']} 狀態已更新為: {$status}";
            $stmtNotif = $this->pdo->prepare("INSERT INTO notifications (user_id, type, message, is_read) VALUES (?, 'order_update', ?, FALSE)");
            $stmtNotif->execute([$orderInfo['user_id'], $msg]);
        }
    }
}
