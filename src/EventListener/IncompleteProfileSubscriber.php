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

/**
 * Subscriber pour gérer les profils incomplets des utilisateurs.
 *
 * Objectif :
 *  - Rediriger les utilisateurs vers la page de bienvenue si ce n’est pas encore fait.
 *  - Rediriger les utilisateurs vers l’édition de profil si leur profil n’est pas rempli.
 *  - S’assure que certaines routes restent accessibles même si le profil est incomplet.
 *
 * Fonctionnement :
 *  - S’exécute sur l’événement `KernelEvents::REQUEST`.
 *  - Vérifie si l’utilisateur est connecté et si la requête est la principale.
 *  - Applique des redirections conditionnelles selon l’état du profil.
 *
 * Routes autorisées sans redirection :
 *  - 'app_welcome', 'app_welcome_accept', 'app_profile_edit',
 *    'app_logout', 'app_login', 'app_register'
 */
class IncompleteProfileSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private RouterInterface $router,
        private ProfileHandler $profileHandler,
    ) {}

    /**
     * Définition des événements souscrits par ce subscriber.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    /**
     * Gestion des requêtes entrantes.
     *
     * - Ignore les requêtes secondaires.
     * - Vérifie l’utilisateur courant.
     * - Redirige vers la page de bienvenue si jamais vue.
     * - Redirige vers l’édition du profil si incomplet.
     */
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

        // ✅ Routes toujours autorisées
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

        // ✅ 1️⃣ welcome : une seule fois
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