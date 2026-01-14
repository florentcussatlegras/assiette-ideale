<?php

namespace App\Service;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;

class SwitchUserService
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function getImpersonator()
    {
        $token = $this->security->getToken();

        if($token instanceof SwitchUserToken) {
            $impersonator = $token->getOriginalToken()->getUser();

            return $impersonator;
        }
    }
}