<?php
namespace Controllers;

use Core\Response;
use Services\OrderService;

class OrdersController {
    private $service;

    public function __construct() {
        // In a full framework we'd use DI, here we just instantiate
        $pdo = getDB(); 
        $this->service = new OrderService($pdo);
        
        // Ensure Admin (simulating admin_gate.php)
        // require_once dirname(__DIR__, 2) . '/admin_gate.php';
        // Note: admin_gate.php usually checks session. We should wrap it in Middleware later.
    }

    public function index() {
        $orders = $this->service->getAllOrders();
        Response::json($orders);
    }

    public function show($id) {
        $order = $this->service->getOrderById($id);
        if (!$order) {
            Response::error('Order not found', 404);
        }
        Response::json($order);
    }

    public function updateStatus($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        $status = $input['status'] ?? null;

        if (!$status) {
            Response::error('Missing status');
        }

        $this->service->updateStatus($id, $status);
        Response::json(['success' => true]);
    }

    // Legacy support for POST to /orders/update-status
    public function updateStatusLegacy() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $status = $input['status'] ?? null;

        if (!$id || !$status) {
            Response::error('Missing ID or status');
        }

        $this->service->updateStatus($id, $status);
        Response::json(['success' => true]);
    }
}
