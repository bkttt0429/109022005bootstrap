<?php
// Simple DB helper using PDO. Edit constants below to match your XAMPP MySQL settings.
// Simple DB helper using PDO. Edit constants below to match your XAMPP MySQL settings.
define('DB_TYPE', $_ENV['DB_TYPE'] ?? (getenv('DB_TYPE') ?: 'mysql'));
define('DB_HOST', $_ENV['DB_HOST'] ?? (getenv('DB_HOST') ?: '127.0.0.1'));
define('DB_PORT', $_ENV['DB_PORT'] ?? (getenv('DB_PORT') ?: (DB_TYPE === 'pgsql' ? '5432' : '3306')));
define('DB_NAME', $_ENV['DB_NAME'] ?? (getenv('DB_NAME') ?: 'shop_db'));
define('DB_USER', $_ENV['DB_USER'] ?? (getenv('DB_USER') ?: 'root'));
define('DB_PASS', $_ENV['DB_PASS'] ?? (getenv('DB_PASS') ?: ''));

function getDB(){
    static $pdo = null;
    if($pdo) return $pdo;

    if (DB_TYPE === 'pgsql') {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    } else {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    }

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed', 'details' => $e->getMessage()]);
        exit;
    }
}
