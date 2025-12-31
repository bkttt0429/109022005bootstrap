<?php
namespace Services;

use PDO;
use Exception;

class WebhookService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Trigger a webhook for a specific event topic.
     */
    public function trigger($topic, $entityId, $payload) {
        $configs = $this->getWebhookConfigs($topic);

        if (empty($configs)) {
            return false;
        }

        $results = [];
        foreach ($configs as $config) {
            $results[] = $this->sendWebhook($config['n8n_webhook_url'], $topic, $entityId, $payload);
        }
        return $results;
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

        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $this->logAttempt($topic, $entityId, $jsonData, $httpCode, $response ?: $error);
        
        return [
            'url' => $url,
            'status' => $httpCode,
            'success' => ($httpCode >= 200 && $httpCode < 300)
        ];
    }

    private function logAttempt($topic, $entityId, $payload, $status, $response) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO automation_logs (event_topic, entity_id, payload, response_status, response_body) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$topic, $entityId, $payload, $status, $response]);
        } catch (Exception $e) {
            error_log("Webhook logging failed: " . $e->getMessage());
        }
    }
}
