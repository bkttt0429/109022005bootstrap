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
}
