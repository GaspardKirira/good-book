<?php

namespace Softadastra\Application\Utils;

use Softadastra\Model\JWT;

class Adastra
{
    static public function getCookie()
    {
        if (isset($_COOKIE['token'])) {
            $jwt = new JWT();
            $token = $_COOKIE['token'];
            if ($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, SECRET)) {
                header('Location: /user/dashboard');
                exit;
            }
        }
    }

    static function getPayload()
    {
        if (isset($_COOKIE['token'])) {
            $jwt = new JWT();
            $token = $_COOKIE['token'];
            if ($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, SECRET)) {
                return $jwt->getPayload($token);
            }
        }
    }
}
