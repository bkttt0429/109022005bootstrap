<?php
require_once __DIR__ . '/db.php';

function get_cart($pdo, $session){
    if(isset($_SESSION['user_id'])){
        $stmt = $pdo->prepare('SELECT product_id, quantity FROM carts WHERE user_id = :u');
        $stmt->execute(['u'=>$_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare('SELECT product_id, quantity FROM carts WHERE session_id = :s');
        $stmt->execute(['s'=>$session]);
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $cart = [];
    foreach($rows as $r){
        $cart[(int)$r['product_id']] = (int)$r['quantity'];
    }
    return $cart;
}
