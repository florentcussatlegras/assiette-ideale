<?php

namespace App\EventListener;

use Twig\Environment;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TwigEventSubscriber implements EventSubscriberInterface
{
    private $twig;
    private $security;

    public function __construct(Environment $twig, Security $security)
    {
        $this->twig = $twig;
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ControllerEvent::class => 'onKernelController'
        ];
    }

    public function onKernelController(ControllerEvent $event)
    {
        $user = $this->security->getUser();

        if(null === $user)
            return;

        $this->twig->addGlobal('isAdmin', $this->security->isGranted('ROLE_ADMIN') ? "Vous êtes l'administrateur du site" : null);
    }
}