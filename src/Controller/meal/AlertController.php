<?php

namespace App\Controller\meal;

use App\Entity\Dish;
use App\Entity\Food;
use App\Entity\FoodGroup\FoodGroupParent;
use App\Service\AlertFeature;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * AlertController.php
 *
 * Gère les alertes nutritionnelles liées aux plats et aliments de l'utilisateur.
 * 
 * Fonctionnalités :
 * - Afficher les alertes pour un plat ou aliment (sélectionné ou non)
 * - Mettre à jour les alertes après modification d'une portion ou quantité
 * - Afficher les alertes hebdomadaires par FoodGroupParent (FGP)
 * - Debug des alertes stockées en session
 *
 * Routes :
 *  /les-alertes/show/{class}/{dishOrFoodId}/{isSelected}/{rankMeal}/{rankDish}
 *  /les-alertes/update-alert-on-dish-on-update-portion/{id}/{nPortion}
 *  /les-alertes/update-alert-on-food-on-update-quantity/{id}/{quantity}/{unitMeasure}
 *  /les-alertes/show-on-week
 *  /les-alertes/show-session
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 * 
 * @package App\Controller\meal
 */
#[Route('les-alertes')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class AlertController extends AbstractController
{
    /**
     * Affiche les alertes pour un plat ou un aliment.
     * 
     * Cas gérés :
     *  - Élément sélectionné par l'utilisateur : récupère les alertes depuis `_dishes_selected` ou `_foods_selected`
     *  - Élément non sélectionné : récupère les alertes depuis `_dishes_not_selected` ou `_foods_not_selected`
     * 
     * Met à jour également la couleur de l'alerte courante en session (`color_current_alert`) si nécessaire.
     * 
     * @param Request $request Requête HTTP contenant éventuellement `showMessages` pour afficher les messages textuels
     * @param string $class Classe de l'entité : 'Dish' ou 'Food'
     * @param int $dishOrFoodId ID du plat ou de l'aliment
     * @param bool $isSelected Indique si l'élément est sélectionné par l'utilisateur
     * @param int|null $rankMeal Rang du repas dans la journée (optionnel)
     * @param int|null $rankDish Rang du plat ou aliment dans le repas (optionnel)
	 * 
     * @return Response Vue contenant les alertes adaptées au contexte
     */
    #[Route('/show/{class}/{dishOrFoodId}/{isSelected}/{rankMeal}/{rankDish}', name: 'alert_meal_list', methods: ['GET'], options: ['expose' => true])]
    public function show(Request $request, string $class, int $dishOrFoodId, bool $isSelected, ?int $rankMeal = null, ?int $rankDish = null): Response
    {
        $alerts = [];
        $session = $request->getSession();

        if ($isSelected) {
            // --- Élément sélectionné par l'utilisateur ---
            // Récupère les alertes associées à l'élément sélectionné depuis la session
            if (in_array($class, ['App\Entity\Dish', 'Dish'])) {
                $alerts = $session->get('_meal_day_alerts/_dishes_selected')[$rankMeal][$rankDish] ?? [];
            } elseif (in_array($class, ['App\Entity\Food', 'Food'])) {
                $alerts = $session->get('_meal_day_alerts/_foods_selected')[$rankMeal][$rankDish] ?? [];
            }

            // Affiche uniquement les messages textuels si demandé via query param
            if ($request->query->get('showMessages')) {
                return $this->render('meals/day/_list_alert_messages.html.twig', [
                    'alerts' => $alerts,
                ]);
            }

            // Mise à jour de la couleur de l'alerte actuelle dans la session pour l'affichage visuel
            if (!empty($alerts)) {
                $session->set('color_current_alert', $alerts['higher_level']->getColor());
            } else {
                $session->remove('color_current_alert');
            }

            // Rend la vue principale AJAX pour le plat/aliment sélectionné
            return $this->render('meals/day/list-ajax-alerts.html.twig', [
                'alerts' => $alerts,
                'class' => $class,
                'dishOrFoodId' => $dishOrFoodId,
                'isSelected' => $isSelected,
                'rankMeal' => $rankMeal,
                'rankDish' => $rankDish,
            ]);

        } else {
            // --- Élément non sélectionné ---
            // Récupère les alertes des éléments non sélectionnés depuis la session
            $alertsOnNotSelected = [];
            if (in_array($class, ['App\Entity\Dish', 'Dish'])) {
                $alertsOnNotSelected = $session->get('_meal_day_alerts/_dishes_not_selected', []);
            } elseif (in_array($class, ['App\Entity\Food', 'Food'])) {
                $alertsOnNotSelected = $session->get('_meal_day_alerts/_foods_not_selected', []);
            }

            // Si des alertes existent pour cet élément, on les récupère
            $alerts = $alertsOnNotSelected[$dishOrFoodId] ?? [];

            // Mise à jour de la couleur de l'alerte actuelle dans la session
            if (!empty($alerts)) {
                $session->set('color_current_alert', $alerts['higher_level']->getColor());
            } else {
                $session->remove('color_current_alert');
            }

            // Affiche uniquement les messages si demandé via query param
            if ($request->query->get('showMessages')) {
                return $this->render('meals/day/_list_alert_messages.html.twig', [
                    'alerts' => $alerts,
                ]);
            }

            // Rend la vue principale AJAX pour le plat/aliment non sélectionné
            return $this->render('meals/day/list-ajax-alerts.html.twig', [
                'alerts' => $alerts,
                'class' => $class,
                'dishOrFoodId' => $dishOrFoodId,
                'isSelected' => $isSelected,
                'rankMeal' => $rankMeal,
                'rankDish' => $rankDish,
            ]);
        }
    }

    /**
     * Met à jour l'alerte lorsqu'une portion d'un plat est modifiée.
     *
     * Appelle le service AlertFeature pour recalculer les alertes
     * après modification de la portion, puis redirige vers la liste des alertes.
     *
     * @param Request $request Requête HTTP
     * @param Dish $dish Plat concerné
     * @param int $nPortion Nouvelle portion
     * @param AlertFeature $alertFeature Service gérant les alertes
	 * 
     * @return Response Redirection vers la liste d'alertes
     */
    #[Route('/update-alert-on-dish-on-update-portion/{id}/{nPortion}', name: 'meal_day_update_alert_on_dish_on_update_portion', methods: ['GET'], requirements: ['id' => '\d+', 'nPortion' => '\d+'], options: ['expose' => true])]
    public function updateAlertOnDishOnUpdatePortion(Request $request, Dish $dish, int $nPortion, AlertFeature $alertFeature): Response
    {
        // Recalcule les alertes suite à la modification de portion
        $alertFeature->setAlertOnDishOrFoodQuantityUpdated(
            $dish,
            $nPortion,
            null,
            $request->query->get('rankMeal'),
            $request->query->get('rankDish')
        );

        // Redirection vers la liste des alertes pour ce plat
        return $this->redirectToRoute('alert_meal_list', [
            'class' => 'Dish',
            'dishOrFoodId' => $dish->getId(),
            'isSelected' => 0,
        ]);
    }

    /**
     * Met à jour l'alerte lorsqu'une quantité d'un aliment est modifiée.
     *
     * @param Request $request Requête HTTP
     * @param Food $food Aliment concerné
     * @param int $quantity Nouvelle quantité
     * @param string $unitMeasure Unité de mesure
     * @param AlertFeature $alertFeature Service gérant les alertes
	 * 
     * @return Response Redirection vers la liste d'alertes
     */
    #[Route('/update-alert-on-food-on-update-quantity/{id}/{quantity}/{unitMeasure}', name: 'meal_day_update_alert_on_food_on_update_quantity', methods: ['GET'], requirements: ['id' => '\d+', 'quantity' => '\d+', 'unitMeasure' => '[a-zA-Z0-9_-]+'], options: ['expose' => true])]
    public function updateAlertOnFoodOnUpdateQuantity(Request $request, Food $food, int $quantity, string $unitMeasure, AlertFeature $alertFeature): Response
    {
        // Recalcule les alertes suite à la modification de quantité
        $alertFeature->setAlertOnDishOrFoodQuantityUpdated(
            $food,
            $quantity,
            $unitMeasure,
            $request->query->get('rankMeal'),
            $request->query->get('rankDish')
        );

        // Redirection vers la liste des alertes pour cet aliment
        return $this->redirectToRoute('alert_meal_list', [
            'class' => 'Food',
            'dishOrFoodId' => $food->getId(),
            'isSelected' => 0,
        ]);
    }

    /**
     * Affiche les alertes hebdomadaires pour tous les FoodGroupParent.
     *
     * Calcule le niveau d'alerte pour chaque FGP en fonction des quantités consommées
     * et les regroupe par niveau pour l'affichage.
     *
     * @param Request $request Requête HTTP
     * @param EntityManagerInterface $manager Manager Doctrine pour récupérer les FGP
     * @param TokenStorageInterface $tokenStorage Stockage du token utilisateur
     * @param AlertFeature $alertFeature Service pour calculer le niveau d'alerte
     * @param array $quantitiesConsumed Quantités consommées par FGP sur la semaine
	 * 
     * @return Response Vue affichant les alertes hebdomadaires
     */
    #[Route('/show-on-week', name: 'alert_show_on_week', options: ['expose' => true])]
    public function showOnWeek(Request $request, EntityManagerInterface $manager, TokenStorageInterface $tokenStorage, AlertFeature $alertFeature, array $quantitiesConsumed): Response
    {
        $fgpLevel = [];

        foreach ($manager->getRepository(FoodGroupParent::class)->findAll() as $fgp) {
            // Calcul du niveau d'alerte pour chaque FGP (quotidien moyen)
            $level = $alertFeature->getLevel($quantitiesConsumed[$fgp->getAlias()] / 7, $fgp->getAlias());
            $fgpLevel[$level][] = $fgp->getId();
        }

        return $this->render('meals/week/alerts_on_week.html.twig', [
            'fgpLevel' => $fgpLevel,
            'quantitiesConsumed' => $quantitiesConsumed,
        ]);
    }

    /**
     * Debug : affiche toutes les données de session pour inspection.
     *
     * @param Request $request Requête HTTP
     * @return void
     */
    #[Route('/show-session', name: 'alert_show_session', methods: ['GET'])]
    public function showSession(Request $request): void
    {
        // Affiche toutes les données de session pour debug
        dd($request->getSession()->all());
    }
}