<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cart_helper.php';
require_once __DIR__ . '/products.php';

$pdo = getDB();
$session = session_id();
$action = $_GET['action'] ?? 'cart_csv';

if($action === 'cart_csv'){
    $cart = get_cart($pdo, $session);
    $products = shop_products();

    $rows = [];
    $total = 0;
    foreach($cart as $pid=>$qty){
        if(!isset($products[$pid])) continue;
        $p = $products[$pid];
        $subtotal = $p['price'] * $qty;
        $rows[] = [$p['title'], $qty, $p['price'], $subtotal];
        $total += $subtotal;
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cart-report.csv"');

    $out = fopen('php://output','w');
    fputcsv($out, ['商品', '數量', '單價', '小計']);
    foreach($rows as $r){ fputcsv($out, $r); }
    fputcsv($out, ['總計','','', $total]);
    fclose($out);
    exit;
}

if($action === 'inventory_log'){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventory-log.csv"');

    $stmt = $pdo->prepare('SELECT product_id, action, quantity, created_at FROM inventory_logs WHERE (user_id = :u OR (user_id IS NULL AND session_id = :s)) ORDER BY created_at DESC LIMIT 500');
    $stmt->execute(['u'=>$_SESSION['user_id'] ?? null, 's'=>$session]);

    $products = shop_products();

    $out = fopen('php://output','w');
    fputcsv($out, ['商品', '動作', '數量', '時間']);
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $title = $products[$row['product_id']]['title'] ?? ('#'.$row['product_id']);
        fputcsv($out, [$title, $row['action'], $row['quantity'], $row['created_at']]);
    }
    fclose($out);
    exit;
}

http_response_code(400);
echo 'unknown report type';
