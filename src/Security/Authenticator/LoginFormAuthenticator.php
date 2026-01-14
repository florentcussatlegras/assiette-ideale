<?php

namespace App\Security\Authenticator;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;
    
    private $userRepository;
    private $router;

    public function __construct(UserRepository $userRepository, RouterInterface $router)
    {
        $this->userRepository = $userRepository;
        $this->router = $router;
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod('POST') && $request->getPathInfo() === $this->getLoginUrl($request);
    }

    public function authenticate(Request $request): Passport
    {
        $usernameOrEmail = $request->request->get('usernameOrEmail');
        $password = $request->request->get('password');

        return new Passport(
            new UserBadge($usernameOrEmail, function($userIdentifier){
                $user = $this->userRepository->findOneBy(['username' => $userIdentifier]);

                if(!$user) {
                    throw new UserNotFoundException();
                    // throw new CustomUserMessageAuthenticationException('Message toto');
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new RememberMeBadge(),
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token'))
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        if($target = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($target);
        }
        
        return new RedirectResponse(
            $this->router->generate('app_dashboard_index')
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse(
            $this->getLoginUrl($request)
        );
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->getLoginUrl($request)
        );
    }

    public function getLoginUrl(Request $request): string
    {
        return $this->router->generate('app_login');
    }
}