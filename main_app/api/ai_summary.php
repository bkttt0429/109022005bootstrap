<?php
require_once 'db.php';
require_once 'config.php'; // For GEMINI_API_KEY

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

$productId = $_GET['product_id'] ?? 0;

if (!$productId) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID required']);
    exit;
}

try {
    $pdo = getDB();
    // Fetch last 50 text reviews
    $stmt = $pdo->prepare("SELECT comment, rating FROM reviews WHERE product_id = ? AND comment != '' ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$productId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($reviews) < 1) {
        echo json_encode(['summary' => '尚無足夠評論可供 AI 分析。']);
        exit;
    }

    // Build Prompt
    $reviewsText = "";
    foreach ($reviews as $r) {
        $reviewsText .= "- Rating {$r['rating']}/5: {$r['comment']}\n";
    }

    $prompt = "You are a helpful shopping assistant. Analyze the following product reviews and provide a concise summary (in Traditional Chinese) of the Pros (優點) and Cons (缺點). formatting as a clean list.\n\nReviews:\n$reviewsText";

    // Call Gemini API
    $apiKey = GEMINI_API_KEY;
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$apiKey";

    $apiBody = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiBody));
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);

    $json = json_decode($response, true);
    $aiText = $json['candidates'][0]['content']['parts'][0]['text'] ?? 'AI 分析失敗，請稍後再試。';

    echo json_encode(['summary' => $aiText]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
