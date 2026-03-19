<?php

namespace App\Controller\meal;

use App\Service\AlertFeature;
use App\Service\BalanceSheetFeature;
use App\Controller\AlertUserController;
use App\Service\LabelResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * AlertMessagesController.php
 *
 * Controller responsable de l'affichage des messages d'alertes nutritionnelles
 * dans la modale des bilans alimentaires (journaliers ou hebdomadaires).
 *
 * Fonctionnalités :
 * - Calculer les alertes du bilan alimentaire sur une période donnée
 * - Résoudre les labels lisibles pour chaque type d'alerte
 * - Trier les alertes par niveau de priorité
 * - Vérifier si un repas est globalement équilibré
 *
 * Routes :
 *  /alert-messages
 *      -> Affiche la liste des messages d'alertes du bilan alimentaire
 *
 *  /alert-is-all-well-balanced
 *      -> Indique si le repas courant est équilibré
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 * 
 * @package App\Controller\meal
 */
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class AlertMessagesController extends AbstractController implements AlertUserController
{
    /**
     * Affiche la liste des alertes du bilan alimentaire pour une période donnée.
     *
     * Étapes du traitement :
     * 1. Récupérer les dates start/end depuis les paramètres GET
     * 2. Calculer les moyennes journalières (énergie, FGP, nutriments)
     * 3. Générer les alertes correspondantes via AlertFeature
     * 4. Résoudre les labels lisibles des alertes
     * 5. Trier les alertes par niveau de priorité
     * 6. Renvoyer la vue Twig affichant les messages
     *
     * @param Request $request Requête HTTP contenant éventuellement les dates start/end
     * @param BalanceSheetFeature $balanceSheetFeature Service calculant les moyennes nutritionnelles
     * @param AlertFeature $alertFeature Service générant les alertes nutritionnelles
     * @param LabelResolver $labelResolver Service permettant de transformer les clés d'alertes en labels lisibles
     *
     * @return Response Vue contenant la liste des messages d'alertes
     */
    #[Route('/alert-messages', name: 'app_alert_messages', methods: ['GET'])]
    public function index(
        Request $request,
        BalanceSheetFeature $balanceSheetFeature,
        AlertFeature $alertFeature,
        LabelResolver $labelResolver
    ): Response {
        
        /** @var App\Entity\User|null $user */
        $user = $this->getUser();

        // Récupération des dates de la période depuis les paramètres GET
        $dateStart = $request->query->get('start');
        $dateEnd = $request->query->get('end');

        // Calcul de l'énergie moyenne journalière sur la période
        $averageDailyEnergy = $balanceSheetFeature->averageDailyEnergyForAPeriod($dateStart, $dateEnd);

        // Calcul des quantités moyennes journalières par groupe alimentaire (FGP)
        $averageDailyFgp = $balanceSheetFeature->averageDailyFgpForAPeriod($dateStart, $dateEnd);

        // Calcul des nutriments moyens journaliers
        $averageDailyNutrient = $balanceSheetFeature->averageDailyNutrientForAPeriod($dateStart, $dateEnd);

        // Génération des alertes nutritionnelles à partir des moyennes calculées
        $balanceSheetAlerts = $alertFeature->getBalanceSheetAlerts(
            $averageDailyEnergy,
            $averageDailyNutrient
        );

        // Tableau final contenant les alertes enrichies avec leurs labels
        $balanceSheetAlertsResolved = [];

        foreach ($balanceSheetAlerts as $key => $alert) {
            $balanceSheetAlertsResolved[] = [
                'key'   => $key,                     // clé interne de l'alerte
                'alert' => $alert,                   // objet alerte contenant niveau et priorité
                'label' => $labelResolver->resolve($key) // label lisible pour l'utilisateur
            ];
        }

        // Tri des alertes par priorité (les alertes les plus graves apparaissent en premier)
        usort($balanceSheetAlertsResolved, function ($a, $b) {
            return ($a['alert']->getPriority() ?? 0) <=> ($b['alert']->getPriority() ?? 0);
        });

        return $this->render('meals/week/_alerts_messages_list.html.twig', [
            'balanceSheetAlerts' => $balanceSheetAlertsResolved,
        ]);
    }

    /**
     * Vérifie si le repas courant est entièrement équilibré.
     *
     * Étapes :
     * 1. Calculer toutes les alertes globales du repas
     * 2. Vérifier si aucune alerte problématique n'est présente
     * 3. Retourner un indicateur boolean permettant d'afficher un message positif
     *
     * @param AlertFeature $alertFeature Service gérant le calcul des alertes nutritionnelles
     *
     * @return Response Vue indiquant si le repas est équilibré
     */
    #[Route('/alert-is-all-well-balanced', name: 'app_alert_is_all_well_balanced', methods: ['GET'])]
    public function isAllWellBalanced(AlertFeature $alertFeature): Response
    {
        // Calcul de toutes les alertes nutritionnelles du repas courant
        $alerts = $alertFeature->computeMealGlobalAlerts();

        // Vérifie si aucune alerte critique n'est présente
        $isFullyBalanced = $alertFeature->isMealFullyBalanced($alerts);

        return $this->render('meals/day/_is_fully_balanced.html.twig', [
            'alerts' => $alerts,
            'isFullyBalanced' => $isFullyBalanced,
        ]);
    }
}