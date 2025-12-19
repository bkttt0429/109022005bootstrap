<?php
require_once 'db.php';
header('Content-Type: application/json');

try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updateStmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?");

    $count = 0;
    foreach ($products as $p) {
        // Use Picsum with random seed to get consistent but different images per product
        // 300x300 square images
        $imgUrl = "https://picsum.photos/seed/" . $p['id'] . "/300/300";
        $updateStmt->execute([$imgUrl, $p['id']]);
        $count++;
    }

    echo json_encode(['success' => true, 'updated' => $count]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
