<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

$pdo = getDB();

// ensure we have a CSRF token in the session
if(!isset($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$method = $_SERVER['REQUEST_METHOD'];

if($method === 'GET'){
    $user = null;
    if(isset($_SESSION['user_id'])){
        $stmt = $pdo->prepare('SELECT id, email, name FROM users WHERE id = :id');
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    echo json_encode(['success'=>true, 'user'=>$user, 'csrf'=>$_SESSION['csrf_token']]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? null;

function respond($payload, $status=200){ http_response_code($status); echo json_encode($payload); exit; }

// require csrf for mutating actions
if(in_array($action, ['login','register','logout'])){
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf'] ?? null);
    if(!$token || !hash_equals($_SESSION['csrf_token'], $token)){
        respond(['success'=>false,'error'=>'invalid_csrf'],403);
    }
}

try{
    if($action === 'register'){
        $email = trim($input['email'] ?? '');
        $pass = $input['password'] ?? '';
        $name = trim($input['name'] ?? null);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(['success'=>false,'error'=>'invalid_email'],400);
        if(strlen($pass) < 6) respond(['success'=>false,'error'=>'password_too_short'],400);

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (email,name,password_hash) VALUES (:e,:n,:p)');
        $stmt->execute(['e'=>$email,'n'=>$name,'p'=>$hash]);
        $uid = (int)$pdo->lastInsertId();
        $_SESSION['user_id'] = $uid;

        // merge session cart into user cart if present
        if(session_id()){
            $sid = session_id();
            // move rows: for each product in session, add to user cart
            $rows = $pdo->prepare('SELECT product_id, quantity FROM carts WHERE session_id = :s');
            $rows->execute(['s'=>$sid]);
            foreach($rows->fetchAll(PDO::FETCH_ASSOC) as $r){
                $p = (int)$r['product_id']; $q = (int)$r['quantity'];
                $up = $pdo->prepare('INSERT INTO carts (user_id, product_id, quantity) VALUES (:u,:p,:q) ON DUPLICATE KEY UPDATE quantity = quantity + :q2');
                $up->execute(['u'=>$uid,'p'=>$p,'q'=>$q,'q2'=>$q]);
            }
            // remove session-only rows
            $del = $pdo->prepare('DELETE FROM carts WHERE session_id = :s'); $del->execute(['s'=>$sid]);
        }

        respond(['success'=>true, 'user'=>['id'=>$uid,'email'=>$email,'name'=>$name]]);
    }

    if($action === 'login'){
        $email = trim($input['email'] ?? '');
        $pass = $input['password'] ?? '';
        if(!$email || !$pass) respond(['success'=>false,'error'=>'credentials_required'],400);

        $stmt = $pdo->prepare('SELECT id, password_hash, name, email FROM users WHERE email = :e');
        $stmt->execute(['e'=>$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$row || !password_verify($pass, $row['password_hash'])){
            respond(['success'=>false,'error'=>'invalid_credentials'],401);
        }

        $_SESSION['user_id'] = (int)$row['id'];

        // merge session cart into user cart
        if(session_id()){
            $sid = session_id();
            $rows = $pdo->prepare('SELECT product_id, quantity FROM carts WHERE session_id = :s');
            $rows->execute(['s'=>$sid]);
            foreach($rows->fetchAll(PDO::FETCH_ASSOC) as $r){
                $p = (int)$r['product_id']; $q = (int)$r['quantity'];
                $up = $pdo->prepare('INSERT INTO carts (user_id, product_id, quantity) VALUES (:u,:p,:q) ON DUPLICATE KEY UPDATE quantity = quantity + :q2');
                $up->execute(['u'=>$_SESSION['user_id'],'p'=>$p,'q'=>$q,'q2'=>$q]);
            }
            $del = $pdo->prepare('DELETE FROM carts WHERE session_id = :s'); $del->execute(['s'=>$sid]);
        }

        respond(['success'=>true, 'user'=>['id'=>$row['id'],'email'=>$row['email'],'name'=>$row['name']]]);
    }

    if($action === 'logout'){
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
        respond(['success'=>true]);
    }

    respond(['success'=>false,'error'=>'unknown_action'],400);

}catch(Exception $e){ respond(['success'=>false,'error'=>$e->getMessage()],500); }
