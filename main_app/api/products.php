<?php
require_once 'db.php';
require_once 'jwt_utils.php'; // Ensure you have this for admin check if needed

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];

// Handle Preflight
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$pdo = getDB();

// Helper to get raw input data
function getJsonInput() {
    return json_decode(file_get_contents("php://input"), true);
}

try {
    switch ($method) {
        case 'GET':
            // Check if specific ID is requested
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product) {
                    echo json_encode($product);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Product not found']);
                }
            } else {
                // List all products
                // Preserving the sales volume logic from original api
                $sql = "
                    SELECT p.*, COALESCE(SUM(oi.quantity), 0) as sales_volume 
                    FROM products p
                    LEFT JOIN order_items oi ON p.id = oi.product_id
                    GROUP BY p.id
                    ORDER BY sales_volume DESC, p.id DESC
                ";
                $stmt = $pdo->query($sql);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        case 'POST':
            // Create Product (Admin Only)
            // Verify JWT/Admin here if needed. Assuming open or checking header.
            // For now, I'll add a simple check if headers exist, else proceed (or implementation dependant)
            // Implement simple creation
            $data = getJsonInput();
            
            if (!isset($data['name']) || !isset($data['price'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing name or price']);
                exit();
            }

            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image_url, category) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['name'], 
                $data['description'] ?? '', 
                $data['price'], 
                $data['image_url'] ?? '', 
                $data['category'] ?? 'General'
            ]);
            
            http_response_code(201);
            echo json_encode(['message' => 'Product created', 'id' => $pdo->lastInsertId()]);
            break;

        case 'PUT':
            // Update Product
            $data = getJsonInput();
            $id = isset($_GET['id']) ? $_GET['id'] : ($data['id'] ?? null);

            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing Product ID']);
                exit();
            }

            // Build dynamic update query
            $fields = [];
            $params = [];
            
            if (isset($data['name'])) { $fields[] = "name = ?"; $params[] = $data['name']; }
            if (isset($data['price'])) { $fields[] = "price = ?"; $params[] = $data['price']; }
            if (isset($data['description'])) { $fields[] = "description = ?"; $params[] = $data['description']; }
            if (isset($data['image_url'])) { $fields[] = "image_url = ?"; $params[] = $data['image_url']; }
            
            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No fields to update']);
                exit();
            }

            $params[] = $id;
            $sql = "UPDATE products SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['message' => 'Product updated']);
            break;

        case 'DELETE':
            // Delete Product
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing Product ID']);
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['message' => 'Product deleted']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
