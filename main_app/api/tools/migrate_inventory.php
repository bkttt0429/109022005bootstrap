<?php
require_once __DIR__ . '/../api_bootstrap.php';

try {
    $pdo = getDB();
    
    // Add new columns if they don't exist
    $cols = [
        'min_stock_level' => 'INT DEFAULT 10',
        'lead_time_days' => 'INT DEFAULT 3',
        'avg_daily_sales' => 'DECIMAL(10, 2) DEFAULT 0.0'
    ];

    foreach ($cols as $col => $type) {
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN $col $type");
            echo "Added column $col\n";
        } catch (Exception $e) {
            echo "Skipping $col (likely exists): " . $e->getMessage() . "\n";
        }
    }

    // Create movements table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS inventory_movements (
            id SERIAL PRIMARY KEY,
            product_id INT REFERENCES products(id),
            quantity_change INT NOT NULL,
            reason VARCHAR(50),
            reference_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Inventory movements table ready.\n";

    // Seed some random data for lead_time and daily_sales to test priority
    $pdo->exec("UPDATE products SET lead_time_days = floor(random() * 7 + 1), avg_daily_sales = random() * 5 WHERE lead_time_days IS NULL OR lead_time_days = 3");
    
    echo "Migration complete.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
