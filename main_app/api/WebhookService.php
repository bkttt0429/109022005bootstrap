<?php
require_once 'db.php';

class WebhookService {
    private $pdo;

    public function __construct() {
        $this->pdo = getDB();
    }

    /**
     * Trigger a webhook for a specific event topic.
     * 
     * @param string $topic The event topic (e.g., 'ORDER_CREATED')
     * @param string $entityId The ID of the entity (e.g., Order ID)
     * @param array $payload The data to send
     * @return void
     */
    public function trigger($topic, $entityId, $payload) {
        $configs = $this->getWebhookConfigs($topic);

        if (empty($configs)) {
            return;
        }

        foreach ($configs as $config) {
            $this->sendWebhook($config['n8n_webhook_url'], $topic, $entityId, $payload);
        }
    }

    private function getWebhookConfigs($topic) {
        $stmt = $this->pdo->prepare("SELECT * FROM webhook_configs WHERE event_topic = ? AND is_active = TRUE");
        $stmt->execute([$topic]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function sendWebhook($url, $topic, $entityId, $payload) {
        $data = [
            'topic' => $topic,
            'entity_id' => $entityId,
            'timestamp' => date('c'),
            'data' => $payload
        ];

        $jsonData = json_encode($data);

        // Basic curl implementation (blocking for MVP, should be async/queue in prod)
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Short timeout to allow frontend to proceed

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Log the attempt
        $this->logAttempt($topic, $entityId, $jsonData, $httpCode, $response ?: $error);
    }

    private function logAttempt($topic, $entityId, $payload, $status, $response) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO automation_logs (event_topic, entity_id, payload, response_status, response_body) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$topic, $entityId, $payload, $status, $response]);
        } catch (Exception $e) {
            // Silently fail logging to not disrupt main flow
            error_log("Webhook logging failed: " . $e->getMessage());
        }
    }
}
