<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\Food;
use App\Entity\TypeMeal;
use App\Entity\UnitMeasure;
use App\Entity\RecommendedQuantity;
use App\Entity\FoodGroup\FoodGroupParent;
use App\Repository\MealRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Service\DishUtil;
use App\Service\FoodUtil;
use App\Service\WeekAlertFeature;

/**
 * QuantityTreatment.php
 * 
 * Service de traitement des quantités alimentaires pour un utilisateur.
 *
 * Fonctionnalités principales :
 *  - Calculer les quantités recommandées par groupe d’aliments pour un utilisateur en tenant compte
 *    des régimes/diets, ratios spécifiques et recommandations standardisées.
 *  - Traquer les quantités consommées par l'utilisateur à travers les plats et aliments enregistrés.
 *  - Fournir des métriques pour les semaines et les jours (restants, consommés, recommandations).
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class QuantityTreatment
{
	public function __construct(
		private Security $security,
		private RequestStack $requestStack,
		private EntityManagerInterface $manager,
		private DishUtil $dishUtil,
		private FoodUtil $foodUtil,
		private WeekAlertFeature $weekAlertFeature,
		private MealRepository $mealRepository,
	) {}

	/**
	 * Calcule les quantités recommandées par FoodGroupParent pour un utilisateur donné.
	 *
	 * Cette fonction réalise les étapes suivantes :
	 * 1. Récupère les quantités de référence pour chaque FoodGroupParent
	 *    en fonction de l'énergie quotidienne de l'utilisateur.
	 * 2. Ajuste ces quantités en fonction des régimes (diets) ou sous-régimes (subDiets)
	 *    associés à l'utilisateur. Chaque régime peut définir un ratio positif ou négatif
	 *    qui augmente ou diminue la quantité recommandée d'un groupe alimentaire.
	 * 3. Pour chaque FoodGroupParent, si plusieurs ratios existent, seule la valeur 
	 *    la plus extrême (la plus négative ou la plus positive) est appliquée.
	 *
	 * @param UserInterface $user L'utilisateur pour lequel on calcule les recommandations.
	 *                            L'objet User doit contenir les informations sur son
	 *                            énergie quotidienne et ses régimes alimentaires.
	 *
	 * @return array Un tableau associatif où les clés sont les alias des FoodGroupParent
	 *               et les valeurs sont les quantités recommandées ajustées, en grammes,
	 *               après application des ratios des régimes.
	 */
	public function calculRecommendedQuantities(UserInterface $user)
	{
		// Récupère l'utilisateur actuellement connecté
		$user = $this->security->getUser();

		// Initialisation des tableaux :
		// - $quantities : stocke les quantités recommandées finales par FoodGroupParent
		// - $ratioObjectNegative : stocke les ratios négatifs des régimes pour chaque FGP
		// - $ratioObjectPositive : stocke les ratios positifs des régimes pour chaque FGP
		// - $ratioNegativeMax : contiendra le ratio négatif le plus extrême pour chaque FGP
		// - $ratioPositiveMax : contiendra le ratio positif le plus extrême pour chaque FGP
		$quantities = $ratioObjectNegative = $ratioObjectPositive = $ratioNegativeMax = $ratioPositiveMax = [];

		// Boucle sur tous les groupes alimentaires principaux (FoodGroupParent)
		foreach ($this->manager->getRepository(FoodGroupParent::class)->findAll() as $fgp) {

			// Récupère la quantité recommandée standard pour ce FGP en fonction de l'énergie de l'utilisateur
			// Stocke dans $quantities pour calculs futurs et dans $quantitiesOriginal pour référence
			$quantities[$fgp->getAlias()] = $quantitiesOriginal[$fgp->getAlias()] = (int)$this->manager
				->getRepository(RecommendedQuantity::class)
				->findOneBy([
					'foodGroupParent' => $fgp,
					'energy' => $user->getEnergy()
				])
				->getQuantity();

			// Si l'utilisateur a des régimes définis
			if (!$user->getDiets()->isEmpty()) {

				// Parcours tous les régimes
				foreach ($user->getDiets() as $diet) {

					// Si le régime n'a pas de sous-régimes, on prend ses ratios
					// Sinon on prend les sous-régimes (subDiets) pour récupérer les ratios
					$ratios = $diet->getSubDiets()->isEmpty() ? $diet->getRatios() : $user->getSubDiets();

					// Boucle sur tous les ratios disponibles
					foreach ($ratios as $ratioObject) {

						// On ne considère que les ratios correspondant au FGP actuel
						if ($ratioObject->getFoodGroupParent() === $fgp) {

							// Si le ratio est négatif, on l'ajoute dans le tableau des ratios négatifs
							if ($ratioObject->getRatio() < 0) {
								$ratioObjectNegative[$fgp->getAlias()][] = $ratioObject->getRatio();
							}
							// Sinon on l'ajoute dans le tableau des ratios positifs
							else {
								$ratioObjectPositive[$fgp->getAlias()][] = $ratioObject->getRatio();
							}
						}
					}
				}
			}
		}

		// Pour chaque FGP ayant des ratios négatifs, on prend le ratio le plus extrême (le plus faible)
		foreach ($ratioObjectNegative as $fgpCode => $ratios) {
			sort($ratios);                     // Tri du plus petit au plus grand
			$ratioNegativeMax[$fgpCode] = $ratios[0]; // Premier élément = ratio négatif maximal
		}

		// Pour chaque FGP ayant des ratios positifs, on prend le ratio le plus extrême (le plus élevé)
		foreach ($ratioObjectPositive as $fgpCode => $ratios) {
			sort($ratios);                     // Tri du plus petit au plus grand
			$ratioPositiveMax[$fgpCode] = end($ratios); // Dernier élément = ratio positif maximal
		}

		// Ajuste les quantités recommandées finales en appliquant le ratio le plus extrême
		if (!empty($ratioNegativeMax) || !empty($ratioPositiveMax)) {

			foreach ($this->manager->getRepository(FoodGroupParent::class)->findAll() as $fgp) {

				// Si le FGP a un ratio négatif extrême, on applique la réduction
				if (isset($ratioNegativeMax[$fgp->getAlias()])) {
					$quantities[$fgp->getAlias()] += $quantities[$fgp->getAlias()] * ($ratioNegativeMax[$fgp->getAlias()] / 100);
				}
				// Sinon, si le FGP a un ratio positif extrême, on applique l'augmentation
				elseif (isset($ratioPositiveMax[$fgp->getAlias()])) {
					$quantities[$fgp->getAlias()] += $quantities[$fgp->getAlias()] * ($ratioPositiveMax[$fgp->getAlias()] / 100);
				}
			}
		}

		// Retourne le tableau final des quantités recommandées ajustées
		return $quantities;
	}

	/**
	 * Initialise les quantités consommées à zéro pour tous les groupes d'aliments (FoodGroupParent).
	 *
	 * Cette fonction parcourt tous les FoodGroupParent disponibles et crée un tableau associatif
	 * avec l'alias du groupe comme clé et 0 comme valeur, indiquant qu'aucune quantité n'a encore été consommée.
	 *
	 * @return array Tableau associatif [alias du FoodGroupParent => quantité consommée (0)]
	 */
	public function getQuantitiesConsumedNull(): array
	{
		$results = [];
		foreach ($this->manager->getRepository(FoodGroupParent::class)->findAll() as $fgp) {
			$results[$fgp->getAlias()] = 0;
		}
		return $results;
	}

	/**
	 * Calcule et met à jour les quantités consommées par FoodGroupParent pour un élément donné.
	 *
	 * Cette fonction prend en compte le type d'élément : 
	 * - Si c'est un plat (Dish), elle récupère les quantités par groupe d'aliments pour ce plat multiplié par le nombre de portions.
	 * - Si c'est un aliment (Food), elle convertit la quantité en grammes selon l'unité de mesure et l'ajoute au groupe correspondant.
	 *
	 * @param array $element Tableau représentant l'élément consommé, avec au moins les clés suivantes :
	 *                       - 'type' => 'Dish' | autre type (aliment)
	 *                       - 'id' => int, identifiant de l'élément
	 *                       - 'quantity' => float|int, quantité consommée
	 *                       - 'unitMeasure' => int|null, identifiant de l'unité pour les aliments
	 * @param array $quantitiesConsumed Tableau associatif des quantités déjà consommées par FoodGroupParent 
	 *                                  (clé = alias du groupe, valeur = quantité en grammes)
	 *
	 * @return array Tableau mis à jour des quantités consommées par FoodGroupParent
	 */
	public function getQuantitiesConsumed($element, array $quantitiesConsumed): array
	{
		switch ($element['type']) {
			case 'Dish':
				$dish = $this->manager->getRepository(Dish::class)->findOneById((int)$element['id']);
				foreach ($this->dishUtil->getFoodGroupParentQuantitiesForNPortion($dish, $element['quantity']) as $fgpCode => $quantity) {
					$quantitiesConsumed[$fgpCode] = ($quantitiesConsumed[$fgpCode] ?? 0) + $quantity;
				}
				break;
			default:
				$food = $this->manager->getRepository(Food::class)->findOneById((int)$element['id']);
				$fgpCode = $food->getFoodGroup()->getParent()->getAlias();
				$unitMeasure = $this->manager->getRepository(UnitMeasure::class)->findOneById((int)$element['unitMeasure']);
				$quantity = $this->foodUtil->convertInGr((float)$element['quantity'], $food, $unitMeasure);
				$quantitiesConsumed[$fgpCode] = ($quantitiesConsumed[$fgpCode] ?? 0) + $quantity;
				break;
		}

		return $quantitiesConsumed;
	}


	/**
	 * Calcule les quantités consommées par l'utilisateur dans la session pour tous les plats et aliments.
	 * 
	 * @param int|null $rankMeal Si renseigné, limite le calcul aux repas jusqu'à ce rang
	 * @param int|string $rankDish Si renseigné, limite le calcul à un plat spécifique (ou 'all' pour tous)
	 * 
	 * @return array Quantités consommées par FoodGroupParent
	 */
	public function getQuantitiesConsumedInSessionDishes($rankMeal = null, $rankDish = 'all')
	{
		$session = $this->requestStack->getSession();

		// Initialise les quantités à zéro pour chaque groupe
		$quantitiesConsumed = $this->getQuantitiesConsumedNull();

		$rankLastMeal = null === $rankMeal ? $session->get('_meal_day_range') : (int)$rankMeal;
		$rankDish = 'all' !== $rankDish  ? (int)$rankDish : null;

		// Parcours tous les repas jusqu'au rang souhaité
		for ($n = 0; $n <= $rankLastMeal; $n++) {
			if ($session->has('_meal_day_' . $n) && !empty($session->get('_meal_day_' . $n)['dishAndFoods'])) {
				foreach ($session->get('_meal_day_' . $n)['dishAndFoods'] as $rankElement => $element) {
					// Stop si on a atteint le plat cible dans le dernier repas
					if ($rankLastMeal === $n && 'all' !== $rankDish && $rankElement === $rankDish) {
						break 2;
					}

					// Ajoute les quantités consommées pour cet élément
					$quantitiesConsumed = $this->getQuantitiesConsumed($element, $quantitiesConsumed);
				}
			}
		}

		return $quantitiesConsumed;
	}

	/**
	 * Récupère tous les repas de l'utilisateur pour chaque jour de la semaine donnée.
	 * Tente de combiner repas en base et repas en session (non encore persistés).
	 *
	 * @param string $startingDate Date de référence pour la semaine
	 * 
	 * @return array Tableau de repas par jour et par type de repas
	 */
	public function getMealsPerDay($startingDate)
	{
		$user = $this->security->getUser();
		$session = $this->requestStack->getSession();

		$meals = []; // Initialise le tableau qui contiendra tous les repas de la semaine

		// Parcourt chaque jour de la semaine (du lundi au vendredi) à partir de la date donnée
		foreach ($this->weekAlertFeature->get_lundi_vendredi_from_week($startingDate) as $dateOfDay) {

			// Parcourt tous les types de repas (ex: petit-déjeuner, déjeuner, dîner)
			foreach ($this->manager->getRepository(TypeMeal::class)->findAll() as $typeMeal) {

				// Récupère en base les repas de l'utilisateur pour ce jour et ce type de repas
				$listMeal = $this->mealRepository->findBy([
					'eatedAt' => $dateOfDay['Y-m-d'], // Date du repas
					'type' => $typeMeal,              // Type de repas
					'user' => $user                    // Utilisateur courant
				]);

				// Si aucun repas n'est trouvé en base
				if (null === $listMeal || empty($listMeal)) {
					// On tente de récupérer le repas depuis la session (ex: ajout temporaire avant sauvegarde)
					if (
						$session->has('_meal_' . $dateOfDay['Y-m-d']) &&
						!empty($session->get('_meal_' . $dateOfDay['Y-m-d']))
					) {
						// Ajoute le repas récupéré depuis la session dans le tableau final
						$meals[$dateOfDay['l']] = $session->get('_meal_' . $dateOfDay['Y-m-d']);
					}
				} else {
					// Sinon, ajoute les repas récupérés depuis la base dans le tableau final
					// Le tableau est organisé par jour puis par type de repas
					$meals[$dateOfDay['l']][$typeMeal->getBackName()] = $listMeal;
				}
			}
		}

		return $meals;
	}

	/**
	 * Calcule les quantités consommées par FoodGroupParent pour l'ensemble de la semaine.
	 *
	 * @param string $startingDate Date de référence pour la semaine
	 * 
	 * @return array Quantités consommées par FoodGroupParent
	 */
	public function getQuantitiesConsumedOnWeek($startingDate)
	{
		$quantitiesConsumed = $this->getQuantitiesConsumedNull();
		// Initialise les quantités consommées pour chaque FoodGroupParent à zéro

		// Parcourt tous les jours de la semaine à partir de la date donnée
		foreach ($this->getMealsPerDay($startingDate) as $day => $listMeal) {

			// Parcourt chaque type de repas pour le jour courant (ex: petit-déjeuner, déjeuner, dîner)
			foreach ($listMeal as $typeMeal => $meals) {

				// Vérifie qu’il y a bien des repas pour ce type de repas
				if (!empty($meals)) {

					// Parcourt chaque repas existant
					foreach ($meals as $meal) {

						// Parcourt tous les éléments du repas (plats et aliments)
						foreach ($meal->getDishAndFoods() as $element) {

							// Calcule la quantité consommée pour cet élément et l’ajoute au total par FoodGroupParent
							$quantitiesConsumed = $this->getQuantitiesConsumed($element, $quantitiesConsumed);
						}
					}
				}
			}
		}

		return $quantitiesConsumed;
	}

	/**
	 * Calcule les quantités restantes à consommer pour chaque FoodGroupParent sur la semaine.
	 *
	 * @param string $startingDate Date de référence pour la semaine
	 * 
	 * @return array Quantités restantes par FoodGroupParent
	 */
	public function getRemainingQuantitiesOnWeek($startingDate)
	{
		$user = $this->security->getUser();

		$quantitiesConsumedOnWeek = $this->getQuantitiesConsumedOnWeek($startingDate);

		$remainingQuantities = [];
		foreach ($user->getQuantitiesRecommended() as $fgpCode => $quantity) {
			// Multiplie la quantité quotidienne recommandée par 7 jours et soustrait ce qui a été consommé
			$remainingQuantities[$fgpCode] = ($quantity * 7) - $quantitiesConsumedOnWeek[$fgpCode];
		}

		return $remainingQuantities;
	}

	/**
	 * Compte le nombre de jours de la semaine où aucun repas n'a été enregistré.
	 *
	 * @param string $startingDate Date de référence pour la semaine
	 * 
	 * @return int Nombre de jours sans repas
	 */
	public function countDayWithNoMeal($startingDate)
	{
		$i = 0;
		foreach ($this->getMealsPerDay($startingDate) as $listMeal) {
			if (empty($listMeal)) {
				$i++;
			}
		}
		return $i;
	}

	/**
	 * Calcule les quantités recommandées restantes par jour en fonction de ce qui a été consommé
	 * et du nombre de jours restants sans repas. Limite également les dépassements à 50% au-dessus
	 * de la recommandation quotidienne.
	 *
	 * @param string $startingDate Date de référence pour la semaine
	 * 
	 * @return array Quantités recommandées restantes par FoodGroupParent pour chaque jour
	 */
	public function remainingQuantitiesPerDay($startingDate)
	{
		$user = $this->security->getUser();

		$countDayWithNoMeal = $this->countDayWithNoMeal($startingDate);
		$quantitiesRecommended = $user->getQuantitiesRecommended();

		$remainingQuantitiesPerDay = [];
		foreach ($this->getRemainingQuantitiesOnWeek($startingDate) as $fgpCode => $quantity) {
			$perDay = $quantity / $countDayWithNoMeal;

			// Limite maximale = 1,5 fois la quantité quotidienne recommandée
			if ($perDay > ($quantitiesRecommended[$fgpCode] + ($quantitiesRecommended[$fgpCode] / 2))) {
				$remainingQuantitiesPerDay[$fgpCode] = $quantitiesRecommended[$fgpCode] + ($quantitiesRecommended[$fgpCode] / 2);
			} else {
				$remainingQuantitiesPerDay[$fgpCode] = $perDay;
			}
		}

		return $remainingQuantitiesPerDay;
	}
}
