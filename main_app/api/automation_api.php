<?php
require_once 'api_bootstrap.php';
require_once 'admin_gate.php';

try {
    $pdo = getDB();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = getJsonInput();
    $action = $input['action'] ?? $_GET['action'] ?? '';

    if ($method === 'GET') {
        // Auto-Init: Check ENV for n8n API Key and sync to DB if missing
        if (!empty($_ENV['N8N_API_KEY'])) {
            $envKey = $_ENV['N8N_API_KEY'];
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('n8n_api_key', ?) ON CONFLICT (setting_key) DO UPDATE SET setting_value = ?");
            $stmt->execute([$envKey, $envKey]);
        }
        
        if ($action === 'get_configs') {
            $stmt = $pdo->query("SELECT * FROM webhook_configs ORDER BY id DESC");
            sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));

        } elseif ($action === 'get_logs') {
            // Get last 50 logs
            $stmt = $pdo->query("SELECT * FROM automation_logs ORDER BY id DESC LIMIT 50");
            $out = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Decode payload for frontend convenience if possible, or send as is
                $row['payload'] = json_decode($row['payload']);
                $out[] = $row;
            }
            sendResponse($out);

        } elseif ($action === 'get_n8n_key') {
            $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'n8n_api_key'");
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            // mask key
            $hasKey = !empty($res['setting_value']);
            sendResponse(['has_key' => $hasKey]);
        }

    } elseif ($method === 'POST') {
        if ($action === 'save_config') {
            $topic = $input['event_topic'];
            $url = $input['n8n_webhook_url'];
            $desc = $input['description'] ?? '';
            $id = $input['id'] ?? null;

            if ($id) {
                // Update
                $stmt = $pdo->prepare("UPDATE webhook_configs SET event_topic = ?, n8n_webhook_url = ?, description = ? WHERE id = ?");
                $stmt->execute([$topic, $url, $desc, $id]);
            } else {
                // Create
                $stmt = $pdo->prepare("INSERT INTO webhook_configs (event_topic, n8n_webhook_url, description) VALUES (?, ?, ?)");
                $stmt->execute([$topic, $url, $desc]);
            }
            sendResponse(['success' => true]);

        } elseif ($action === 'delete_config') {
            $id = $input['id'];
            $stmt = $pdo->prepare("DELETE FROM webhook_configs WHERE id = ?");
            $stmt->execute([$id]);
            sendResponse(['success' => true]);

        } elseif ($action === 'save_n8n_key') {
            $key = $input['api_key'];
            // Upsert
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('n8n_api_key', ?) ON CONFLICT (setting_key) DO UPDATE SET setting_value = ?");
            $stmt->execute([$key, $key]);
            sendResponse(['success' => true]);

        } elseif ($action === 'proxy_n8n') {
            // Get API Key
            $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'n8n_api_key'");
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $apiKey = $res ? $res['setting_value'] : '';

            if (!$apiKey) {
                sendResponse(['error' => 'API Key not configured'], 400);
            }

            $endpoint = $input['endpoint']; // e.g., 'workflows' or 'workflows/1/activate'
            $httpMethod = $input['method'] ?? 'GET';
            $n8nUrl = "http://localhost:5678/api/v1/" . ltrim($endpoint, '/');

            $ch = curl_init($n8nUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-N8N-API-KEY: ' . $apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
            
            if ($httpMethod === 'POST' && isset($input['body'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input['body']));
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            http_response_code($httpCode);
            echo $response;
            exit;
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
