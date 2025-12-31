<?php
$url = 'http://localhost/109022005bootstrap/main_app/api/v1/inventory/inbound';
$data = [
    'product_id' => 45,
    'quantity' => 10,
    'reason' => 'MANUAL_TEST'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "Status Code: " . $info['http_code'] . "\n";
echo "Response: " . $response . "\n";
