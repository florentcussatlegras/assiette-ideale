<?php

namespace App\EventListener;

use App\Controller\AlertUserController;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class UserProfileListener
{
    public function __construct(
        private Security $security
    ){}

    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();

        if(is_array($controller)) {
            $controller = $controller[0];
        }

        if($controller instanceof AlertUserController) {
            $user = $this->security->getUser();
            
            
        }
    }
}