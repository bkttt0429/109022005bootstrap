<?php
require_once 'db.php';
try {
    $pdo = getDB();
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo $col['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
