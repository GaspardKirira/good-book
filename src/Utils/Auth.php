<?php

namespace Softadastra\Utils;

use Softadastra\Model\JWT;

class Auth
{
    public static function checkAuth(): ?array
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $token = '';

        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = trim(substr($authHeader, 7));
        }

        if (!$token || strlen($token) < 10) {
            http_response_code(401);
            echo json_encode([
                'status' => 'unauthorized',
                'message' => 'Token manquant ou invalide.'
            ]);
            exit;
        }

        try {
            $jwt = new JWT();

            if (!$jwt->isValid($token) || $jwt->isExpired($token) || !$jwt->check($token, SECRET)) {
                http_response_code(401);
                echo json_encode([
                    'status' => 'unauthorized',
                    'message' => '🔐 Token invalide ou expiré. Veuillez vous reconnecter.'
                ]);
                exit;
            }

            return $jwt->getPayload($token);
        } catch (\Exception $e) {
            error_log("[Auth::checkAuth] " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Erreur lors de la vérification du token.'
            ]);
            exit;
        }
    }
}
