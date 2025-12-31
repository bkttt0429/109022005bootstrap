<?php
require_once __DIR__ . '/../api_bootstrap.php';

echo "--- PHP Environment Headers ---\n";
$headers = getallheaders();
print_r($headers);

echo "\n--- SERVER Variable ---\n";
print_r($_SERVER);
