<?php
require_once 'api_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJsonInput();

    if (!isset($input['name'], $input['email'], $input['password'])) {
        sendResponse(['error' => 'Missing required fields'], 400);
    }

    $name = trim($input['name']);
    $email = trim($input['email']);
    $password = $input['password'];

    try {
        $pdo = getDB();

        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendResponse(['error' => 'Email already registered'], 409);
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insert User
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$name, $email, $passwordHash]);

        sendResponse(['success' => true, 'message' => 'Registration successful']);

    } catch (Exception $e) {
        sendResponse(['error' => 'Internal Server Error', 'details' => $e->getMessage()], 500);
    }
} else {
    sendResponse(['error' => 'Method Not Allowed'], 405);
}
