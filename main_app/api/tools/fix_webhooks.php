<?php
require_once __DIR__ . '/../api_bootstrap.php';

try {
    $pdo = getDB();
    
    // Check if table exists
    $stmt = $pdo->query("SELECT * FROM webhook_configs");
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current Webhook Configs:\n";
    print_r($configs);
    
    // Check for RESTOCK_REQUEST
    $found = false;
    foreach ($configs as $config) {
        if ($config['event_topic'] === 'RESTOCK_REQUEST') {
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        echo "\nRESTOCK_REQUEST not found. Adding default config...\n";
        $stmt = $pdo->prepare("INSERT INTO webhook_configs (event_topic, n8n_webhook_url, description, is_active) VALUES (?, ?, ?, TRUE)");
        $stmt->execute(['RESTOCK_REQUEST', 'http://localhost:5678/webhook/restock-trigger', 'ERP Smart Restock']);
        echo "Added successfully!\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
