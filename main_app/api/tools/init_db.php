<?php
// Initialize Database Script
// Run this to set up the database structure and initial data

require_once 'db.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

echo "Starting Database Initialization...\n";

// 1. Attempt to create database if it doesn't exist
try {
    // Connect without DB name first
    $dsn_root = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
    $pdo_root = new PDO($dsn_root, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Check if DB exists
    $stmt = $pdo_root->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    if (!$stmt->fetchColumn()) {
        echo "Database '" . DB_NAME . "' does not exist. Creating...\n";
        $pdo_root->exec("CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Database created successfully.\n";
    } else {
        echo "Database '" . DB_NAME . "' already exists.\n";
    }
} catch (Exception $e) {
    die("CRITICAL ERROR: Could not connect to MySQL to check/create database. Ensure XAMPP MySQL is running.\nError: " . $e->getMessage() . "\n");
}

// 2. Connect to the shop_db
try {
    $pdo = getDB();
    echo "Connected to '" . DB_NAME . "'.\n";
} catch (Exception $e) {
    // Should not happen if step 1 worked, but good to check
    die("Error connecting to database: " . $e->getMessage());
}

// 3. Create Tables
try {
    // USERS Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table 'users' checked/created.\n";

    // PRODUCTS Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        category VARCHAR(50),
        image_url VARCHAR(255),
        stock_quantity INT DEFAULT 100,
        sku VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table 'products' checked/created.\n";

    // CARTS Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS carts (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table 'carts' checked/created.\n";

    // INVENTORY LOGS Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        session_id VARCHAR(255) NULL,
        product_id INT UNSIGNED NOT NULL,
        action VARCHAR(32) NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_inventory_user (user_id),
        KEY idx_inventory_session (session_id),
        KEY idx_inventory_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table 'inventory_logs' checked/created.\n";

    // ORDERS Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        status VARCHAR(32) NOT NULL DEFAULT 'Pending',
        order_number VARCHAR(64) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table 'orders' checked/created.\n";

    // ORDER ITEMS Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        quantity INT UNSIGNED NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        subtotal DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table 'order_items' checked/created.\n";

    // REVIEWS Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        product_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table 'reviews' checked/created.\n";

} catch (PDOException $e) {
    die("Database Schema Error: " . $e->getMessage());
}

// 4. Run Seed Data if table empty
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    if ($stmt->fetchColumn() == 0) {
        echo "Products table empty. Running seed_data.php...\n";
        // We can't easily include seed_data.php because it outputs JSON.
        // We will call it via CURL or just let the user know.
        // Or better, we can copy the logic, but that's duplicating code.
        // Let's just instruct the user.
        echo "Note: Tables created. To generate fake data, please run seed_data.php separately.\n";
        echo "Url: seed_data.php\n";
    } else {
        echo "Data already exists. Skipping seed.\n";
    }
} catch (PDOException $e) {
    echo "Error checking data: " . $e->getMessage();
}

echo "\nInitialization Complete! You can now use the application.\n";
?>
