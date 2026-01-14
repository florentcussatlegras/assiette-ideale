<?php

namespace App\Security\RememberMe;

use Symfony\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface;
use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentTokenInterface;

class CustomTokenProvider implements TokenProviderInterface
{
    public function loadTokenBySeries(string $series)
    {
        return 1234;
    }
   
    public function deleteTokenBySeries(string $series)
    {

    }
   
    public function updateToken(string $series, string $tokenValue, \DateTime $lastUsed)
    {

    }
   
    public function createNewToken(PersistentTokenInterface $token)
    {

    }
}