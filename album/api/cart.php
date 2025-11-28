<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/db.php';

$pdo = getDB();
$session = session_id();

// helper to output current cart
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
    foreach($rows as $r){ $cart[(int)$r['product_id']] = (int)$r['quantity']; }
    return $cart;
}

$method = $_SERVER['REQUEST_METHOD'];

if($method === 'GET'){
    echo json_encode(['success'=>true, 'cart'=>get_cart($pdo,$session)]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = isset($input['action']) ? $input['action'] : ($_GET['action'] ?? null);

if(!$action){
    http_response_code(400);
    echo json_encode(['success'=>false, 'error'=>'action required']);
    exit;
}

if(in_array($action, ['add','update','remove','clear'])){
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf'] ?? null);
    if(!$token || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'],$token)){
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'invalid_csrf']);
        exit;
    }
}

try{
    if($action === 'add'){
        $product_id = (int)($input['product_id'] ?? 0);
        $qty = max(1, (int)($input['quantity'] ?? 1));
        if(!$product_id){ throw new Exception('product_id required'); }

        if(isset($_SESSION['user_id'])){
            $stmt = $pdo->prepare('INSERT INTO carts (user_id, product_id, quantity) VALUES (:u,:p,:q) ON DUPLICATE KEY UPDATE quantity = quantity + :q2');
            $stmt->execute(['u'=>$_SESSION['user_id'],'p'=>$product_id,'q'=>$qty,'q2'=>$qty]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO carts (session_id, product_id, quantity) VALUES (:s,:p,:q) ON DUPLICATE KEY UPDATE quantity = quantity + :q2');
            $stmt->execute(['s'=>$session,'p'=>$product_id,'q'=>$qty,'q2'=>$qty]);
        }
        echo json_encode(['success'=>true, 'cart'=>get_cart($pdo,$session)]);
        exit;
    }

    if($action === 'update'){
        $product_id = (int)($input['product_id'] ?? 0);
        $qty = max(0, (int)($input['quantity'] ?? 0));
        if(!$product_id){ throw new Exception('product_id required'); }

        if(isset($_SESSION['user_id'])){
            if($qty <= 0){
                $stmt = $pdo->prepare('DELETE FROM carts WHERE user_id = :u AND product_id = :p');
                $stmt->execute(['u'=>$_SESSION['user_id'],'p'=>$product_id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO carts (user_id, product_id, quantity) VALUES (:u,:p,:q) ON DUPLICATE KEY UPDATE quantity = :q');
                $stmt->execute(['u'=>$_SESSION['user_id'],'p'=>$product_id,'q'=>$qty]);
            }
        } else {
            if($qty <= 0){
                $stmt = $pdo->prepare('DELETE FROM carts WHERE session_id = :s AND product_id = :p');
                $stmt->execute(['s'=>$session,'p'=>$product_id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO carts (session_id, product_id, quantity) VALUES (:s,:p,:q) ON DUPLICATE KEY UPDATE quantity = :q');
                $stmt->execute(['s'=>$session,'p'=>$product_id,'q'=>$qty]);
            }
        }

        echo json_encode(['success'=>true, 'cart'=>get_cart($pdo,$session)]);
        exit;
    }

    if($action === 'remove'){
        $product_id = (int)($input['product_id'] ?? 0);
        if(!$product_id){ throw new Exception('product_id required'); }
        if(isset($_SESSION['user_id'])){
            $stmt = $pdo->prepare('DELETE FROM carts WHERE user_id = :u AND product_id = :p');
            $stmt->execute(['u'=>$_SESSION['user_id'],'p'=>$product_id]);
        } else {
            $stmt = $pdo->prepare('DELETE FROM carts WHERE session_id = :s AND product_id = :p');
            $stmt->execute(['s'=>$session,'p'=>$product_id]);
        }
        echo json_encode(['success'=>true, 'cart'=>get_cart($pdo,$session)]);
        exit;
    }

    if($action === 'clear'){
        if(isset($_SESSION['user_id'])){
            $stmt = $pdo->prepare('DELETE FROM carts WHERE user_id = :u');
            $stmt->execute(['u'=>$_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare('DELETE FROM carts WHERE session_id = :s');
            $stmt->execute(['s'=>$session]);
        }
        echo json_encode(['success'=>true, 'cart'=>[]]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'unknown action']);

}catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
