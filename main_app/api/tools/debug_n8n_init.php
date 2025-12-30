<?php
require_once 'api_bootstrap.php';

header('Content-Type: text/plain');

echo "=== Environment Variable Check ===\n";
echo "Parsed N8N_API_KEY from \$_ENV: " . ($_ENV['N8N_API_KEY'] ?? 'NOT SET') . "\n";
echo "Parsed N8N_API_KEY from \$_SERVER: " . ($_SERVER['N8N_API_KEY'] ?? 'NOT SET') . "\n";
echo "getenv('N8N_API_KEY'): " . getenv('N8N_API_KEY') . "\n";

echo "\n=== Raw .env File Content ===\n";
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo file_get_contents($envPath);
} else {
    echo ".env file not found at $envPath\n";
}

echo "\n\n=== Database Check ===\n";
try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM system_settings WHERE setting_key = 'n8n_api_key'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "Found key in DB: " . substr($row['setting_value'], 0, 5) . "...\n";
    } else {
        echo "Key NOT found in DB.\n";
    }
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}

echo "\n=== Manual Sync Attempt ===\n";
if (!empty($_ENV['N8N_API_KEY'])) {
    $key = $_ENV['N8N_API_KEY'];
    echo "Attempting to sync key: " . substr($key, 0, 5) . "...\n";
    try {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('n8n_api_key', ?) ON CONFLICT (setting_key) DO UPDATE SET setting_value = ?");
        $stmt->execute([$key, $key]);
        echo "Sync successful.\n";
    } catch (Exception $e) {
        echo "Sync failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "Skipping sync, ENV is empty.\n";
}
