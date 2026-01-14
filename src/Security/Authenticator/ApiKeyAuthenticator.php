<?php

namespace App\Security\Authenticator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;


class ApiKeyAuthenticator extends AbstractAuthenticator
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function supports(Request $request): bool
    {
        return $request->request->has('X-AUTH-TOKEN');
        // return $request->attributes->get('_route') === 'api_login';
    }

    public function authenticate(Request $request): Passport
    {
        $apiToken = $request->request->get('X-AUTH-TOKEN');
        $apiToken = 'florent_admin';
        if(null === $apiToken) {
            throw new CustomUserMessageAuthenticationException('Token API not provide');
        }

        return new SelfValidatingPassport(new UserBadge($apiToken));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $sfirewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $error): ?Response
    {
        $data = [
            'message' => strtr($error->getMessageKey(), $error->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
    
    public function getLoginUrl(Request $request)
    {
        return $this->router->generate('api_login');
    }
}