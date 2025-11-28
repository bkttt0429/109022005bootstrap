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
        ensureSchema($pdo);
        return $pdo;
    }catch(Exception $e){
        http_response_code(500);
        echo json_encode(['error' => 'DB connection failed', 'message' => $e->getMessage()]);
        exit;
    }
}

// Lightweight schema guard: if someone forgets to import schema.sql, create the
// minimal tables so cart.php/auth.php won't 500 with “table doesn't exist”.
function ensureSchema(PDO $pdo){
    static $checked = false;
    if($checked) return;
    $checked = true;

    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        name VARCHAR(255) NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS carts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        session_id VARCHAR(255) NULL,
        product_id INT UNSIGNED NOT NULL,
        quantity INT UNSIGNED NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT uc_cart_user_product UNIQUE (user_id, product_id),
        CONSTRAINT uc_cart_session_product UNIQUE (session_id, product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS inventory_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        session_id VARCHAR(255) NULL,
        product_id INT UNSIGNED NOT NULL DEFAULT 0,
        action VARCHAR(32) NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_inventory_user (user_id),
        KEY idx_inventory_session (session_id),
        KEY idx_inventory_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    // Seed a demo user (password: password) for quick testing when schema.sql
    // hasn't been run manually.
    $pdo->exec("INSERT INTO users (email, name, password_hash) VALUES ('demo@example.com','Demo User','$2y$10$sF0rtCwAd3C3BjiMo0VwJeE9suNr97W7ikhkEjvq9ZBAzF.vS8HXe') ON DUPLICATE KEY UPDATE email=email");
}
