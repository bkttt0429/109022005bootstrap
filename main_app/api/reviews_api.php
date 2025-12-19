<?php
require_once 'db.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $productId = $_GET['product_id'] ?? 0;
    
    if (!$productId) {
        echo json_encode(['reviews' => [], 'average_rating' => 0, 'total' => 0]);
        exit;
    }

    try {
        $pdo = getDB();
        // Fetch reviews with user names
        $stmt = $pdo->prepare("
            SELECT r.*, u.name as user_name, u.avatar_url 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = ? 
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$productId]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate Average
        $stmtAvg = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE product_id = ?");
        $stmtAvg->execute([$productId]);
        $stats = $stmtAvg->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'reviews' => $reviews, 
            'average_rating' => round($stats['avg_rating'] ?? 0, 1),
            'total' => $stats['total_reviews'] ?? 0
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // In a real app, verify session/token here. 
    // For this demo, we trust the client sends user_id (or we could parse it if we had proper session handling)
    // Let's assume the client sends { user_id, product_id, rating, comment }
    
    if (!isset($input['user_id'], $input['product_id'], $input['rating'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing fields']);
        exit;
    }

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$input['user_id'], $input['product_id'], $input['rating'], $input['comment'] ?? '']);
        
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
