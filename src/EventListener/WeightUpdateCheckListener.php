<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use App\Service\EnergyAdjustmentManager;

class WeightUpdateCheckListener
{
    public function __construct(
        private Security $security,
        private RouterInterface $router,
        private EnergyAdjustmentManager $energyAdjustmentManager
    ) {}

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        // On ignore les requêtes console
        if (!$event->isMainRequest()) {
            return;
        }

        /** @var \App\Entity\User|null $user */
        $user = $this->security->getUser();
        if (!$user) {
            return;
        }


        // Si l'utilisateur suit un régime et n'a pas mis à jour son poids depuis 7 jours
        // if ($user->isWeightGoalActive() && $this->energyAdjustmentManager->needsWeeklyUpdate($user)) {
        /** @var \App\Entity\User|null $user */

        if ($user->isWeightGoalActive() && $this->energyAdjustmentManager->needsWeeklyUpdate($user)) {
            // Flag à récupérer dans Twig
            $currentRoute = $request->attributes->get('_route');
            $currentElement = $request->attributes->get('element');

            // On ne montre pas le modal si déjà sur la page de saisie du poids
            if (!($currentRoute === 'app_profile_edit' && $currentElement === 'weight')) {
                // Ajouter un attribut à la requête pour Twig
                $request->attributes->set('needs_weight_update', true);
            }
        }
    }
}
