<?php
require_once 'db.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Debug logging function
function logGoogleAuth($msg) {
    file_put_contents('debug_auth_google.log', date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

logGoogleAuth("Request received");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $token = $input['token'] ?? '';

    logGoogleAuth("Token received, length: " . strlen($token));
    
    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['error' => 'Token is required']);
        exit;
    }

    // Verify Token with Google
    // Disable SSL verification for XAMPP dev environment (fix for common SSL error)
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // DEV ONLY: Fix SSL certificate problem
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        logGoogleAuth("CURL Error: " . $curlError);
        http_response_code(500);
        echo json_encode(['error' => 'CURL Error: ' . $curlError]);
        exit;
    }

    if ($httpCode !== 200) {
        logGoogleAuth("Google Token Verification Failed. HTTP: $httpCode. Response: $response");
        http_response_code(401);
        echo json_encode(['error' => 'Invalid Google Token']);
        exit;
    }
    
    logGoogleAuth("Google Token Verified. proceeding to DB.");

    $googleUser = json_decode($response, true);
    
    // Check Audience (Optional but recommended)
    // if ($googleUser['aud'] !== 'YOUR_CLIENT_ID') { ... }

    $googleId = $googleUser['sub'];
    $email = $googleUser['email'];
    $name = $googleUser['name'];
    $avatar = $googleUser['picture'] ?? '';

    try {
        $pdo = getDB();

        // Check if user exists by google_id
        $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ?");
        $stmt->execute([$googleId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Check if user exists by email (link account)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingUser) {
                // Link account
                $stmt = $pdo->prepare("UPDATE users SET google_id = ?, avatar_url = ? WHERE id = ?");
                $stmt->execute([$googleId, $avatar, $existingUser['id']]);
                $user = $existingUser;
                $user['google_id'] = $googleId;
                $user['avatar_url'] = $avatar;
            } else {
                // Create new user
                $stmt = $pdo->prepare("INSERT INTO users (name, email, google_id, avatar_url, password_hash) VALUES (?, ?, ?, ?, ?)");
                // Use a random password for google-only users
                $randomPass = password_hash(bin2hex(random_bytes(10)), PASSWORD_BCRYPT);
                $stmt->execute([$name, $email, $googleId, $avatar, $randomPass]);
                
                $newId = $pdo->lastInsertId();
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$newId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } else {
             // Update avatar in case it changed
             $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
             $stmt->execute([$avatar, $user['id']]);
             $user['avatar_url'] = $avatar;
        }

        // Start Session (Simulated for this demo architecture)
        // In a real app, you'd set $_SESSION or return a JWT
        // Here we just return the user object like auth_api.php does
        
        session_start();
        $_SESSION['user_id'] = $user['id'];
        
        unset($user['password_hash']); // Don't send hash back
        
        logGoogleAuth("Login Successful for User ID: " . $user['id']);
        echo json_encode(['success' => true, 'user' => $user]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
