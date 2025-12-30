<?php
require_once 'api_bootstrap.php';
require_once 'admin_gate.php';

try {
    $pdo = getDB();

    // 1. System Status
    $status = [
        'server' => '正常',
        'db' => '正常'
    ];

    // 2. Real Stats: Counts
    $productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $orderCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10")->fetchColumn();

    // 3. Chart Data (Simulated for visualization)
    $chartData = [
        ['name' => '1月', 'sales' => 4000, 'inventory' => 2400],
        ['name' => '2月', 'sales' => 3000, 'inventory' => 1398],
        ['name' => '3月', 'sales' => 2000, 'inventory' => 9800],
        ['name' => '4月', 'sales' => 2780, 'inventory' => 3908],
        ['name' => '5月', 'sales' => 1890, 'inventory' => 4800],
        ['name' => '6月', 'sales' => 2390, 'inventory' => 3800],
        ['name' => '7月', 'sales' => 3490, 'inventory' => 4300],
    ];

    sendResponse([
        'chartData' => $chartData,
        'stats' => [
            'products' => $productCount,
            'orders' => $orderCount,
            'lowStock' => $lowStockCount,
            'system' => $status
        ]
    ]);

} catch (Exception $e) {
    sendResponse(['error' => $e->getMessage()], 500);
}
