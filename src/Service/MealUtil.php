<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\Food;
use App\Entity\Meal;
use App\Entity\MealModel;
use App\Repository\DishRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\FoodGroupParentRepository;
use App\Repository\FoodRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * MealUtil.php
 *
 * Service utilitaire pour la gestion des repas (Meal/MealModel).
 *
 * Permet de :
 *  - Récupérer les groupes alimentaires présents dans les repas
 *  - Calculer l'énergie et les nutriments des plats et aliments
 *  - Gérer les sessions de repas (ajout, suppression, réorganisation)
 *  - Vérifier la présence de gluten et lactose
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class MealUtil
{
	public function __construct(
		private RequestStack $requestStack,                     // Pour accéder à la session et gérer les repas stockés
		private EntityManagerInterface $manager,               // Pour récupérer les entités via Doctrine
		private WeekAlertFeature $weekAlertFeature,           // Pour gérer les alertes hebdomadaires (lundi/vendredi)
		private EnergyHandler $energyHandler,                 // Pour calculer l'énergie d'un aliment ou plat
		private FoodUtil $foodUtil,                            // Pour gérer la conversion, recherche et filtrage d'aliments
		private DishUtil $dishUtil,                            // Pour récupérer les quantités de FoodGroupParents d’un plat
		private FoodGroupParentRepository $foodGroupParentRepository, // Pour récupérer tous les FoodGroupParents
		private DishRepository $dishRepository,               // Pour récupérer des plats spécifiques
		private FoodRepository $foodRepository,               // Pour récupérer des aliments spécifiques
	) {}

	public const TYPE_BREAKFAST = "meal.type.breakfast";
	public const TYPE_SNACK_MORNING = "meal.type.snack_morning";
	public const TYPE_LUNCH = "meal.type.lunch";
	public const TYPE_SNACK_AFTERNOON = "meal.type.snack_afternoon";
	public const TYPE_DINNER = "meal.type.dinner";

	/**
	 * Récupère la liste des FoodGroupParent principaux pour un ensemble
	 * de plats (Dish) et aliments (Food) donnés.
	 *
	 * @param array $dishAndFoods Tableau d'éléments contenant :
	 *                            - 'type' => 'Dish' ou 'Food'
	 *                            - 'id' => identifiant de l'élément
	 * 
	 * @return array Liste des IDs de FoodGroupParent principaux (principal = true)
	 */
	public function getListfgp(array $dishAndFoods): array
	{
		$results = [];

		foreach ($dishAndFoods as $element) {
			if ($element === null) {
				continue; // Ignore les éléments nuls
			}

			switch ($element['type']) {
				case 'Dish':
					// Récupère le plat depuis la BDD
					$dish = $this->manager->getRepository(Dish::class)->findOneById((int)$element['id']);

					// Pour chaque FoodGroupParent lié au plat
					foreach ($dish->getDishFoodGroupParents() as $dishFoodGroupParent) {
						$fgp = $dishFoodGroupParent->getFoodGroupParent();

						// Ajouter seulement si principal et pas déjà présent
						if ($fgp->getIsPrincipal() && !in_array($fgp->getId(), $results)) {
							$results[] = $fgp->getId();
						}
					}
					break;

				default: // Cas "Food"
					// Récupère l'aliment et son FoodGroupParent
					$food = $this->manager->getRepository(Food::class)->find((int)$element['id']);
					$fgp = $food->getFoodGroup()->getParent();

					// Ajouter seulement si principal et pas déjà présent
					if ($fgp->getIsPrincipal() && !in_array($fgp->getId(), $results)) {
						$results[] = $fgp->getId();
					}
					break;
			}
		}

		return $results;
	}

	/**
	 * Supprime un repas de la session en fonction de son rang ($rankMeal).
	 * 
	 * Si le repas supprimé n'est pas le dernier, les suivants sont décalés
	 * pour combler le "trou" dans la session.
	 *
	 * @param int $rankMeal Rang du repas à supprimer (0-based)
	 */
	public function removeMealSession(int $rankMeal): void
	{
		$session = $this->requestStack->getSession();

		// Supprime le repas spécifique
		$session->remove('_meal_day_' . $rankMeal);

		// Vérifie si plusieurs repas sont enregistrés
		if ($session->get('_meal_day_range') > 0) {

			// Décale tous les repas suivants d'une position vers le début
			for ($n = $rankMeal + 1; $n <= $session->get('_meal_day_range'); $n++) {
				$meal = $session->get('_meal_day_' . $n);
				$session->remove('_meal_day_' . $n);
				$session->set('_meal_day_' . ($n - 1), $meal);
			}

			// Met à jour le nombre total de repas dans la session
			$rangeMeal = $session->get('_meal_day_range') - 1;
			$session->set('_meal_day_range', $rangeMeal);
		} else {
			// Cas où il n'y avait qu'un seul repas : nettoyage complet
			$session->remove('_meal_day_range');
			$session->set('_meal_day_energy', 0);
			$session->remove('_meal_day_energy_evolution');
			$session->remove('_meal_day_alerts/_dishes_selected');
			$session->remove('_meal_day_alerts/_foods_selected');
			$session->remove('_meal_day_alerts/_dishes_not_selected');
			$session->remove('_meal_day_alerts/_foods_not_selected');
		}
	}

	/**
	 * Supprime tous les repas enregistrés dans la session.
	 *
	 * Cette méthode efface :
	 * - Tous les repas journaliers (_meal_day_{rank})
	 * - Les variables de suivi de l'énergie et des nutriments
	 * - Les alertes liées aux plats et aliments sélectionnés
	 * - Les repas de la semaine (_meal_{Y-m-d}) définis par WeekAlertFeature
	 */
	public function removeMealsSession(): void
	{
		$session = $this->requestStack->getSession();

		// Supprime tous les repas journaliers si la session contient un range
		if ($session->has('_meal_day_range')) {
			for ($rank = 0; $rank <= $session->get('_meal_day_range'); $rank++) {
				$session->remove('_meal_day_' . $rank);
			}

			// Nettoyage des informations globales sur les repas
			$session->remove('_meal_day_range');
			$session->set('_meal_day_energy', 0);
			$session->remove('_meal_day_evolution/energy');
			$session->remove('_meal_day_evolution/protein');
			$session->remove('_meal_day_evolution/carbohydrate');
			$session->remove('_meal_day_evolution/lipid');
			$session->remove('_meal_day_evolution/sodium');
			$session->remove('_meal_day_alerts/_dishes_selected');
			$session->remove('_meal_day_alerts/_foods_selected');
			$session->remove('_meal_day_alerts/_dishes_not_selected');
			$session->remove('_meal_day_alerts/_foods_not_selected');
		}

		// Supprime les repas enregistrés pour chaque jour de la semaine (lundi → vendredi)
		foreach ($this->weekAlertFeature->get_lundi_vendredi_from_week() as $dateOfDay) {
			if ($session->has('_meal_' . $dateOfDay['Y-m-d'])) {
				$session->remove('_meal_' . $dateOfDay['Y-m-d']);
			}
		}
	}

	/**
	 * Calcule l'énergie totale d'un repas ou d'un modèle de repas.
	 *
	 * Parcourt tous les plats et aliments du repas et additionne
	 * leur apport énergétique en fonction de la quantité et de l'unité.
	 *
	 * @param Meal|MealModel $meal L'objet repas ou modèle de repas
	 * 
	 * @return float L'énergie totale en kcal
	 */
	public function getEnergy(Meal|MealModel $meal): float
	{
		$energy = 0;

		// Parcours de tous les éléments (plats ou aliments) du repas
		foreach ($meal->getDishAndFoods() as $element) {
			$energy += $this->energyHandler->getEnergyForDishOrFoodSelected(
				$element['id'],             // identifiant du plat ou de l'aliment
				$element['type'],           // 'Dish' ou 'Food'
				$element['quantity'],       // quantité sélectionnée
				$element['unitMeasureAlias'] // unité utilisée (ex: 'g', 'ml', 'unit')
			);
		}

		return $energy;
	}

	/**
	 * Calcule la répartition des nutriments d'un repas.
	 *
	 * Parcourt tous les plats et aliments du repas et additionne
	 * leurs apports en protéines, lipides, glucides et sodium.
	 *
	 * @param Meal $meal L'objet repas
	 * 
	 * @return array Tableau associatif des nutriments avec clés :
	 *               'protein', 'lipid', 'carbohydrate', 'sodium'
	 */
	public function getNutrients(Meal $meal): array
	{
		// Initialisation des valeurs totales
		$results = [
			'protein' => 0,
			'lipid' => 0,
			'carbohydrate' => 0,
			'sodium' => 0,
		];

		// Parcours de tous les éléments (plats ou aliments) du repas
		foreach ($meal->getDishAndFoods() as $element) {
			// Récupération des nutriments pour l'élément en fonction de la quantité et de l'unité
			$nutrients = $this->foodUtil->getNutrientsForDishOrFoodSelected(
				$element['id'],             // identifiant du plat ou de l'aliment
				$element['type'],           // 'Dish' ou 'Food'
				$element['quantity'],       // quantité choisie
				$element['unitMeasureAlias'] // unité utilisée (ex: 'g', 'ml', 'unit')
			);

			// Ajout des nutriments de cet élément aux totaux
			$results['protein'] += $nutrients['protein'];
			$results['lipid'] += $nutrients['lipid'];
			$results['carbohydrate'] += $nutrients['carbohydrate'];
			$results['sodium'] += $nutrients['sodium'];
		}

		return $results;
	}

	/**
	 * Calcule la quantité de chaque groupe alimentaire parent dans un repas.
	 *
	 * @param Meal $meal L'objet repas
	 * 
	 * @return array Tableau associatif [alias du groupe parent => quantité totale en grammes]
	 */
	public function getFoodGroupParents(Meal $meal): array
	{
		// Récupère tous les groupes alimentaires parents
		$foodGroupParents = $this->foodGroupParentRepository->findAll();

		// Initialisation du tableau des résultats à 0 pour chaque groupe parent
		foreach ($foodGroupParents as $foodGroupParent) {
			$results[$foodGroupParent->getAlias()] = 0;
		}

		// Parcours de chaque élément du repas (plat ou aliment)
		foreach ($meal->getDishAndFoods() as $element) {

			if ('Dish' === $element['type']) {
				// ----- Cas plat -----
				$dish = $this->manager->getRepository(Dish::class)->findOneById((int)$element['id']);

				// Récupère les quantités par groupe parent pour la portion choisie
				$fgpValues = $this->dishUtil->getFoodGroupParentQuantitiesForNPortion($dish, $element['quantity']);

				// Ajoute ces valeurs au total du repas
				foreach ($foodGroupParents as $foodGroupParent) {
					$results[$foodGroupParent->getAlias()] += $fgpValues[$foodGroupParent->getAlias()];
				}
			} else {
				// ----- Cas aliment -----
				$food = $this->manager->getRepository(Food::class)->findOneById((int)$element['id']);

				// Conversion en grammes si l'unité n'est pas déjà en grammes
				if ('g' !== $element['unitMeasureAlias']) {
					$quantityG = $this->foodUtil->convertInGr($element['quantity'], $food, $element['unitMeasureAlias']);
				} else {
					$quantityG = $element['quantity'];
				}

				// Ajoute la quantité à son groupe alimentaire parent
				$results[$food->getFoodGroup()->getParent()->getAlias()] += $quantityG;
			}
		}

		return $results;
	}

	/**
	 * Vérifie la présence de gluten et de lactose dans un repas.
	 *
	 * Parcourt tous les éléments (plats ou aliments) du repas pour déterminer 
	 * si le repas contient du gluten, du lactose ou les deux.
	 *
	 * @param MealModel|Meal $meal L'objet représentant le repas ou un modèle de repas
	 * 
	 * @return array Tableau associatif avec deux clés : 'gluten' et 'lactose', 
	 *               indiquant la présence de ces composants.
	 */
	public function checkGlutenAndLactose(MealModel|Meal $meal): array
	{
		// Initialisation des indicateurs à false
		$containsGluten = false;
		$containsLactose = false;

		// Parcours de chaque élément du repas (plat ou aliment)
		foreach ($meal->getDishAndFoods() as $item) {
			$id   = (int) $item['id'];   // Identifiant du plat ou aliment
			$type = $item['type'];       // Type de l'élément ('Dish' ou 'Food')

			// ----- CAS DISH (Plat) -----
			if ($type === 'Dish') {
				$dish = $this->dishRepository->find($id); // Récupération du plat par ID

				if (!$dish) {
					continue; // Si le plat n'existe pas, passer au suivant
				}

				// Vérification de la présence de gluten dans ce plat
				if ($dish->getHaveGluten()) {
					$containsGluten = true;
				}

				// Vérification de la présence de lactose dans ce plat
				if ($dish->getHaveLactose()) {
					$containsLactose = true;
				}

				// Si les deux sont détectés, on peut arrêter la boucle, on retourne le résultat
				if ($containsGluten && $containsLactose) {
					return [
						'gluten' => true,
						'lactose' => true,
					];
				}
			}

			// ----- CAS FOOD (Aliment) -----
			if ($type === 'Food') {
				$food = $this->foodRepository->find($id); // Récupération de l'aliment par ID

				if (!$food) {
					continue; // Si l'aliment n'existe pas, passer au suivant
				}

				// Vérification de la présence de gluten dans cet aliment
				if ($food->getHaveGluten()) {
					$containsGluten = true;
				}

				// Vérification de la présence de lactose dans cet aliment
				if ($food->getHaveLactose()) {
					$containsLactose = true;
				}

				// Si les deux sont détectés, on retourne immédiatement le résultat
				if ($containsGluten && $containsLactose) {
					return [
						'gluten' => true,
						'lactose' => true,
					];
				}
			}
		}

		// Si on a fini la boucle sans trouver les deux, on retourne le statut final
		return [
			'gluten' => $containsGluten,
			'lactose' => $containsLactose,
		];
	}
}
