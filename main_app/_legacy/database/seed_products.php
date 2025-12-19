<?php
require_once '../api/db.php';

try {
    $pdo = getDB();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h3>Re-initializing Products Table...</h3>";

    // 1. Drop old table to ensure clean schema (resolves 'name' vs 'title' conflicts)
    // Note: In a production environment, we would migrating, not dropping.
    $pdo->exec("DROP TABLE IF EXISTS products");
    
    // 2. Create Table with correct columns for our ERP
    $sql = "CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        cost DECIMAL(10,2) DEFAULT 0.00,
        stock_quantity INT DEFAULT 0,
        sku VARCHAR(50),
        category VARCHAR(100),
        image_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'products' created.<br>";

    // 3. Insert Data
    // Common Logic found in Bootstrap examples + ERP items
    $products = [
        [
            'name' => 'Professional Camera Kit',
            'desc' => 'High-end DSLR camera with 24-70mm lens, perfect for professional photography.',
            'price' => 45000,
            'stock' => 15,
            'img' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
        ],
        [
            'name' => 'Premium Office Chair',
            'desc' => 'Ergonomic mesh chair with lumbar support for long working hours.',
            'price' => 8500,
            'stock' => 50,
            'img' => 'https://images.unsplash.com/photo-1592078615290-033ee584e267?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
        ],
        [
            'name' => 'Mechanical Keyboard',
            'desc' => 'RGB backlit mechanical keyboard with Cherry MX Blue switches.',
            'price' => 3200,
            'stock' => 100,
            'img' => 'https://mechanicalkeyboards.com/cdn/shop/files/17173-SWCS4-Ducky-Origin-Vintage.jpg?v=1728311476&width=750'
        ],
        [
            'name' => 'Wireless Noise-Canceling Headphones',
            'desc' => 'Over-ear headphones with active noise cancellation and 30-hour battery life.',
            'price' => 7900,
            'stock' => 30,
            'img' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
        ],
        [
            'name' => 'Smart Watch Series 5',
            'desc' => 'Advanced health monitoring, GPS, and water resistance up to 50m.',
            'price' => 12000,
            'stock' => 25,
            'img' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
        ],
        [
            'name' => 'Designer Coffee Mug',
            'desc' => 'Ceramic mug with minimalist design, microwave safe.',
            'price' => 350,
            'stock' => 200,
            'img' => 'https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
        ],
        [
            'name' => '4K Monitor 27-inch',
            'desc' => 'IPS panel with 144Hz refresh rate and HDR support.',
            'price' => 15000,
            'stock' => 10,
            'img' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
        ],
        [
            'name' => 'Vintage Film Camera',
            'desc' => 'Restored 35mm film camera for retro photography enthusiasts.',
            'price' => 5500,
            'stock' => 5,
            'img' => 'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock_quantity, image_url) VALUES (:name, :desc, :price, :stock, :img)");

    foreach ($products as $p) {
        $stmt->execute([
            ':name' => $p['name'],
            ':desc' => $p['desc'],
            ':price' => $p['price'],
            ':stock' => $p['stock'],
            ':img' => $p['img']
        ]);
    }
    
    echo "Successfully inserted " . count($products) . " products.<br>";
    echo "<a href='../products.php'>Go to Products Page</a>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
