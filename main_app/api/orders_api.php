<?php
require_once 'admin_gate.php';
require_once 'db.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

function getOrders($pdo) {
    try {
        $sql = "SELECT o.*, u.name as user_name 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                ORDER BY o.created_at DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

try {
    $pdo = getDB();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $orders = getOrders($pdo);
        echo json_encode($orders);
    } elseif ($method === 'POST') {
        // Update Status
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['action']) && $input['action'] === 'update_status') {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$input['status'], $input['id']]);
            echo json_encode(['success' => true]);
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
