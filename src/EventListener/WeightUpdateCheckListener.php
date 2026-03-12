<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use App\Service\EnergyAdjustmentManager;

/**
 * Listener pour vérifier si un utilisateur doit mettre à jour son poids.
 *
 * Objectif :
 *  - Détecter les utilisateurs suivant un objectif de poids actif.
 *  - Vérifier si leur poids n'a pas été mis à jour depuis plus de 7 jours.
 *  - Ajouter un flag `needs_weight_update` à la requête pour affichage conditionnel dans Twig.
 *
 * Fonctionnement :
 *  - Écoute l’événement `KernelEvents::REQUEST` (via service configuré).
 *  - Ignore les requêtes secondaires ou les utilisateurs non connectés.
 *  - Exclut la page de saisie du poids pour éviter un rappel redondant.
 *
 * Points clés :
 *  - Utilise Security pour récupérer l’utilisateur courant.
 *  - Utilise le service EnergyAdjustmentManager pour déterminer si une mise à jour est nécessaire.
 *  - Compatible avec Twig pour l’affichage conditionnel dans les templates.
 */
class WeightUpdateCheckListener
{
    public function __construct(
        private Security $security,
        private RouterInterface $router,
        private EnergyAdjustmentManager $energyAdjustmentManager
    ) {}

    /**
     * Vérifie la nécessité d’une mise à jour du poids sur chaque requête principale.
     *
     * - Ajoute l’attribut `needs_weight_update` à la requête si nécessaire.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // On ignore les requêtes secondaires
        if (!$event->isMainRequest()) {
            return;
        }

        /** @var \App\Entity\User|null $user */
        $user = $this->security->getUser();
        if (!$user) {
            return;
        }

        // Vérifie si l'utilisateur suit un objectif de poids et nécessite une mise à jour hebdomadaire
        if ($user->isWeightGoalActive() && $this->energyAdjustmentManager->needsWeeklyUpdate($user)) {
            $currentRoute = $request->attributes->get('_route');
            $currentElement = $request->attributes->get('element');

            // Ne pas afficher le flag si déjà sur la page de saisie du poids
            if (!($currentRoute === 'app_profile_edit' && $currentElement === 'weight')) {
                $request->attributes->set('needs_weight_update', true);
            }
        }
    }
}