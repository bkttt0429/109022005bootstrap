<?php
require_once 'db.php';
try {
    $pdo = getDB();
    echo "\n--- Orders Table ---\n";
    $stmt = $pdo->query("DESCRIBE orders");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
