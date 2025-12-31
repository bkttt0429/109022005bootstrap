<?php
namespace Services;

use PDO;

class ProductService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllProducts() {
        // Safer query consistent across SQL modes
        $sql = "
            SELECT p.*, 
            (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE product_id = p.id) as sales_volume
            FROM products p
            ORDER BY sales_volume DESC, p.id DESC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createProduct($data) {
        $sql = "INSERT INTO products (name, sku, category, price, stock_quantity, image_url, description) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['sku'] ?? null,
            $data['category'] ?? 'General',
            $data['price'],
            $data['stock_quantity'] ?? 0,
            $data['image_url'] ?? null,
            $data['description'] ?? null
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateProduct($id, $data) {
        $sql = "UPDATE products SET 
                name = ?, 
                sku = ?, 
                category = ?, 
                price = ?, 
                stock_quantity = ?, 
                image_url = ?, 
                description = ? 
                WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['sku'] ?? null,
            $data['category'] ?? 'General',
            $data['price'],
            $data['stock_quantity'] ?? 0,
            $data['image_url'] ?? null,
            $data['description'] ?? null,
            $id
        ]);
    }

    public function deleteProduct($id) {
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
