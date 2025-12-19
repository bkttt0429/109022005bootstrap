<?php
require_once 'db.php';

try {
    $pdo = getDB();
    echo "Connected to database.\n";

    // Add updated_at to orders if not exists
    // MySQL 5.7+ supports generated columns or just modifying. 
    // Easiest is to try adding it and catch exception if it exists, or check first.
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'updated_at'");
    if ($stmt->rowCount() == 0) {
        $sql = "ALTER TABLE orders ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $pdo->exec($sql);
        echo "Added updated_at column to orders table.\n";
    } else {
        echo "updated_at column already exists.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
