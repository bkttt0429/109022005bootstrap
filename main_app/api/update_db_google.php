<?php
require_once 'db.php';

try {
    $pdo = getDB();
    echo "Connected to database.\n";

    // Add google_id column
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER email");
        echo "Added google_id column.\n";
    } catch (PDOException $e) {
        echo "google_id column might already exist (Error: " . $e->getMessage() . ")\n";
    }

    // Add avatar_url column
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) NULL AFTER name");
        echo "Added avatar_url column.\n";
    } catch (PDOException $e) {
        echo "avatar_url column might already exist (Error: " . $e->getMessage() . ")\n";
    }

    echo "Database update completed.\n";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
?>
