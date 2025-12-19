<?php
require_once 'db.php';

// Check Authentication (Simple check for demo, ideally check session/admin role)
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Access Denied");
}

$type = $_GET['type'] ?? 'orders';
$filename = "export_{$type}_" . date('Ymd') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$pdo = getDB();
$output = fopen('php://output', 'w');

try {
    if ($type === 'orders') {
        // Headers
        fputcsv($output, ['Order ID', 'Order Number', 'User Name', 'Total Amount', 'Status', 'Created At']);
        
        // Data
        $sql = "SELECT o.id, o.order_number, u.name as user_name, o.total_amount, o.status, o.created_at 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                ORDER BY o.created_at DESC";
        $stmt = $pdo->query($sql);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
    } elseif ($type === 'products') {
        // Headers
        fputcsv($output, ['ID', 'Name', 'Price', 'Stock', 'Category', 'Created At']);
        
        // Data
        $sql = "SELECT id, name, price, stock_quantity, category, created_at FROM products ORDER BY id ASC";
        $stmt = $pdo->query($sql);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
    }
} catch (Exception $e) {
    // On error, the CSV might contain the error message, which is acceptable for simple export
    fputcsv($output, ['Error', $e->getMessage()]);
}

fclose($output);
exit;
?>
