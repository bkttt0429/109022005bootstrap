<?php
require_once __DIR__ . '/../api_bootstrap.php';

try {
    $pdo = getDB();
    
    // Create reviews table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS reviews (
        id SERIAL PRIMARY KEY,
        product_id INT REFERENCES products(id) ON DELETE CASCADE,
        user_id INT REFERENCES users(id) ON DELETE CASCADE,
        rating INT CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    // Adjust SERIAL for MySQL if needed
    $dbType = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($dbType === 'mysql') {
        $sql = "CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT,
            user_id INT,
            rating INT,
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
    }

    $pdo->exec($sql);
    echo "Reviews table checked/created successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
