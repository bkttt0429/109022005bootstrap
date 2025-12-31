<?php
namespace Controllers;

use Core\Response;
use Services\ProductService;

class ProductsController {
    private $service;

    public function __construct() {
        $this->service = new ProductService(getDB());
    }

    public function index() {
        $products = $this->service->getAllProducts();
        Response::json($products);
    }

    public function show($id) {
        $product = $this->service->getProductById($id);
        if (!$product) {
            Response::error('Product not found', 404);
        }
        Response::json($product);
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['name']) || !isset($data['price'])) {
            Response::error('Invalid data', 400);
        }
        $id = $this->service->createProduct($data);
        Response::json(['message' => 'Product created', 'id' => $id]);
    }

    public function update($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            Response::error('Invalid data', 400);
        }
        $success = $this->service->updateProduct($id, $data);
        if ($success) {
            Response::json(['message' => 'Product updated']);
        } else {
            Response::error('Update failed', 500);
        }
    }

    public function destroy($id) {
        $success = $this->service->deleteProduct($id);
        if ($success) {
            Response::json(['message' => 'Product deleted']);
        } else {
            Response::error('Delete failed', 500);
        }
    }
}
