<?php

namespace App\Security\Authenticator;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class SocialAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function supports(Request $request): ?bool
    {
        return false; // ne se déclenche jamais automatiquement
    }

    public function authenticate(Request $request): Passport
    {
        // Passport minimal requis mais jamais utilisé, car l’auth arrive via authenticateUser()
        return new SelfValidatingPassport(
            new UserBadge('social_user_dummy')
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        return new RedirectResponse(
            $this->urlGenerator->generate('app_homepage')
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?RedirectResponse
    {
        return new RedirectResponse(
            $this->urlGenerator->generate('app_login')
        );
    }
}
