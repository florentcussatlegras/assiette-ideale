<?php

namespace App\Controller\BalanceSheet;

use App\Controller\AlertUserController;
use App\Service\BalanceSheetFeature;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * DefaultController.php
 *
 * Controller gérant le bilan nutritionnel et les statistiques de consommation de l'utilisateur.
 *
 * Ce controller permet d'afficher :
 * - le plat ou aliment favori de l'utilisateur sur une période donnée
 * - le repas le plus calorique
 * - la page principale du bilan nutritionnel avec toutes les statistiques entre deux dates
 *
 * Toutes les routes de ce controller nécessitent que l'utilisateur soit authentifié.
 * Ce controller implémente AlertUserController pour respecter le contrat des alertes utilisateurs.
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 *
 * @package App\Controller\BalanceSheet
 */
#[Route('/balance_sheet', name: 'app_balance_sheet_')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class DefaultController extends AbstractController implements AlertUserController
{
    /**
     * Affiche l'élément favori (plat ou aliment) de l'utilisateur sur une période donnée.
     *
     * Les dates de début et de fin sont passées en paramètres GET (start et end).
     * Le paramètre {type} détermine s'il s'agit d'un plat ('dish') ou d'un aliment ('food').
     *
     * @param string $type Type d'élément favori ('dish' ou 'food')
     * @param Request $request Requête HTTP contenant les paramètres start et end
     * @param BalanceSheetFeature $balanceSheetFeature Service pour récupérer les statistiques nutritionnelles
     * @return \Symfony\Component\HttpFoundation\Response Vue contenant l'élément favori
     */
    #[Route('/favorite/{type}', name: 'favorite_item', methods: ['GET'], requirements: ['type' => 'dish|food'])]
    public function favoriteItem(string $type, Request $request, BalanceSheetFeature $balanceSheetFeature)
    {
        // Récupère l'élément favori en fonction du type
        $item = $this->getFavoriteItem($type, $request, $balanceSheetFeature);

        return $this->render('balance_sheet/_favorite_item.html.twig', [
            'item' => $item,
            'type' => $type,
        ]);
    }

    /**
     * Méthode générique qui récupère l'élément favori sur une période
     */
    private function getFavoriteItem(string $type, Request $request, BalanceSheetFeature $balanceSheetFeature)
    {
        // Récupère la date de début envoyée dans l'URL via les paramètres GET
        // Exemple : /favorite-dish?start=2024-03-01
        $start = $request->query->get('start');

        // Récupère la date de fin envoyée dans l'URL
        // Exemple : /favorite-dish?end=2024-03-07
        $end = $request->query->get('end');

        // Vérifie que les deux dates existent
        // Si l'une des deux est absente, on ne peut pas calculer les statistiques
        // donc on retourne null
        if (!$start || !$end) {
            return null;
        }

        return match ($type) {
            'dish' => $balanceSheetFeature->getFavoriteDishPerPeriod($start, $end),
            'food' => $balanceSheetFeature->getFavoriteFoodPerPeriod($start, $end),
            default => null,
        };
    }

    /**
     * Affiche le repas le plus calorique sur une période donnée
     */
    #[Route('/most-caloric-meal', name: 'most_caloric_meal', methods: ['GET'])]
    public function mostCaloricMeal(Request $request, BalanceSheetFeature $balanceSheetFeature)
    {
        // Vérifie la présence des dates
        if ($request->query->get('start') && $request->query->get('end')) {

            // Récupération des dates
            $start = $request->query->get('start');
            $end = $request->query->get('end');

            // Récupère le repas le plus calorique sur la période
            $meal = $balanceSheetFeature->getMostCaloricPerPeriod($start, $end);
        }

        return $this->render('balance_sheet/_most_caloric_meal.html.twig', [
            'meal' => $meal ?? null,
        ]);
    }

    /**
     * Page principale du bilan nutritionnel
     * Permet d'afficher les statistiques entre deux dates
     */
    #[Route(
        '/{start?}/{end?}',
        name: 'index',
        methods: ['GET'],
        requirements: [
            'start' => '\d{4}-\d{2}-\d{2}',
            'end' => '\d{4}-\d{2}-\d{2}'
        ]
    )]
    public function index(?string $start, ?string $end)
    {
        /**
         * Gestion de la date de début
         */

        // Si une date start est passée dans l'URL
        if ($start) {

            // Conversion de la string en objet DateTime
            $start = \DateTime::createFromFormat('Y-m-d', $start);
        } else {

            // Sinon on prend la date d'hier par défaut
            $start = new \DateTime('-1 day');
        }

        // Formatage pour l'affichage ou l'utilisation côté front
        $start = $start->format('m/d/Y');

        /**
         * Gestion de la date de fin
         */
        if ($end) {

            // Conversion de la date passée
            $end = \DateTime::createFromFormat('Y-m-d', $end);
        } else {

            // Date par défaut : hier
            $end = new \DateTime('-1 day');
        }

        // Formatage pour l'affichage ou l'utilisation côté front
        $end = $end->format('m/d/Y');

        return $this->render('balance_sheet/index.html.twig', [
            'start' => $start,
            'end' => $end,
        ]);
    }
}
