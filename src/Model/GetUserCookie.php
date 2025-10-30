<?php

namespace Softadastra\Model;

use Softadastra\Application\Http\RedirectionHelper;
use Softadastra\Model\JWT;

class GetUserCookie
{
    private $jwt;
    private $token;
    private bool $redirectOnFail;

    public function __construct(bool $redirectOnFail = true)
    {
        $this->jwt = new JWT();
        $this->token = $_COOKIE['token'] ?? null;
        $this->redirectOnFail = $redirectOnFail;
    }

    private function validateToken()
    {
        if (
            isset($this->token)
            && $this->jwt->isValid($this->token)
            && !$this->jwt->isExpired($this->token)
            && $this->jwt->check($this->token, SECRET)
        ) {
            return $this->jwt->getPayload($this->token);
        }
        return null;
    }

    public function getUserEntity()
    {
        $payload = $this->validateToken();
        if (!$payload) {
            if ($this->redirectOnFail) {
                RedirectionHelper::redirect("login");
            }
            return null;
        }
        $repo = new \Domain\Users\UserRepository();
        return $repo->findById((int)$payload['id']) ?: null;
    }
}
