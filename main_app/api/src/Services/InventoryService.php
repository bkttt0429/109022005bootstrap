<?php
namespace Services;

use PDO;
use Exception;

class InventoryService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function getInventoryStatus() {
        $sql = "
            SELECT 
                id, name, sku, stock_quantity, min_stock_level, 
                lead_time_days, avg_daily_sales, category,
                CASE 
                    WHEN stock_quantity = 0 THEN 100
                    WHEN avg_daily_sales = 0 THEN 0
                    ELSE ROUND((avg_daily_sales * lead_time_days) / NULLIF(stock_quantity, 0), 2)
                END as priority_score
            FROM products
            ORDER BY priority_score DESC, stock_quantity ASC
        ";
        
        $stmt = $this->pdo->query($sql);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function($p) {
            $p['stock_status'] = $this->calculateStockStatus($p);
            return $p;
        }, $products);
    }

    private function calculateStockStatus($p) {
        if ($p['stock_quantity'] <= 0) return 'Out of Stock';
        if ($p['stock_quantity'] <= $p['min_stock_level']) return 'Critical';
        $safetyBuffer = $p['avg_daily_sales'] * $p['lead_time_days'];
        if ($p['stock_quantity'] <= $safetyBuffer) return 'Warning';
        return 'Healthy';
    }

    public function processInbound($productId, $quantity, $reason = 'RESTOCK', $reference = null) {
        $this->pdo->beginTransaction();
        try {
            // 強制轉為整數，避免資料庫欄位類型錯誤
            $quantity = (int)round((float)$quantity);
            
            // Update stock
            $stmt = $this->pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
            $stmt->execute([$quantity, $productId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Product ID $productId not found.");
            }

            // Log movement
            $logStmt = $this->pdo->prepare("
                INSERT INTO inventory_movements (product_id, quantity_change, reason, reference_id)
                VALUES (?, ?, ?, ?)
            ");
            $logStmt->execute([$productId, $quantity, $reason, $reference]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
