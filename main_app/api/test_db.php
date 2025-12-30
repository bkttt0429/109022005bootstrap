<?php
header('Content-Type: text/plain');
echo "PHP Version: " . phpversion() . "\n";
echo "Loaded Extensions: " . implode(', ', get_loaded_extensions()) . "\n";

try {
    require_once 'api_bootstrap.php';
    echo "Environment Loaded. DB_TYPE: " . DB_TYPE . "\n";
    $pdo = getDB();
    echo "Database Connected Successfully!\n";
    
    $stmt = $pdo->query("SELECT version()");
    echo "DB Version: " . $stmt->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
