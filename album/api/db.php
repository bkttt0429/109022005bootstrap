<?php
// Simple DB helper using PDO. Edit constants below to match your XAMPP MySQL settings.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'shop_db');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB(){
    static $pdo = null;
    if($pdo) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try{
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
        return $pdo;
    }catch(Exception $e){
        http_response_code(500);
        echo json_encode(['error' => 'DB connection failed', 'message' => $e->getMessage()]);
        exit;
    }
}
