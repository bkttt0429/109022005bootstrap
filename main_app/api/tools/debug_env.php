<?php
require_once 'api_bootstrap.php';
header('Content-Type: text/plain');
echo "DB_TYPE Constant: " . (defined('DB_TYPE') ? DB_TYPE : 'NOT DEFINED') . "\n";
echo "DB_HOST Constant: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "\n";
echo "ENV DB_TYPE: " . ($_ENV['DB_TYPE'] ?? 'NOT SET') . "\n";
echo "SERVER DB_TYPE: " . ($_SERVER['DB_TYPE'] ?? 'NOT SET') . "\n";
echo "ENV File Path: " . __DIR__ . "/.env\n";
echo "ENV File Exists: " . (file_exists(__DIR__ . "/.env") ? "YES" : "NO") . "\n";
if (file_exists(__DIR__ . "/.env")) {
    echo "ENV File Content (first line): " . head(file(__DIR__ . "/.env"), 1)[0] . "\n";
}
function head($arr, $n) { return array_slice($arr, 0, $n); }
?>
