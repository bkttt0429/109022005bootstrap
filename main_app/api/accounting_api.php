<?php
require_once 'admin_gate.php';
require_once 'db.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

/**
 * Accounting API
 * Provides financial metrics: Revenue, Expenses, and Profit
 */

try {
    $pdo = getDB();

    // 1. Calculate Revenue (Completed Orders Only)
    $revenueStmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'Completed'");
    $totalRevenue = (float)$revenueStmt->fetchColumn() ?: 0;

    // 2. Simulate Expenses (Until a real expense table is added)
    // Formula: Fixed Costs + 40% of Revenue as Variable Costs
    $fixedCosts = 5000; 
    $variableCostRate = 0.45;
    $totalExpenses = $fixedCosts + ($totalRevenue * $variableCostRate);
    
    // 3. Monthly Aggregation (Group by month)
    // We'll use the last 6 months
    $monthlyStmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(CASE WHEN status = 'Completed' THEN total_amount ELSE 0 END) as revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ");
    $monthlyRaw = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

    $chartData = [];
    // Ensure we have 6 months even if data is missing
    $current = new DateTime();
    $current->modify('-5 months');
    
    for ($i = 0; $i < 6; $i++) {
        $mKey = $current->format('Y-m');
        $rev = 0;
        foreach ($monthlyRaw as $row) {
            if ($row['month'] === $mKey) {
                $rev = (float)$row['revenue'];
                break;
            }
        }
        
        // Simulated expenses for the chart
        $exp = ($rev > 0) ? ($rev * 0.5 + 800) : 1000;

        $chartData[] = [
            'name' => $current->format('n月'),
            'revenue' => round($rev, 2),
            'expenses' => round($exp, 2),
            'profit' => round($rev - $exp, 2)
        ];
        $current->modify('+1 month');
    }

    // 4. Recent Transactions (Latest 5 orders)
    $recentStmt = $pdo->query("
        SELECT id, order_number, total_amount, status, created_at 
        FROM orders 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentTransactions = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    $profit = $totalRevenue - $totalExpenses;
    $margin = ($totalRevenue > 0) ? ($profit / $totalRevenue) * 100 : 0;

    echo json_encode([
        'summary' => [
            'totalRevenue' => round($totalRevenue, 2),
            'totalExpenses' => round($totalExpenses, 2),
            'grossProfit' => round($profit, 2),
            'netMargin' => round($margin, 2) . '%'
        ],
        'chartData' => $chartData,
        'recentTransactions' => $recentTransactions
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
