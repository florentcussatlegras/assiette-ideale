<?php

namespace App\EventListener;

use Twig\Environment;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber pour ajouter des variables globales à Twig.
 *
 * Objectif :
 *  - Fournir une variable globale `isAdmin` dans tous les templates Twig.
 *  - Détecte si l’utilisateur courant possède le rôle ROLE_ADMIN.
 *
 * Fonctionnement :
 *  - Écoute l’événement `ControllerEvent`.
 *  - Injecte `isAdmin` seulement si un utilisateur est connecté.
 *  - Permet de conditionner l’affichage côté frontend selon les rôles.
 *
 * Points clés :
 *  - Utilisation de Security pour récupérer l’utilisateur et ses rôles.
 *  - Injection directe de Twig pour ajouter les variables globales.
 */
class TwigEventSubscriber implements EventSubscriberInterface
{
    private Environment $twig;
    private Security $security;

    public function __construct(Environment $twig, Security $security)
    {
        $this->twig = $twig;
        $this->security = $security;
    }

    /**
     * Définition des événements souscrits.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ControllerEvent::class => 'onKernelController',
        ];
    }

    /**
     * Ajoute la variable globale `isAdmin` dans Twig si l'utilisateur est connecté.
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $user = $this->security->getUser();

        if (null === $user) {
            return;
        }

        $this->twig->addGlobal(
            'isAdmin',
            $this->security->isGranted('ROLE_ADMIN') ? "Vous êtes l'administrateur du site" : null
        );
    }
}