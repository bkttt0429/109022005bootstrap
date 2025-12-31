<?php
// Force logging to a specific location we know is writable
$logFile = 'D:/xampp/htdocs/109022005bootstrap/main_app/super_debug.txt';
$content = date('Y-m-d H:i:s') . " - Request received from " . $_SERVER['REMOTE_ADDR'] . "\n";
$content .= "URI: " . $_SERVER['REQUEST_URI'] . "\n";
$content .= "Params: " . print_r($_REQUEST, true) . "\n";
file_put_contents($logFile, $content, FILE_APPEND);
