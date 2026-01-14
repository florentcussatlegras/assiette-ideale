<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;

class LocaleListener implements EventSubscriberInterface
{
    public function __construct(
        private $localeListener = 'fr'
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            // KernelEvents::REQUEST => ['onKernelRequest'],
            // KernelEvents::CONTROLLER_ARGUMENTS => ['onKernelControllerArguments']
        ];
    }

    // public function onKernelRequest(RequestEvent $event)
    // {
    //     dd('la');
    // }

    // public function onKernelControllerArguments(ControllerArgumentsEvent $event)
    // {
    //     $namedArguments = $event->getRequest()->attributes->all();
    //     dump($event->getController());
    //     dump($event->getArguments());
    //     dd($namedArguments);
    // }
}