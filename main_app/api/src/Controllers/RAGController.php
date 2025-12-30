<?php
namespace Controllers;

use Core\Response;
use Services\RAGService;

class RAGController {
    private $service;

    public function __construct() {
        $this->service = new RAGService(getDB());
    }

    public function chat() {
        $input = json_decode(file_get_contents('php://input'), true);
        $message = $input['message'] ?? '';

        if (empty($message)) {
            Response::error('Message cannot be empty');
        }

        $result = $this->service->processMessage($message);
        
        // Extract reply
        $reply = $result['candidates'][0]['content']['parts'][0]['text'] ?? "Sorry, I couldn't process that.";
        
        Response::json([
            'reply' => $reply,
            'raw' => $result
        ]);
    }
}
