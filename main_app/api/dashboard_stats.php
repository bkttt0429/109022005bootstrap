<?php
require_once 'admin_gate.php';
require_once 'db.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

try {
    $pdo = getDB();

    // 1. Monthly Sales (Mocked logic based on random orders for demo, or real aggregation if data exists)
    // Since we just started creating orders, let's aggregate real orders if recent, else mix with mock.
    // For now, let's just return what the UI expects but simulated from "backend".
    
    // Real Stats: System Status
    $status = [
        'server' => '正常',
        'db' => '正常',
        'user' => 'Admin' // Should get from key or session
    ];

    // Real Stats: Counts
    $productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $orderCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10")->fetchColumn();

    // Chart Data (Simulated for visualization as we don't have historical data)
    $chartData = [
        ['name' => '1月', 'sales' => 4000, 'inventory' => 2400],
        ['name' => '2月', 'sales' => 3000, 'inventory' => 1398],
        ['name' => '3月', 'sales' => 2000, 'inventory' => 9800],
        ['name' => '4月', 'sales' => 2780, 'inventory' => 3908],
        ['name' => '5月', 'sales' => 1890, 'inventory' => 4800],
        ['name' => '6月', 'sales' => 2390, 'inventory' => 3800],
        ['name' => '7月', 'sales' => 3490, 'inventory' => 4300],
    ];

    echo json_encode([
        'chartData' => $chartData,
        'stats' => [
            'products' => $productCount,
            'orders' => $orderCount,
            'lowStock' => $lowStockCount,
            'system' => $status
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
