<?php

namespace Softadastra\Model;

use DateTime;
use Exception;

class JWT
{
    public function generate(array $header, array $payload, string $secret, int $validity = 86400): string
    {
        if (!is_array($header)) {
            throw new Exception("The header parameter must be an array.");
        }
        if (!is_array($payload)) {
            throw new Exception("The payload parameter must be an array.");
        }
        if (empty($secret)) {
            throw new Exception("The secret cannot be empty.");
        }

        if ($validity > 0) {
            $now = new DateTime();
            $expiration = $now->getTimestamp() + $validity;
            $payload['iat'] = $now->getTimestamp();
            $payload['exp'] = $expiration;
        }

        $base64Header = base64_encode(json_encode($header));
        $base64Payload = base64_encode(json_encode($payload));

        // Nettoyage de base64
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], $base64Header);
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], $base64Payload);

        // Signature HMAC
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);
        $base64Signature = base64_encode($signature);

        // Nettoyage de la signature
        $signature = str_replace(['+', '/', '='], ['-', '_', ''], $base64Signature);

        // Assemblage du JWT
        $jwt = $base64Header . '.' . $base64Payload . '.' . $signature;

        return $jwt;
    }

    public function check(string $token, string $secret): bool
    {
        if (!$this->isValid($token)) {
            throw new Exception("The JWT token is invalid.");
        }

        $header = $this->getHeader($token);
        $payload = $this->getPayload($token);

        $verifToken = $this->generate($header, $payload, $secret, 0);

        return $token === $verifToken;
    }

    public function getHeader(string $token): array
    {
        $array = explode('.', $token);
        if (count($array) !== 3) {
            throw new Exception("The JWT token format is invalid.");
        }

        $header = json_decode(base64_decode($array[0]), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("The JWT token header is corrupted. Suggestion: Open the developer tools by pressing F12, then go to the 'Application' tab. Finally, delete the token in the appropriate section.");
        }

        return $header;
    }

    public function getPayload(string $token): array
    {
        $array = explode('.', $token);
        if (count($array) !== 3) {
            throw new Exception("The JWT token format is invalid.");
        }

        $payload = json_decode(base64_decode($array[1]), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("The JWT token payload is corrupted.");
        }

        return $payload;
    }

    public function isExpired(string $token): bool
    {
        $payload = $this->getPayload($token);

        $now = new DateTime();

        return isset($payload['exp']) && $payload['exp'] < $now->getTimestamp();
    }

    public function isValid(string $token): bool
    {
        return preg_match(
            '/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/',
            $token
        ) === 1;
    }
}
