<?php
namespace Controllers;

use PDO;
use Exception;
use Core\Response;
use Services\InventoryService;
use Services\WebhookService;

class InventoryController {
    private $service;

    public function __construct() {
        // Ensure we handle potential DB connection issues gracefully
        $db = getDB();
        if (!$db) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Database connection failed']);
            exit;
        }
        $this->service = new InventoryService($db);
    }

    public function getInventory($vars = null) {
        $data = $this->service->getInventoryStatus();
        return Response::json($data);
    }

    public function inbound($vars = null) {
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true) ?? [];
        
        // 合併 JSON 與 網址參數，確保一定抓得到
        $data = array_merge($_REQUEST, $jsonData);
        
        if (!isset($data['product_id']) || !isset($data['quantity'])) {
            return Response::json([
                'error' => 'Missing product_id or quantity',
                'received' => $data
            ], 400);
        }

        try {
            $this->service->processInbound(
                $data['product_id'], 
                $data['quantity'], 
                $data['reason'] ?? 'RESTOCK',
                $data['reference'] ?? null
            );
            return Response::json(['message' => 'Stock updated successfully']);
        } catch (Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function triggerRestock($vars = null) {
        $data = json_decode(file_get_contents('php://input'), true);
        $productId = $data['product_id'] ?? null;

        if (!$productId) {
            return Response::json(['error' => 'Product ID required'], 400);
        }

        // Fetch product details for the webhook payload
        $stmt = $this->service->getPdo()->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            return Response::json(['error' => 'Product not found'], 404);
        }

        // Trigger n8n via WebhookService
        $webhook = new WebhookService($this->service->getPdo());
        $result = $webhook->trigger('RESTOCK_REQUEST', $productId, [
            'product_name' => $product['name'],
            'sku' => $product['sku'],
            'current_stock' => $product['stock_quantity'],
            'min_stock' => $product['min_stock_level'],
            'suggested_qty' => max(50, $product['avg_daily_sales'] * 30) // Suggest 30 days of stock
        ]);

        if (!$result) {
            return Response::json([
                'message' => 'No active webhook found for RESTOCK_REQUEST. Please configure n8n webhook URL.',
                'status' => 'warning'
            ], 200);
        }
        
        return Response::json([
            'message' => 'Restock workflow triggered via n8n',
            'status' => 'success',
            'product_id' => $productId,
            'webhook_results' => $result
        ]);
    }
}
