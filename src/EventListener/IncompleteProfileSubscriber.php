<?php
// src/EventSubscriber/IncompleteProfileSubscriber.php

namespace App\EventListener;

use App\Service\ProfileHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;

class IncompleteProfileSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private RouterInterface $router,
        private ProfileHandler $profileHandler,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // ✅ routes toujours autorisées
        $allowedRoutes = [
            'app_welcome',
            'app_welcome_accept',
            'app_profile_edit',
            'app_logout',
            'app_login',
            'app_register',
        ];

        if (in_array($route, $allowedRoutes, true)) {
            return;
        }

        // ✅ 1️⃣ welcome une seule fois
        if (!$user->getHasSeenWelcome()) {
            $event->setResponse(
                new RedirectResponse(
                    $this->router->generate('app_welcome')
                )
            );
            return;
        }

        // ✅ 2️⃣ profil incomplet
        if (!$user->hasFirstFillProfile()) {
            $event->setResponse(
                new RedirectResponse(
                    $this->router->generate(
                        'app_profile_edit',
                        ['element' => $this->profileHandler->currentStep()]
                    )
                )
            );
            return;
        }
    }
}


