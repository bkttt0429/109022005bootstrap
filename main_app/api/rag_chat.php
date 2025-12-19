<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'config.php';

// ------------------------------------------------------------------
// CONFIGURATION
// ------------------------------------------------------------------
$GEMINI_API_KEY = trim(GEMINI_API_KEY);
// File Search REQUIRES Gemini 1.5+
$MODEL_NAME = 'gemini-2.5-flash'; 
$CACHE_FILE = 'gemini_file_store.json';
$CACHE_DURATION = 48 * 3600; // 48 hours (File API default expiration)

// ------------------------------------------------------------------
// HELPERS
// ------------------------------------------------------------------

function getProductDataAsText($pdo) {
    // Fetch all products with relevant details for the RAG context
    $stmt = $pdo->query("
        SELECT 
            p.id, p.name, p.price, p.stock_quantity, p.category, p.description,
            COALESCE(SUM(oi.quantity), 0) as sold_count
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        GROUP BY p.id
        ORDER BY sold_count DESC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $text = "ERP SYSTEM PRODUCT CATALOG (Generated: " . date('Y-m-d H:i:s') . ")\n";
    $text .= "--------------------------------------------------\n";
    
    foreach ($products as $p) {
        $text .= "ID: {$p['id']}\n";
        $text .= "Name: {$p['name']}\n";
        $text .= "Category: {$p['category']}\n";
        $text .= "Price: {$p['price']}\n";
        $text .= "Stock: {$p['stock_quantity']}\n";
        $text .= "Sold: {$p['sold_count']}\n";
        $text .= "Description: {$p['description']}\n";
        $text .= "--------------------------------------------------\n";
    }
    return $text;
}

function uploadToGemini($text, $apiKey) {
    $uploadUrlBase = "https://generativelanguage.googleapis.com/upload/v1beta/files?key=$apiKey";

    // 1. Initial Request (Resumable Upload)
    $metadata = json_encode(['file' => ['display_name' => 'erp_products.txt']]);
    $numBytes = strlen($text);

    $ch = curl_init($uploadUrlBase);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Goog-Upload-Protocol: resumable',
        'X-Goog-Upload-Command: start',
        'X-Goog-Upload-Header-Content-Length: ' . $numBytes,
        'X-Goog-Upload-Header-Content-Type: text/plain',
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $metadata);
    // Capture headers to get the upload URL
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    curl_close($ch);

    // Extract X-Goog-Upload-URL
    if (!preg_match('/x-goog-upload-url:\s*(.*?)(\r\n|\n|$)/i', $headers, $matches)) {
        throw new Exception("Failed to get upload URL from Gemini: " . substr($response, 0, 200));
    }
    $uploadUrl = trim($matches[1]);

    // 2. Upload Actual Bytes
    $ch2 = curl_init($uploadUrl);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'Content-Length: ' . $numBytes,
        'X-Goog-Upload-Offset: 0',
        'X-Goog-Upload-Command: upload, finalize'
    ]);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, $text);
    
    $fileResponse = curl_exec($ch2);
    if (curl_errno($ch2)) {
        throw new Exception('Upload Error: ' . curl_error($ch2));
    }
    curl_close($ch2);

    $fileData = json_decode($fileResponse, true);
    if (!isset($fileData['file']['uri'])) {
        throw new Exception("Upload failed, no URI returned: " . $fileResponse);
    }

    return $fileData['file'];
}

function getOrUploadFile($pdo, $apiKey, $cacheFile, $duration) {
    // Check Cache
    if (file_exists($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true);
        if ($cache && (time() - $cache['timestamp'] < $duration)) {
            // Validate File State if needed? No, assume OK for 48h.
            return $cache['uri'];
        }
    }

    // Refresh
    $text = getProductDataAsText($pdo);
    $fileInfo = uploadToGemini($text, $apiKey);
    $uri = $fileInfo['uri'];

    // Save Cache
    file_put_contents($cacheFile, json_encode([
        'uri' => $uri,
        'timestamp' => time(),
        'name' => $fileInfo['name']
    ]));

    return $uri;
}

// ------------------------------------------------------------------
// MAIN LOGIC
// ------------------------------------------------------------------

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

try {
    $pdo = getDB();

    // 1. Get File URI (Upload or Cache)
    $fileUri = getOrUploadFile($pdo, $GEMINI_API_KEY, $CACHE_FILE, $CACHE_DURATION);

    // 2. Generate Content using File URI
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/$MODEL_NAME:generateContent?key=$GEMINI_API_KEY";

    $prompt = "User Question: " . $userMessage . "\n";
    $prompt .= "Answer based on the Product Catalog file provided. Be helpful and polite.\n";
    $prompt .= "IMPORTANT: If the user asks for a list, comparison, or quantitative data, AFTER your text response, output a JSON array wrapped in <data> tags. Format: <data>[{\"name\": \"Item\", \"value\": 10}, ...]</data>. The 'value' must be a number.";

    $apiBody = [
        "contents" => [
            [
                "role" => "user",
                "parts" => [
                    ["file_data" => ["mime_type" => "text/plain", "file_uri" => $fileUri]],
                    ["text" => $prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiBody));
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('Gemini API Error: ' . curl_error($ch));
    }
    curl_close($ch);

    $responseData = json_decode($response, true);
    $aiText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

    if (empty($aiText)) {
        // Fallback message if AI returns nothing (e.g. safety filter)
        $aiText = "Sorry, I could not generate a response based on the file.";
    }

    echo json_encode([
        'reply' => $aiText, 
        'debug_context' => ['mode' => 'file_search', 'model' => $MODEL_NAME, 'file_uri' => $fileUri],
        'raw_api_response' => $responseData
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'debug_trace' => $e->getTraceAsString()]);
}
?>
