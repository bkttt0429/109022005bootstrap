<?php
require_once 'admin_gate.php'; // Protects this endpoint
require_once 'db.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // List orders
            // Optional: Filter by user_id or status if passed as query params
            $sql = "SELECT o.*, u.name as user_name 
                    FROM orders o 
                    LEFT JOIN users u ON o.user_id = u.id 
                    ORDER BY o.created_at DESC";
            $stmt = $pdo->query($sql);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'PUT': 
        case 'PATCH':
            // Update Order Status
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? ($_GET['id'] ?? null);
            $status = $input['status'] ?? null;

            if (!$id || !$status) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing ID or Status']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            if ($stmt->execute([$status, $id])) {
                echo json_encode(['success' => true, 'message' => 'Status updated']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Update failed']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
