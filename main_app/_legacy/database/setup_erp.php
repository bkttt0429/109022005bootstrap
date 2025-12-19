<?php
// Setup Script for ERP Database
require_once '../api/db.php';

try {
    // Assuming db.php defines a function getDB() or returns a PDO object
    // Based on common patterns in this project, typically: function getDB() { ... return $pdo; }
    if (function_exists('getDB')) {
        $pdo = getDB();
    } else {
        // Fallback: Try to connect manually if db.php is just variables
        $host = 'localhost';
        $db   = '109022005bootstrap'; // Guessing/Default
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass);
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read Schema
    $sql = file_get_contents('erp_schema.sql');
    
    // Execute Schema (Split by ;)
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }

    // Try to update products table
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN stock_quantity INT DEFAULT 0");
        echo "Added stock_quantity to products.<br>";
    } catch (Exception $e) { /* Ignore if exists */ }

    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN cost DECIMAL(10,2) DEFAULT 0.00");
        echo "Added cost to products.<br>";
    } catch (Exception $e) { /* Ignore if exists */ }

    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN sku VARCHAR(50)");
        echo "Added sku to products.<br>";
    } catch (Exception $e) { /* Ignore if exists */ }

    echo "ERP Database Schema setup successfully!";

} catch (Exception $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
