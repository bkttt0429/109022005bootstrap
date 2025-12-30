<?php
class JWT {
    private static $secret_key = null;
    private static $algorithm = 'HS256';

    private static function getSecret() {
        if (self::$secret_key === null) {
            self::$secret_key = $_ENV['JWT_SECRET'] ?? 'default_secret_key_change_me';
        }
        return self::$secret_key;
    }

    public static function encode($payload, $validity_input = 3600) {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algorithm]);
        
        // Add expiration
        if (!isset($payload['exp'])) {
            $payload['exp'] = time() + $validity_input;
        }

        $base64Header = self::base64UrlEncode($header);
        $base64Payload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::getSecret(), true);
        $base64Signature = self::base64UrlEncode($signature);

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    public static function decode($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($base64Header, $base64Payload, $base64Signature) = $parts;

        $signature = self::base64UrlDecode($base64Signature);
        $expectedSignature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::getSecret(), true);

        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }

        $payload = json_decode(self::base64UrlDecode($base64Payload), true);

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
