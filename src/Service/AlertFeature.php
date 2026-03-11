<?php

namespace App\Service;

// Déclarations des dépendances utilisées par le service
use App\Repository\DishRepository;
use App\Repository\FoodRepository;
use App\Repository\LevelAlertRepository;
use App\Entity\Dish;
use App\Entity\Food;
use App\Service\DishUtil;
use App\Service\FoodUtil;
use App\Entity\Alert\LevelAlert;
use App\Entity\UnitMeasure;
use App\Service\QuantityTreatment;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\FoodGroup\FoodGroupParent;
use App\Repository\FoodGroupParentRepository;
use App\Repository\NutrientRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * AlertFeature.php
 *
 * Service principal pour gérer les alertes nutritionnelles.
 * Gère les alertes pour les plats, les aliments, les nutriments et les groupes alimentaires.
 * Il prend en compte les quantités consommées, les portions, et le profil utilisateur.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale 
 */
class AlertFeature
{
	public const NUTRITIONAL_ELEMENTS = [
		NutrientHandler::PROTEIN,
		NutrientHandler::LIPID,
		NutrientHandler::CARBOHYDRATE,
		NutrientHandler::SODIUM,
		EnergyHandler::ENERGY
	];

	// Injection des dépendances via constructeur
	public function __construct(
		private RequestStack $requestStack,       // Pour accéder à la session utilisateur
		private EntityManagerInterface $manager,  // Pour interagir avec la base de données
		private DishUtil $dishUtil,               // Utilitaires pour les plats
		private FoodUtil $foodUtil,               // Utilitaires pour les aliments
		private QuantityTreatment $quantityTreatment, // Calcul des quantités consommées
		private Security $security,               // Récupération de l'utilisateur courant
		private DishRepository $dishRepository,   // Repository pour les plats
		private FoodRepository $foodRepository,   // Repository pour les aliments
		private LevelAlertRepository $levelAlertRepository, // Repository pour les niveaux d'alerte
		private FoodGroupParentRepository $foodGroupParentRepository, // Repo groupes d'aliments
		private NutrientRepository $nutrientRepository,           // Repo nutriments
		private TranslatorInterface $translator,                  // Traduction des messages
	) {}


	/************* ALERTES DES PLATS/FOODS DE LA LISTE DES REPAS ************/

	/**
	 * Calcule et met en session les alertes pour tous les plats et aliments
	 * déjà sélectionnés dans les repas de la journée.
	 *
	 * Cette méthode parcourt chaque repas et chaque élément sélectionné,
	 * qu'il s'agisse d'un plat (Dish) ou d'un aliment simple (Food), puis :
	 *  - Calcule les quantités consommées par groupe alimentaire parent.
	 *  - Calcule les quantités de nutriments consommés (énergie, protéines, lipides, glucides, sodium).
	 *  - Génère les alertes nutritionnelles correspondantes pour chaque plat ou aliment.
	 *  - Compile et structure les alertes en une liste finale.
	 *  - Met à jour les totaux journaliers pour les nutriments et les groupes alimentaires.
	 *  - Stocke toutes les alertes dans la session utilisateur.
	 *
	 * @return void
	 */
	public function setAlertOnDishesAndFoodsAlreadySelected(): void
	{
		// Récupération de la session utilisateur
		$session = $this->requestStack->getSession();

		// Initialise les quantités consommées par groupe alimentaire parent à zéro
		$fgpQuantitiesConsumed = $this->quantityTreatment->getQuantitiesConsumedNull();

		// Initialise les totaux consommés pour chaque élément nutritionnel (protéines, lipides, etc.)
		$consumed = array_fill_keys(self::NUTRITIONAL_ELEMENTS, 0);

		// Tableaux qui contiendront les alertes générées
		$alertDishes = []; // alertes pour les plats
		$alertFoods = [];  // alertes pour les aliments

		// Liste globale de toutes les alertes générées pour la journée
		$finalListAlerts = [];

		// Parcours tous les repas de la journée
		for ($n = 0; $n <= $session->get('_meal_day_range'); $n++) {

			// Si le repas n'existe pas dans la session on passe au suivant
			if (!$session->has('_meal_day_' . $n)) {
				continue;
			}

			$meal = $session->get('_meal_day_' . $n);

			// Vérifie que le repas contient des plats ou aliments
			if (!array_key_exists('dishAndFoods', $meal)) {
				continue;
			}

			// Parcours chaque élément du repas (plat ou aliment)
			foreach ($meal['dishAndFoods'] as $index => $element) {

				// Détermine si l'élément est un plat
				$isDish = strtolower($element['type']) === 'dish';

				// Récupère l'entité correspondante (Dish ou Food)
				$entity = $isDish
					? $this->manager->getRepository(Dish::class)->findOneById((int)$element['id'])
					: $this->manager->getRepository(Food::class)->findOneById((int)$element['id']);

				/*
				|--------------------------------------------------------------------------
				| ALERTES SUR LES GROUPES ALIMENTAIRES PARENTS
				|--------------------------------------------------------------------------
				*/

				if ($isDish) {

					// Calcule les quantités de chaque groupe alimentaire parent pour la portion du plat
					$fgpQuantitiesForNPortion = $this->dishUtil
						->getFoodGroupParentQuantitiesForNPortion($entity, $element['quantity']);

					// Vérifie si une alerte doit être générée
					if (null !== $alerts = $this->getAlerts(
						'food_group_parent',
						$entity,
						$fgpQuantitiesConsumed,
						$element['quantity'],
						$fgpQuantitiesForNPortion,
						$element['unitMeasureAlias'],
						$finalListAlerts
					)) {
						$alertDishes[$n][$index]['food_group_parent'] = $alerts;
					}
				} else {

					// Récupère l’alias du groupe alimentaire parent de l’aliment
					$fgpAlias = $entity->getFoodGroup()->getParent()->getAlias();

					if (null !== $alerts = $this->getAlerts(
						'food_group_parent',
						$entity,
						$fgpQuantitiesConsumed,
						(float)$element['quantity'],
						null,
						(string)$element['unitMeasureAlias'],
						$finalListAlerts
					)) {
						$alertFoods[$n][$index]['food_group_parent'] = $alerts;
					}
				}

				/*
				|--------------------------------------------------------------------------
				| ALERTES SUR LES ELEMENTS NUTRITIONNELS (NUTRIMENTS + ENERGIE)
				|--------------------------------------------------------------------------
				*/

				foreach (self::NUTRITIONAL_ELEMENTS as $nutrient) {

					// Calcule la quantité du nutriment pour la portion sélectionnée
					$quantity = $this->extractDataFromDishOrFoodSelected(
						$nutrient,
						$entity,
						$element['quantity'],
						$element['unitMeasureAlias'] ?? null
					);

					// Vérifie si une alerte doit être générée
					if (null !== $alerts = $this->getAlerts(
						$nutrient,
						$entity,
						$consumed[$nutrient],
						$quantity,
						null,
						null,
						$finalListAlerts
					)) {

						if ($isDish) {
							$alertDishes[$n][$index][$nutrient] = $alerts;
						} else {
							$alertFoods[$n][$index][$nutrient] = $alerts;
						}
					}

					// Mise à jour du total consommé pour ce nutriment
					$consumed[$nutrient] += $quantity;
				}

				/*
				|--------------------------------------------------------------------------
				| COMPILATION DES ALERTES
				|--------------------------------------------------------------------------
				*/

				// Compile les alertes du plat
				if ($isDish && isset($alertDishes[$n][$index])) {
					$alertDishes[$n][$index] = $this->compileAlertsInformation($alertDishes[$n][$index]);
				}

				// Compile les alertes de l'aliment
				if (!$isDish && isset($alertFoods[$n][$index])) {
					$alertFoods[$n][$index] = $this->compileAlertsInformation($alertFoods[$n][$index]);
				}

				/*
				|--------------------------------------------------------------------------
				| MISE A JOUR DES QUANTITES CONSOMMEES PAR GROUPE ALIMENTAIRE
				|--------------------------------------------------------------------------
				*/

				if ($isDish) {

					// Ajoute les quantités du plat aux totaux journaliers
					foreach ($fgpQuantitiesForNPortion as $fgpAlias => $quantity) {
						$fgpQuantitiesConsumed[$fgpAlias] =
							($fgpQuantitiesConsumed[$fgpAlias] ?? 0) + $quantity;
					}
				} else {

					// Conversion de la quantité consommée en grammes
					$fgpAlias = $entity->getFoodGroup()->getParent()->getAlias();

					$quantityInGr = $this->foodUtil->convertInGr(
						(float)$element['quantity'],
						$entity,
						(string)$element['unitMeasureAlias']
					);

					// Mise à jour du total consommé pour ce groupe alimentaire
					$fgpQuantitiesConsumed[$fgpAlias] =
						($fgpQuantitiesConsumed[$fgpAlias] ?? 0) + $quantityInGr;
				}
			}
		}

		/*
		|--------------------------------------------------------------------------
		| STOCKAGE DES ALERTES DANS LA SESSION
		|--------------------------------------------------------------------------
		*/

		$session->set('_meal_day_alerts/_final_list', $finalListAlerts);
		$session->set('_meal_day_alerts/_dishes_selected', $alertDishes);
		$session->set('_meal_day_alerts/_foods_selected', $alertFoods);
	}



	/********** ALERTES DES PLATS/FOODS QUE L'ON VA CHOISIR DANS LA LISTE DE LA FENETRE MODALE ***********/

	/**
	 * Calcule et stocke les alertes nutritionnelles pour un plat ou un aliment
	 * qui est sur le point d'être sélectionné pour un repas donné.
	 *
	 * Cette fonction :
	 *  - Récupère la session utilisateur et les quantités déjà consommées pour le plat/repas.
	 *  - Récupère l'entité Dish ou Food correspondante.
	 *  - Calcule les alertes (protéines, lipides, glucides, sodium, énergie, groupes alimentaires).
	 *  - Met à jour les alertes dans la session pour les plats ou aliments non sélectionnés.
	 *
	 * @param int   $rankMeal  L'indice du repas dans la journée.
	 * @param int   $rankDish  L'indice du plat dans le repas.
	 * @param array $item      Tableau contenant les informations de l'élément à sélectionner
	 *                         (clé 'item_type' => 'Dish'|'Food', clé 'id' => identifiant).
	 *
	 * @return array|null      Les alertes calculées pour le plat ou l'aliment, ou null si aucune.
	 *
	 * @throws \Error          Si l'entité Dish ou Food n'existe pas en base.
	 */
	public function setAlertOnSingleDishOrFoodAboutTobeSelected(int $rankMeal, int $rankDish, array $item): ?array
	{
		$session = $this->requestStack->getSession();
		// Récupère la session utilisateur pour accéder aux repas, plats et aliments.

		$alertDishes = [];
		$alertFoods  = [];
		// Tableaux temporaires pour stocker les alertes du plat ou de l’aliment sélectionné.

		// Quantités déjà consommées pour ce repas/plat
		$fgpQuantitiesConsumed = $this->quantityTreatment->getQuantitiesConsumedInSessionDishes($rankMeal, $rankDish);
		// Récupère les quantités déjà consommées par groupe alimentaire parent pour ce plat/repas spécifique.

		$nutrientsConsumed = [];

		foreach (self::NUTRITIONAL_ELEMENTS as $element) {
			$nutrientsConsumed[$element] =
				$session->get('_meal_day_evolution/' . $element)[$rankMeal][$rankDish] ?? 0;
		}
		// Récupère les quantités déjà consommées de chaque nutriment pour ce plat/repas, avec zéro par défaut si non défini.

		$type = $item['item_type'];
		// Détermine si l’élément à sélectionner est un plat (Dish) ou un aliment (Food).

		// Récupérer l'entité Dish ou Food
		$entity = $type === 'Dish'
			? $this->dishRepository->findOneById($item['id'])
			: $this->foodRepository->findOneById($item['id']);
		// Récupère l’entité correspondante depuis le repository en fonction du type.

		if (!$entity) {
			throw new \Error(sprintf('Aucun plat ou aliment %d', $item['id']));
			// Si l’entité n’existe pas en base, on déclenche une erreur.
		};

		// Calcul des alertes
		$alerts = $this->calculateAlertsForEntityAboutToBeSelected(
			$rankMeal,
			$rankDish,
			$entity,
			$type,
			$fgpQuantitiesConsumed,
			$nutrientsConsumed
		);
		// Appelle la fonction qui calcule les alertes pour le plat/aliment à sélectionner en tenant compte
		// des quantités déjà consommées et des nutriments du plat/aliment.

		if ($alerts !== null) {
			// Stocker dans le tableau correspondant
			if ($type === 'Dish') {
				$alertDishes[$entity->getId()] = $alerts;
				// Si c’est un plat, ajoute les alertes dans le tableau des plats.
			} else {
				$alertFoods[$entity->getId()] = $alerts;
				// Si c’est un aliment, ajoute les alertes dans le tableau des aliments.
			}
		}

		$existingDishesAlerts = $session->get('_meal_day_alerts/_dishes_not_selected', []);
		$existingFoodsAlerts = $session->get('_meal_day_alerts/_foods_not_selected', []);
		// Récupère les alertes déjà existantes pour les plats/aliments non sélectionnés depuis la session.

		// DISHES
		$mergedDishesAlerts = $existingDishesAlerts + $alertDishes;
		$session->set('_meal_day_alerts/_dishes_not_selected', $mergedDishesAlerts);
		// Fusionne les nouvelles alertes avec celles existantes et les stocke dans la session pour les plats.

		// FOODS
		$mergedFoodsAlerts = $existingFoodsAlerts + $alertFoods;
		$session->set('_meal_day_alerts/_foods_not_selected', $mergedFoodsAlerts);
		// Fusionne les nouvelles alertes avec celles existantes et les stocke dans la session pour les aliments.

		return $alerts;
		// Retourne les alertes calculées pour ce plat ou aliment, ou null si aucune.
	}

	/**
	 * Met à jour les alertes nutritionnelles lorsqu’un plat (Dish) ou un aliment (Food)
	 * voit sa quantité modifiée dans un repas.
	 *
	 * Cette fonction :
	 * - Récupère les quantités déjà consommées pour le repas et le plat/aliment concerné.
	 * - Calcule les alertes pour les groupes alimentaires parents, l’énergie, les protéines,
	 *   les lipides, les glucides et le sodium.
	 * - Compile les alertes avec les messages et les niveaux les plus élevés.
	 * - Met à jour les alertes correspondantes dans la session pour les plats ou aliments
	 *   non sélectionnés.
	 *
	 * @param Dish|Food|null $object        L’entité Dish ou Food concernée, ou null si non définie.
	 * @param float          $quantityOrPortion Quantité ou portion modifiée.
	 * @param int|null       $unitMeasureId ID de l’unité de mesure (obligatoire pour Food).
	 * @param int|null       $rankMeal       Index du repas dans la journée.
	 * @param int|null       $rankDish       Index du plat/aliment dans le repas.
	 *
	 * @return void
	 */
	public function setAlertOnDishOrFoodQuantityUpdated(
		Dish|Food|null $object = null,
		float $quantityOrPortion,
		null|int $unitMeasureId = null,
		$rankMeal = null,
		$rankDish = null
	) {
		// Récupère la session utilisateur pour accéder aux informations de repas et aux alertes existantes.
		$session = $this->requestStack->getSession();

		// Récupère les quantités déjà consommées pour chaque groupe alimentaire parent pour ce repas/plat.
		$fgpQuantitiesConsumed = $this->quantityTreatment->getQuantitiesConsumedInSessionDishes($rankMeal, $rankDish);

		/*
		|--------------------------------------------------------------------------
		| RECUPERATION DES CONSOMMATIONS ACTUELLES (NUTRIMENTS + ENERGIE)
		|--------------------------------------------------------------------------
		*/

		// Initialise un tableau contenant les consommations actuelles
		$consumed = [];

		foreach (self::NUTRITIONAL_ELEMENTS as $element) {

			$consumed[$element] = (
				$session->has('_meal_day_evolution/' . $element)
				&& isset($session->get('_meal_day_evolution/' . $element)[$rankMeal][$rankDish])
			)
				? $session->get('_meal_day_evolution/' . $element)[$rankMeal][$rankDish]
				: 0;

			// Récupère la quantité déjà consommée pour cet élément nutritionnel
			// (énergie, protéines, lipides, glucides, sodium) ou 0 par défaut.
		}

		/*
		|--------------------------------------------------------------------------
		| CAS OU L'OBJET EST UN PLAT
		|--------------------------------------------------------------------------
		*/

		if ($object instanceof Dish) {

			$dish = $object;

			// Initialise le tableau pour stocker les alertes du plat
			$alertDishes = [];

			/*
			|--------------------------------------------------------------------------
			| ALERTES SUR LES GROUPES PARENTS D'ALIMENTS
			|--------------------------------------------------------------------------
			*/

			// Calcule les quantités par groupe alimentaire parent pour la portion du plat
			$fgpQuantitiesForNPortion = $this->dishUtil
				->getFoodGroupParentQuantitiesForNPortion($dish, $quantityOrPortion);

			if (null !== $listAlertFgp = $this->getAlerts(
				'food_group_parent',
				$dish,
				$fgpQuantitiesConsumed,
				$quantityOrPortion,
				$fgpQuantitiesForNPortion
			)) {

				$alertDishes[$dish->getId()]["food_group_parent"] = $listAlertFgp;
				// Ajoute l'alerte sur le groupe alimentaire parent si nécessaire.
			}

			/*
			|--------------------------------------------------------------------------
			| ALERTES NUTRITIONNELLES (ENERGIE + NUTRIMENTS)
			|--------------------------------------------------------------------------
			*/

			foreach (self::NUTRITIONAL_ELEMENTS as $element) {

				// Calcule la quantité du nutriment pour la portion du plat
				$quantity = $this->extractDataFromDishOrFoodSelected(
					$element,
					$dish,
					$quantityOrPortion
				);

				// Vérifie si une alerte doit être générée
				if (null !== $alerts = $this->getAlerts(
					$element,
					$dish,
					$consumed[$element],
					$quantity
				)) {

					$alertDishes[$dish->getId()][$element] = $alerts;
				}
			}

			/*
			|--------------------------------------------------------------------------
			| COMPILATION DES ALERTES
			|--------------------------------------------------------------------------
			*/

			// Compile toutes les alertes du plat en une structure unique avec messages et niveaux
			if (isset($alertDishes[$dish->getId()])) {
				$alertDishes[$dish->getId()] =
					$this->compileAlertsInformation($alertDishes[$dish->getId()]);
			}

			/*
			|--------------------------------------------------------------------------
			| MISE A JOUR DES ALERTES DANS LA SESSION
			|--------------------------------------------------------------------------
			*/

			// Récupère les alertes existantes pour les plats non sélectionnés
			$alertsInSession = $session->get('_meal_day_alerts/_dishes_not_selected', []);

			if (!empty($alertDishes)) {

				// Met à jour ou ajoute l'alerte du plat dans la session
				$alertsInSession[$dish->getId()] = $alertDishes[$dish->getId()];
			} else {

				// Si plus d'alerte, supprime l'alerte précédente du plat
				unset($alertsInSession[$dish->getId()]);
			}

			// Sauvegarde les alertes mises à jour dans la session
			$session->set('_meal_day_alerts/_dishes_not_selected', $alertsInSession);
		}

		/*
		|--------------------------------------------------------------------------
		| CAS OU L'OBJET EST UN ALIMENT
		|--------------------------------------------------------------------------
		*/

		if ($object instanceof Food) {

			$food = $object;

			// Initialise le tableau pour stocker les alertes de l’aliment
			$alertFoods = [];

			// Récupère l’unité de mesure correspondante
			$unitMeasure = $this->manager
				->getRepository(UnitMeasure::class)
				->findOneById($unitMeasureId);

			$unitAlias = $unitMeasure->getAlias();

			/*
			|--------------------------------------------------------------------------
			| ALERTES SUR LES GROUPES PARENTS D'ALIMENTS
			|--------------------------------------------------------------------------
			*/

			if (null !== $listAlertFgp = $this->getAlerts(
				'food_group_parent',
				$food,
				$fgpQuantitiesConsumed,
				$quantityOrPortion,
				null,
				$unitAlias
			)) {

				$alertFoods[$food->getId()]["food_group_parent"] = $listAlertFgp;
			}

			/*
			|--------------------------------------------------------------------------
			| ALERTES NUTRITIONNELLES (ENERGIE + NUTRIMENTS)
			|--------------------------------------------------------------------------
			*/

			foreach (self::NUTRITIONAL_ELEMENTS as $element) {

				// Calcule la quantité du nutriment pour la portion choisie
				$quantity = $this->extractDataFromDishOrFoodSelected(
					$element,
					$food,
					$quantityOrPortion,
					$unitAlias
				);

				// Vérifie si une alerte doit être générée
				if (null !== $alerts = $this->getAlerts(
					$element,
					$food,
					$consumed[$element],
					$quantity
				)) {

					$alertFoods[$food->getId()][$element] = $alerts;
				}
			}

			/*
			|--------------------------------------------------------------------------
			| COMPILATION DES ALERTES
			|--------------------------------------------------------------------------
			*/

			// Compile toutes les alertes de l’aliment en une structure unique
			if (isset($alertFoods[$food->getId()])) {

				$alertFoods[$food->getId()] =
					$this->compileAlertsInformation($alertFoods[$food->getId()]);
			}

			/*
			|--------------------------------------------------------------------------
			| MISE A JOUR DES ALERTES DANS LA SESSION
			|--------------------------------------------------------------------------
			*/

			// Récupère les alertes existantes pour les aliments non sélectionnés
			$alertsInSession = $session->get('_meal_day_alerts/_foods_not_selected', []);

			if (!empty($alertFoods)) {

				// Met à jour ou ajoute l'alerte de l'aliment dans la session
				$alertsInSession[$food->getId()] = $alertFoods[$food->getId()];
			} else {

				// Si plus d'alerte, supprime l'alerte précédente
				unset($alertsInSession[$food->getId()]);
			}

			// Sauvegarde les alertes mises à jour dans la session
			$session->set('_meal_day_alerts/_foods_not_selected', $alertsInSession);
		}
	}

	/********************************** FUNCTIONS **********************************/

	/**
	 * Génère les alertes nutritionnelles pour un plat (Dish) ou un aliment (Food)
	 * ou pour les groupes alimentaires parents, en fonction des quantités consommées
	 * et des recommandations de l’utilisateur.
	 *
	 * Cette fonction :
	 * - Vérifie si l’objet est un plat ou un aliment et convertit la quantité si nécessaire.
	 * - Calcule les alertes pour les groupes alimentaires parents si le sujet est 'food_group_parent'.
	 * - Calcule les alertes pour les nutriments (énergie, protéines, lipides, glucides, sodium) sinon.
	 * - Compile les messages et niveaux d’alerte.
	 * - Met à jour le tableau global `$finalListAlerts` avec les informations des alertes.
	 *
	 * @param string             $subject                                 Sujet de l’alerte ('food_group_parent', 'energy', 'protein', 'lipid', 'carbohydrate', 'sodium').
	 * @param Dish|Food|null     $object                                  Entité concernée (plat ou aliment), ou null.
	 * @param array|int|float    $quantityOrFgpQuantitiesOrEnergyConsumed Quantités déjà consommées ou totaux pour les FGP ou énergie.
	 * @param array|float        $quantityOrPortionOrEnergyAdded         Quantité ajoutée du plat/aliment ou énergie ajoutée.
	 * @param array|null         $fgpQuantitiesForNPortionAdded          Quantités des groupes alimentaires parents pour un plat (Dish).
	 * @param string|null        $unitMeasureAlias                        Alias de l’unité de mesure (pour Food).
	 * @param array|null         $finalListAlerts                         Référence vers le tableau global des alertes pour stockage.
	 *
	 * @return array|null Tableau contenant les messages et niveaux d’alerte, ou null si aucune alerte.
	 */
	public function getAlerts(
		string $subject,
		Dish|Food|null $object = null,
		array|int|float $quantityOrFgpQuantitiesOrEnergyConsumed = null,
		array|float $quantityOrPortionOrEnergyAdded,
		?array $fgpQuantitiesForNPortionAdded = null,
		?string $unitMeasureAlias = null,
		?array &$finalListAlerts = []
	): ?array {

		/** @var App/Entity/User|null $user */
		$user = $this->security->getUser();

		$listAlert = [];
		// Initialise le tableau des alertes pour cet objet ou nutriment.

		$session = $this->requestStack->getSession();
		// Récupère la session utilisateur pour accéder aux données et recommandations.

		if ($object instanceof Food && null !== $unitMeasureAlias && 'g' !== $unitMeasureAlias) {
			$quantityOrPortionOrEnergyAdded = $this->foodUtil->convertInGr($quantityOrPortionOrEnergyAdded, $object, $unitMeasureAlias);
			// Convertit la quantité de l’aliment en grammes si elle n’est pas déjà en grammes.
		}

		// ALERTES SUR LES QUANTITES DE GROUPES D'ALIMENT
		if ('food_group_parent' === $subject) {
			$fgpQuantitiesRecommended = $user->getRecommendedQuantities();
			// Récupère les quantités recommandées par l’utilisateur pour chaque groupe parent.

			foreach ($fgpQuantitiesRecommended as $fgpAlias => $value) {
				// Vérifie si le plat ou l’aliment appartient à ce groupe alimentaire.
				if (
					($object instanceof Food && $fgpAlias === $object->getFoodGroup()->getParent()->getAlias())
					|| ($object instanceof Dish && $fgpQuantitiesForNPortionAdded[$fgpAlias] > 0)
				) {
					if ($object instanceof Dish) {
						$alert = $this->createAlert($subject, $quantityOrFgpQuantitiesOrEnergyConsumed[$fgpAlias], $fgpQuantitiesForNPortionAdded[$fgpAlias], $fgpQuantitiesRecommended[$fgpAlias], $fgpAlias);
						// Crée une alerte pour le plat en fonction de la quantité déjà consommée, ajoutée et recommandée.
					}

					if ($object instanceof Food) {
						$alert = $this->createAlert($subject, $quantityOrFgpQuantitiesOrEnergyConsumed[$fgpAlias], $quantityOrPortionOrEnergyAdded, $fgpQuantitiesRecommended[$fgpAlias], $fgpAlias);
						// Crée une alerte pour l’aliment.
					}

					if (null !== $alert) {
						$fgp = $this->manager->getRepository(FoodGroupParent::class)->findOneByAlias($fgpAlias);
						$listAlert['messages'][] = sprintf(LevelAlert::MESSAGE_FGP_NOT_RECOMMENDED, $alert->getPlaceholderText(), strtolower($fgp->getName()));
						$listAlert['levels'][] = $alert->getPriority();
						$finalListAlerts[$subject][$fgpAlias] = [
							"message" => sprintf(LevelAlert::MESSAGE_FGP_NOT_RECOMMENDED, $alert->getPlaceholderText(), strtolower($fgp->getName())),
							"code" => $alert->getCode(),
						];
						// Ajoute le message et le niveau d’alerte au tableau des alertes.
					}
				}
			}
		} else {
			// ALERTES SUR LES NUTRIMENTS (ENERGY, PROTEIN, LIPID, CARBOHYDRATE, SODIUM)
			$propertyAccessor = PropertyAccess::createPropertyAccessor();
			$quantityOrEnergyDailyRecommended = (int)$propertyAccessor->getValue($this->security->getUser(), $subject);
			// Récupère la valeur recommandée de l’utilisateur pour ce nutriment.

			if (null !== $alert = $this->createAlert($subject, $quantityOrFgpQuantitiesOrEnergyConsumed, $quantityOrPortionOrEnergyAdded, $quantityOrEnergyDailyRecommended)) {
				if ('energy' === $subject) {
					$message = sprintf(LevelAlert::MESSAGE_ENERGY_NOT_RECOMMENDED, $alert->getPlaceholderText());
				} else {
					$message = sprintf(LevelAlert::MESSAGE_NUTRIENT_NOT_RECOMMENDED, $alert->getPlaceholderText(), $this->translator->trans($subject, [], 'nutrient'));
				}
				$listAlert['messages'][] = $message;
				$listAlert['levels'][] = $alert->getPriority();
				$finalListAlerts[$subject] = [
					"message" => $message,
					"code" => $alert->getCode(),
				];
				// Ajoute l’alerte pour le nutriment avec le message et le code.
			}
		}

		if (!empty($listAlert)) {
			ksort($listAlert);
			return $listAlert;
			// Retourne le tableau des alertes trié si au moins une alerte est présente.
		}

		return null;
		// Retourne null si aucune alerte n’a été générée.
	}

	/**
	 * Calcule la quantité d’un nutriment ou de l’énergie pour un aliment ou un plat donné.
	 *
	 * Cette fonction prend un élément sélectionné (Food ou Dish), la quantité choisie,
	 * et éventuellement l’unité de mesure. Elle retourne la valeur du nutriment ou de l’énergie
	 * calculée en fonction de la portion.
	 *
	 * @param string $type Type de nutriment ou 'energy' à extraire (ex : 'protein', 'lipid', 'carbohydrate', 'sodium', 'energy').
	 * @param Food|Dish $element L’entité Aliment ou Plat sélectionné.
	 * @param float $quantity Quantité ou portion sélectionnée par l’utilisateur.
	 * @param string|null $unitMeasureAlias Optionnel. Alias de l’unité de mesure si différent de grammes (ex : 'mg', 'ml').
	 *
	 * @return int|null La quantité calculée du nutriment pour l’élément donné.
	 */
	public function extractDataFromDishOrFoodSelected(string $type, Food|Dish $element, float $quantity, ?string $unitMeasureAlias = null): ?int
	{
		$result = 0;
		// Initialise le résultat à zéro

		$propertyAccessor = PropertyAccess::createPropertyAccessor();
		// Crée un PropertyAccessor pour lire dynamiquement les propriétés de l’entité

		if ($element instanceof Food) {
			if (null !== $unitMeasureAlias && 'g' !== $unitMeasureAlias) {
				$quantity = $this->foodUtil->convertInGr($quantity, $element, $unitMeasureAlias);
				// Convertit la quantité dans l’unité standard (grammes) si nécessaire
			}
			$result = ($quantity * (int)$propertyAccessor->getValue($element, $type)) / 100;
			// Calcule la quantité du nutriment : proportion du nutriment pour la portion donnée

			return $result;
			// Retourne le résultat pour un aliment simple
		}

		if ($element instanceof Dish) {
			$result = 0;
			foreach ($element->getDishFoods()->toArray() as $dishFood) {
				$quantiteGr = ($dishFood->getQuantityG() * $quantity) / $element->getLengthPersonForRecipe();
				// Convertit la portion du plat en grammes pour chaque aliment composant le plat

				$result += ($quantiteGr * $propertyAccessor->getValue($dishFood->getFood(), $type)) / 100;
				// Ajoute la contribution de chaque aliment au total du plat pour ce nutriment
			}

			return $result;
			// Retourne le total du nutriment pour le plat entier
		}

		return $result;
		// Retourne 0 si l’élément n’est ni Food ni Dish (sécurité)
	}

	/**
	 * Calcule et met en session l’évolution de l’énergie et des nutriments
	 * pour tous les plats et aliments sélectionnés au cours de la journée.
	 *
	 * Cette méthode parcourt chaque repas, puis chaque plat ou aliment du repas,
	 * calcule la contribution en énergie, protéines, lipides, glucides et sodium
	 * pour chaque portion, et met à jour les totaux cumulés pour la journée.
	 *
	 * @return bool Retourne true si le calcul a été effectué avec succès.
	 */
	public function setEnergyAndNutrientsDataSession(): bool
	{
		// Récupère la session utilisateur pour accéder aux données des repas
		$session = $this->requestStack->getSession();

		if ($session->has('_meal_day_range')) {
			// Vérifie si la session contient la plage des repas de la journée

			/*
			|--------------------------------------------------------------------------
			| INITIALISATION DES TOTAUX JOURNALIERS
			|--------------------------------------------------------------------------
			*/

			// Initialise les totaux journaliers pour chaque élément nutritionnel
			$dayTotals = array_fill_keys(self::NUTRITIONAL_ELEMENTS, 0);

			// Initialise les tableaux d’évolution cumulée
			$evolutions = [];

			foreach (self::NUTRITIONAL_ELEMENTS as $element) {
				$evolutions[$element][0][0] = 0;
			}

			/*
			|--------------------------------------------------------------------------
			| PARCOURS DES REPAS DE LA JOURNEE
			|--------------------------------------------------------------------------
			*/

			for ($i = 0; $i <= $session->get('_meal_day_range'); $i++) {

				// Initialise les totaux pour le repas actuel
				$mealTotals = array_fill_keys(self::NUTRITIONAL_ELEMENTS, 0);

				// Récupère les informations du repas actuel
				$meal = $session->get('_meal_day_' . $i);

				/*
				|--------------------------------------------------------------------------
				| INITIALISATION DE L'EVOLUTION AU DEBUT DU REPAS
				|--------------------------------------------------------------------------
				*/

				if ($i > 0) {

					// Initialise le premier index avec la fin du repas précédent
					foreach (self::NUTRITIONAL_ELEMENTS as $element) {
						$evolutions[$element][$i][0] = end($evolutions[$element][$i - 1]);
					}
				}

				/*
				|--------------------------------------------------------------------------
				| PARCOURS DES PLATS ET ALIMENTS DU REPAS
				|--------------------------------------------------------------------------
				*/

				if (isset($meal['dishAndFoods'])) {

					// Récupère la liste des plats et aliments du repas
					$dishAndFoods = $meal['dishAndFoods'];

					foreach ($dishAndFoods as $j => $dishOrFood) {

						// Détermine si l'élément est un plat ou un aliment
						$repo = (
							'Dish' === $dishOrFood['type']
							|| 'dish' === $dishOrFood['type']
						) ? $this->dishRepository : $this->foodRepository;

						// Récupère l’entité Dish ou Food depuis la base de données
						$item = $repo->findOneById((int)$dishOrFood['id']);

						/*
						|--------------------------------------------------------------------------
						| CALCUL DES NUTRIMENTS + ENERGIE
						|--------------------------------------------------------------------------
						*/

						foreach (self::NUTRITIONAL_ELEMENTS as $element) {

							// Calcule la quantité du nutriment pour cet aliment/plat
							$quantity = $this->extractDataFromDishOrFoodSelected(
								$element,
								$item,
								(float)$dishOrFood['quantity'],
								(string)$dishOrFood['unitMeasureAlias']
							);

							// Cumule la quantité pour le repas
							$mealTotals[$element] += $quantity;

							// Cumule la quantité pour la journée
							$dayTotals[$element] += $quantity;

							// Stocke l'évolution cumulée
							$evolutions[$element][$i][$j + 1] = $dayTotals[$element];
						}
					}
				}

				/*
				|--------------------------------------------------------------------------
				| MISE A JOUR DE L'ENERGIE DU REPAS
				|--------------------------------------------------------------------------
				*/

				// Met à jour l'énergie totale du repas dans la session
				$meal['energy'] = $mealTotals['energy'];
				$session->set('_meal_day_' . $i, $meal);
			}

			/*
			|--------------------------------------------------------------------------
			| ENREGISTREMENT DES EVOLUTIONS EN SESSION
			|--------------------------------------------------------------------------
			*/

			// Stocke l’évolution journalière cumulée pour chaque élément nutritionnel
			foreach (self::NUTRITIONAL_ELEMENTS as $element) {

				$session->set(
					'_meal_day_evolution/' . $element,
					$evolutions[$element]
				);
			}

			/*
			|--------------------------------------------------------------------------
			| TOTAL ENERGETIQUE JOURNALIER
			|--------------------------------------------------------------------------
			*/

			// Stocke l'énergie totale consommée dans la journée
			$session->set('_meal_day_energy', $dayTotals['energy']);
		}

		// Indique que le calcul et l’enregistrement en session ont été effectués
		return true;
	}

	/**
	 * Calcule le bilan des alertes nutritionnelles pour l'utilisateur.
	 *
	 * Cette méthode compare les moyennes journalières consommées en énergie, nutriments
	 * et quantités de groupes alimentaires parents (FGP) avec les recommandations de l'utilisateur.
	 * Elle renvoie un tableau des niveaux d'alerte correspondants pour chaque élément.
	 *
	 * @param float $averageDailyEnergy Moyenne quotidienne de l'énergie consommée.
	 * @param array $averageDailyNutrient Tableau associatif ['nutriment' => moyenne consommée].
	 * @param array $averageDailyFgp Tableau associatif ['fgpAlias' => moyenne consommée].
	 *
	 * @return array|Response Tableau des alertes par nutriment et groupe alimentaire,
	 *                        ou Response si aucune recommandation FGP n'est disponible.
	 */
	public function getBalanceSheetAlerts(float $averageDailyEnergy, array $averageDailyNutrient, array $averageDailyFgp): array|Response
	{
		/** @var App\Entity\User|null $user */
		$user = $this->security->getUser();
		// Récupère l'utilisateur connecté pour accéder à ses recommandations

		$results = [];
		// Tableau qui contiendra les alertes calculées

		$propertyAccessor = PropertyAccess::createPropertyAccessor();
		// Utilisé pour accéder dynamiquement aux propriétés de l'utilisateur

		// ENERGIE
		$energyDailyRecommended = (int)$propertyAccessor->getValue($this->security->getUser(), 'energy');
		// Récupère la valeur recommandée d'énergie journalière
		$results['energy'] = $this->isWellBalanced($averageDailyEnergy, $energyDailyRecommended);
		// Calcule si la moyenne journalière est équilibrée par rapport à la recommandation

		// NUTRIMENTS
		foreach ($averageDailyNutrient as $nutrient => $averageQuantity) {
			$nutrientDailyRecommended = (int)$propertyAccessor->getValue($this->security->getUser(), $nutrient);
			// Récupère la recommandation journalière pour ce nutriment
			$results[$nutrient] = $this->isWellBalanced($averageQuantity, $nutrientDailyRecommended);
			// Calcule le niveau d'alerte pour ce nutriment
		}

		// FOOD GROUP PARENT (FGP)
		$fgpQuantitiesRecommended = $user->getRecommendedQuantities();
		// Récupère les quantités recommandées pour chaque groupe d'aliment parent

		if (!$fgpQuantitiesRecommended) {
			// Si aucune recommandation n'existe, retourne une réponse explicative
			return new Response('Vous n\'avez aucune recommendations de quantités de groupes d\'aliments');
		}

		$balanceWell = $this->manager->getRepository(LevelAlert::class)->findOneByCode(LevelAlert::BALANCE_WELL);
		// Niveau d'alerte par défaut pour un FGP équilibré

		foreach ($averageDailyFgp as $fgpAlias => $averageQuantity) {
			$fgp = $this->foodGroupParentRepository->findOneBy(['alias' => $fgpAlias]);
			if (!$fgp || !isset($fgpQuantitiesRecommended[$fgpAlias])) {
				continue;
				// Ignore si le FGP n'existe pas ou n'a pas de recommandation
			}

			$levelAlert = $this->isWellBalanced($averageQuantity, $fgpQuantitiesRecommended[$fgpAlias]);
			// Calcule le niveau d'alerte pour le FGP

			if ($fgp->getIsPrincipal()) {
				$results[$fgpAlias] = $levelAlert;
				// Les FGP principaux gardent le niveau d'alerte calculé
			} else {
				$results[$fgpAlias] = in_array($levelAlert->getCode(), LevelAlert::HIGH_ALERTS, true)
					? $levelAlert
					: $balanceWell;
				// Les FGP secondaires utilisent BALANCE_WELL si l'alerte n'est pas élevée
			}
		}

		return $results;
		// Retourne le tableau final des alertes par nutriment et FGP
	}

	/**
	 * Récupère les alertes de balance pour le poids, l'IMC et l'énergie.
	 *
	 * Cette méthode calcule les niveaux d'alerte relatifs au poids de l'utilisateur,
	 * à son IMC (Indice de Masse Corporelle) et à l'énergie consommée,
	 * en renvoyant un tableau associatif des résultats.
	 *
	 * @return array Tableau contenant les alertes :
	 *               - 'imc' : alerte sur l'IMC
	 *               - 'weight' : alerte sur le poids
	 *               - 'energy' : alerte sur l'énergie
	 */
	public function getWeightEnergyAndImcBalanceAlerts(): array
	{
		/** @var App\Entity\User|null $user */
		$user = $this->security->getUser();
		// Récupère l'utilisateur actuellement connecté

		return [
			'imc' => $this->getWeightAlert(),
			// Calcul de l'alerte pour l'IMC (Indice de Masse Corporelle)

			'weight' => $this->getImcAlert($user->getImc()),
			// Calcul de l'alerte pour le poids en fonction de l'IMC de l'utilisateur

			'energy' => $this->getEnergyAlert(),
			// Calcul de l'alerte pour l'énergie consommée
		];
	}

	/**
	 * Calcule l'alerte liée au poids de l'utilisateur.
	 *
	 * Cette méthode compare le poids actuel de l'utilisateur avec son poids idéal
	 * et renvoie un objet LevelAlert représentant le niveau d'alerte correspondant.
	 *
	 * @return LevelAlert Niveau d'alerte lié au poids de l'utilisateur
	 */
	public function getWeightAlert(): LevelAlert
	{
		/** @var App\Entity\User|null $user */
		$user = $this->security->getUser();
		// Récupère l'utilisateur actuellement connecté

		$weight = $user->getWeight();
		// Poids actuel de l'utilisateur

		$idealWeight = $user->getIdealWeight();
		// Poids idéal de l'utilisateur

		return $this->isWellBalanced($weight, $idealWeight);
		// Compare le poids actuel au poids idéal et renvoie l'alerte correspondante
	}

	/**
	 * Calcule l'alerte énergétique de l'utilisateur.
	 *
	 * Cette méthode détermine si l'apport énergétique de l'utilisateur est équilibré
	 * par rapport à sa valeur cible ou calculée.  
	 * - Si l'utilisateur a activé le calcul automatique de l'énergie, l'alerte renvoyée
	 *   est toujours BALANCE_WELL (équilibré).  
	 * - Sinon, elle compare l'énergie réelle avec l'énergie calculée et renvoie
	 *   un objet LevelAlert correspondant au niveau d'alerte.
	 *
	 * @return LevelAlert|null Niveau d'alerte énergétique ou null si non applicable
	 */
	public function getEnergyAlert(): null|LevelAlert
	{
		/** @var App\Entity\User|null $user */
		$user = $this->security->getUser();
		// Récupère l'utilisateur actuellement connecté

		if ($user->isAutomaticCalculateEnergy()) {
			// Si le calcul automatique d'énergie est activé, on considère l'énergie équilibrée
			return $this->manager
				->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_WELL);
		}

		// Sinon, compare l'énergie réelle avec l'énergie calculée et renvoie l'alerte correspondante
		return $this->isWellBalanced($user->getEnergy(), $user->getEnergyCalculate());
	}

	/**
	 * Détermine si une quantité est bien équilibrée par rapport à la valeur recommandée.
	 *
	 * Cette méthode compare la quantité réelle ($quantity) avec la quantité recommandée ($quantityRecommended)
	 * et retourne un objet LevelAlert correspondant au niveau d'équilibre ou de déséquilibre :
	 * - Si la quantité recommandée est nulle ou négative, on considère que c'est équilibré.
	 * - Si la quantité réelle est nulle ou négative, on renvoie une alerte de déficit critique.
	 * - Les excès et déficits sont évalués selon des multiplicateurs définis dans LevelAlert.
	 *
	 * @param float|int $quantity La quantité réelle mesurée (ex. énergie, nutriment, poids)
	 * @param float|int $quantityRecommended La quantité cible ou recommandée
	 * 
	 * @return LevelAlert L'objet LevelAlert correspondant au niveau d'alerte (équilibré, manque ou excès)
	 */
	public function isWellBalanced(float|int $quantity, float|int $quantityRecommended): LevelAlert
	{
		if ($quantityRecommended <= 0) {
			// Si la quantité recommandée est <= 0, on considère que l'équilibre est atteint
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_WELL);
		}

		if ($quantity <= 0) {
			// Si la quantité réelle est <= 0, c'est un déficit critique
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_CRITICAL_LACK);
		}

		$ratio = $quantity / $quantityRecommended;
		// Calcul du ratio réel / recommandé pour évaluer excès ou déficit

		// Excès
		if ($ratio > LevelAlert::STRONGLY_MULTIPLIER) {
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_CRITICAL_EXCESS);
		}

		if ($ratio > LevelAlert::HIGHLY_MULTIPLIER) {
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_VERY_EXCESS);
		}

		if ($ratio > 1 + LevelAlert::RECOMMENDED_WELL_RANGE) {
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_EXCESS);
		}

		// Déficit
		if ($ratio < 1 / LevelAlert::STRONGLY_MULTIPLIER) {
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_CRITICAL_LACK);
		}

		if ($ratio < 1 / LevelAlert::HIGHLY_MULTIPLIER) {
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_VERY_LACK);
		}

		if ($ratio < 1 - LevelAlert::RECOMMENDED_WELL_RANGE) {
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_LACK);
		}

		// Bien équilibré
		return $this->manager->getRepository(LevelAlert::class)
			->findOneByCode(LevelAlert::BALANCE_WELL);
	}

	/**
	 * Détermine le niveau d'alerte pour un IMC donné.
	 *
	 * Cette méthode évalue l'indice de masse corporelle (IMC) et renvoie un objet LevelAlert
	 * correspondant au niveau de déséquilibre pondéral :
	 * - IMC < 16 : déficit critique (trop maigre)
	 * - 16 ≤ IMC < 17 : déficit très important
	 * - 17 ≤ IMC < 18.5 : déficit léger
	 * - 18.5 ≤ IMC ≤ 25 : bien équilibré
	 * - 25 < IMC ≤ 30 : excès léger
	 * - 30 < IMC ≤ 35 : excès très important
	 * - IMC > 35 : excès critique
	 *
	 * @param float $imc L'indice de masse corporelle de l'utilisateur
	 * 
	 * @return LevelAlert L'objet LevelAlert correspondant au niveau de déséquilibre ou équilibre
	 */
	public function getImcAlert(float $imc): LevelAlert
	{
		if ($imc < 16) {
			// IMC très bas → déficit critique
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_CRITICAL_LACK);
		}

		if ($imc < 17) {
			// IMC bas → déficit très important
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_VERY_LACK);
		}

		if ($imc < 18.5) {
			// IMC légèrement bas → déficit léger
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_LACK);
		}

		if ($imc <= 25) {
			// IMC normal → bien équilibré
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_WELL);
		}

		if ($imc <= 30) {
			// IMC légèrement élevé → excès léger
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_EXCESS);
		}

		if ($imc <= 35) {
			// IMC élevé → excès très important
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_VERY_EXCESS);
		}

		// IMC très élevé → excès critique
		return $this->manager->getRepository(LevelAlert::class)
			->findOneByCode(LevelAlert::BALANCE_CRITICAL_EXCESS);
	}


	/**
	 * Détermine le pourcentage d'ajustement calorique selon le niveau d'alerte IMC.
	 *
	 * Cette méthode retourne un pourcentage à appliquer pour ajuster l'apport calorique
	 * en fonction du niveau d'IMC :
	 * - Excès critique : -15%
	 * - Excès très important : -10%
	 * - Excès léger : -5%
	 * - Bien équilibré : 0%
	 * - Déficit léger : +5%
	 * - Déficit très important : +10%
	 * - Déficit critique : +15%
	 *
	 * @param LevelAlert $imcAlert Objet LevelAlert correspondant à l'IMC
	 * 
	 * @return int Pourcentage d'ajustement calorique (positif ou négatif)
	 */
	public function getCalorieAdjustmentPercent(LevelAlert $imcAlert): int
	{
		return match ($imcAlert->getCode()) {
			LevelAlert::BALANCE_CRITICAL_EXCESS => -15,
			LevelAlert::BALANCE_VERY_EXCESS     => -10,
			LevelAlert::BALANCE_EXCESS          => -5,
			LevelAlert::BALANCE_WELL            => 0,
			LevelAlert::BALANCE_LACK            => 5,
			LevelAlert::BALANCE_VERY_LACK       => 10,
			LevelAlert::BALANCE_CRITICAL_LACK   => 15,
			default => 0,
		};
	}

	/**
	 * Vérifie si un repas est parfaitement équilibré.
	 *
	 * Cette méthode parcourt toutes les catégories d'alertes pour un repas et
	 * retourne false dès qu'un élément n'est pas "bien équilibré" (LevelAlert::BALANCE_WELL).
	 *
	 * @param array $alerts Tableau des alertes par catégorie et sous-catégorie
	 * 
	 * @return bool true si toutes les alertes sont équilibrées, false sinon
	 */
	public function isMealFullyBalanced(array $alerts): bool
	{
		foreach ($alerts as $category) {
			foreach ($category as $data) {
				if (($data['level'] ?? null) !== LevelAlert::BALANCE_WELL) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Calcule les alertes globales pour un repas ou une journée complète.
	 *
	 * Cette méthode génère un tableau d'alertes pour :
	 * 1. L'énergie totale consommée vs recommandée par l'utilisateur.
	 * 2. Chaque nutriment consommé (protéines, lipides, glucides, sodium, etc.) vs les valeurs recommandées.
	 * 3. Chaque groupe alimentaire parent (Food Group Parent) vs les quantités recommandées.
	 *
	 * Pour chaque catégorie (énergie, nutriments, groupes alimentaires) :
	 * - On récupère la consommation réelle depuis la session.
	 * - On récupère les valeurs recommandées depuis l'utilisateur.
	 * - On calcule le niveau d'alerte via `isWellBalanced()`.
	 *
	 * Pour les groupes alimentaires non principaux, une consommation inférieure à la recommandation
	 * n'est pas considérée comme un problème et le niveau BALANCE_WELL est appliqué.
	 *
	 * @return array Tableau contenant les alertes globales structurées ainsi :
	 *   [
	 *     'energy' => [
	 *         'consumed' => float,
	 *         'recommended' => float,
	 *         'level' => LevelAlert,
	 *     ],
	 *     'nutrients' => [
	 *         'protein' => [
	 *             'nutrientName' => string,
	 *             'consumed' => float,
	 *             'recommended' => float,
	 *             'level' => LevelAlert,
	 *         ],
	 *         ...
	 *     ],
	 *     'food_groups' => [
	 *         'fruit' => [
	 *             'fgpName' => string,
	 *             'isPrincipal' => bool,
	 *             'consumed' => float,
	 *             'recommended' => float,
	 *             'level' => LevelAlert,
	 *         ],
	 *         ...
	 *     ]
	 *   ]
	 */
	public function computeMealGlobalAlerts(): array
	{
		/** Récupère l'utilisateur actuellement connecté */
		/** @var App\Entity\User|null $user */
		$user = $this->security->getUser();

		/** Récupère la session courante pour accéder aux données de la journée */
		$session = $this->requestStack->getSession();

		/** Tableau qui contiendra toutes les alertes globales */
		$alerts = [];

		// =========================
		// ALERTES SUR L'ENERGIE
		// =========================

		// On récupère la dernière valeur d'énergie consommée depuis la session (dernier plat de la journée)
		$consumed = $this->getLastValueFromSessionArray(
			$session->get('_meal_day_evolution/energy', [])
		);

		// Valeur d'énergie recommandée pour l'utilisateur
		$recommended = $user->getEnergy();

		// Calcul du niveau d'alerte : bien équilibré ou excès/déficit
		$level = $recommended > 0
			? $this->isWellBalanced($consumed, $recommended)
			: $this->manager->getRepository(LevelAlert::class)->findOneByCode(LevelAlert::BALANCE_WELL);

		// Stockage des informations d'énergie dans le tableau d'alertes
		$alerts['energy'] = [
			'consumed'     => $consumed,
			'recommended'  => $recommended,
			'level'        => $level,
		];

		// =========================
		// ALERTES SUR LES NUTRIMENTS
		// =========================

		// On récupère tous les nutriments disponibles en base
		$nutrients = $this->nutrientRepository->findAll();

		foreach ($nutrients as $nutrient) {
			// Consommation réelle du nutriment depuis la session (dernier plat)
			$consumed = $this->getLastValueFromSessionArray(
				$session->get('_meal_day_evolution/' . $nutrient->getCode(), [])
			);

			// Détermine la méthode pour récupérer la valeur recommandée de l'utilisateur
			$getter = 'get' . ucfirst($nutrient->getCode());
			$recommended = method_exists($user, $getter)
				? (float) $user->$getter()
				: 0;

			// Calcul du niveau d'alerte pour ce nutriment
			$level = $recommended > 0
				? $this->isWellBalanced($consumed, $recommended)
				: $this->manager->getRepository(LevelAlert::class)->findOneByCode(LevelAlert::BALANCE_WELL);

			// Stockage des alertes dans le tableau
			$alerts['nutrients'][$nutrient->getCode()] = [
				'nutrientName' => $nutrient->getName(),
				'consumed'     => $consumed,
				'recommended'  => $recommended,
				'level'        => $level,
			];
		}

		// =========================
		// ALERTES SUR LES GROUPES D'ALIMENTS PARENTS (Food Group Parent)
		// =========================

		// Quantités réellement consommées pour chaque FGP dans les plats de la session
		$fgpConsumed = $this->quantityTreatment->getQuantitiesConsumedInSessionDishes();

		// Quantités recommandées par l'utilisateur pour chaque FGP
		$fgpRecommended = $user->getRecommendedQuantities();

		// Récupère des informations supplémentaires sur chaque FGP (ex. si c'est un groupe principal)
		$aliasMetaMap = $this->foodGroupParentRepository->getAliasMetadataMap();

		foreach ($fgpRecommended as $fgpAlias => $recommendedQuantity) {
			// Consommation réelle du groupe alimentaire
			$consumedQuantity = $fgpConsumed[$fgpAlias] ?? 0;

			// Détermine si le groupe alimentaire est principal ou secondaire
			$isPrincipal = $aliasMetaMap[$fgpAlias]['isPrincipal'];

			// Si le groupe n'est pas principal et consommé moins que recommandé → niveau bien équilibré
			if (!$isPrincipal && $consumedQuantity < $recommendedQuantity) {
				$level = $this->manager->getRepository(LevelAlert::class)->findOneByCode(LevelAlert::BALANCE_WELL);
			} else {
				// Sinon, calcul normal du niveau d'alerte
				$level = $recommendedQuantity > 0
					? $this->isWellBalanced($consumedQuantity, $recommendedQuantity)
					: $this->manager->getRepository(LevelAlert::class)->findOneByCode(LevelAlert::BALANCE_WELL);
			}

			// Stockage des alertes pour le FGP
			$alerts['food_groups'][$fgpAlias] = [
				'fgpName'      => $aliasMetaMap[$fgpAlias]['name'],
				'isPrincipal'  => $aliasMetaMap[$fgpAlias]['isPrincipal'],
				'consumed'     => $consumedQuantity,
				'recommended'  => $recommendedQuantity,
				'level'        => $level,
			];
		}

		// Retourne l'ensemble des alertes calculées pour la journée/repas
		return $alerts;
	}

	/**
	 * Retourne le niveau d'alerte le plus critique pour la journée entière
	 *
	 * @param array $mealsOfDay Tableau des repas de la journée (groupés par type)
	 * @param int $energyTotal Énergie totale consommée dans la journée
	 * @param int $recommendedEnergy Énergie recommandée pour l'utilisateur
	 * 
	 * @return LevelAlert|null Niveau d'alerte le plus élevé ou null si aucun
	 */
	public function getHighestAlertLevelForDay(
		array $mealsOfDay,
		int $energyTotal,
		int $recommendedEnergy
	): ?LevelAlert {

		// On commence par comparer l'énergie totale consommée avec l'énergie recommandée
		// pour obtenir un niveau d'alerte initial
		$highestLevel = $this->isWellBalanced($energyTotal, $recommendedEnergy);

		// Parcours de tous les repas regroupés par type (ex: petit-déjeuner, déjeuner…)
		foreach ($mealsOfDay as $mealsPerType) {

			if (!empty($mealsPerType)) {

				// Parcours de chaque repas individuel
				foreach ($mealsPerType as $meal) {

					// Vérification des alertes liées aux plats du repas
					foreach ($meal->getAlertOnDishes() as $alert) {
						// On compare le niveau d'alerte de chaque plat avec le niveau courant
						// et on garde le plus critique
						$highestLevel = $this->getMoreCritical(
							$alert['higher_level'],
							$highestLevel
						);
					}

					// Vérification des alertes liées aux aliments du repas
					foreach ($meal->getAlertOnFoods() as $alert) {
						// Même logique : on garde le niveau d'alerte le plus sévère
						$highestLevel = $this->getMoreCritical(
							$alert['higher_level'],
							$highestLevel
						);
					}
				}
			}
		}

		// Retourne le niveau d'alerte le plus critique trouvé pour la journée
		return $highestLevel;
	}

	/**
	 * Récupère le niveau d'alerte le plus critique pour la journée (tous repas confondus)
	 *
	 * @return LevelAlert|null Retourne l'alerte la plus sévère ou null si aucune alerte
	 */
	public function getHighestAlertLevelMealDay(): ?LevelAlert
	{
		// Récupère la session
		$session = $this->requestStack->getSession();

		// Récupère la liste finale des alertes stockées en session
		$finalAlertsSession = $session->get('_meal_day_alerts/_final_list', []);

		if (empty($finalAlertsSession)) {
			return null; // Pas d'alertes → on retourne null
		}

		// Extrait tous les codes d'alerte de la session
		$codes = $this->extractCodes($finalAlertsSession);

		if (empty($codes)) {
			return null; // Aucun code → pas d'alerte
		}

		// 🔎 On récupère toutes les entités LevelAlert correspondant aux codes
		$levelAlerts = $this->levelAlertRepository->findBy([
			'code' => $codes
		]);

		if (empty($levelAlerts)) {
			return null; // Aucun LevelAlert trouvé
		}

		// 🏆 On compare tous les LevelAlert pour trouver le plus critique
		$mostCritical = array_shift($levelAlerts); // On prend le premier comme référence

		foreach ($levelAlerts as $alert) {
			$mostCritical = $this->getMoreCritical($mostCritical, $alert);
		}

		return $mostCritical; // Retourne l'alerte la plus sévère
	}

	/**
	 * Crée une alerte nutritionnelle pour un nutriment ou un groupe alimentaire.
	 *
	 * Cette fonction :
	 * - Calcule le niveau d'alerte en fonction de la quantité consommée + ajoutée par rapport à la recommandation.
	 * - Retourne une instance de LevelAlert si le niveau n’est pas « recommandé », sinon retourne null.
	 *
	 * @param string        $subject                       Sujet de l’alerte ('energy', 'protein', 'lipid', 'carbohydrate', 'sodium', 'food_group_parent').
	 * @param float|array   $quantityOrEnergyConsumed     Quantité déjà consommée (ou tableau pour les groupes alimentaires).
	 * @param float         $quantityOrEnergyAdded        Quantité ajoutée à vérifier.
	 * @param float|array   $quantityOrEnergyRecommended Quantité recommandée pour l’utilisateur (ou tableau pour les groupes alimentaires).
	 * @param string|null   $fgpAlias                      Alias du groupe alimentaire parent si applicable.
	 *
	 * @return LevelAlert|null L’alerte correspondant au niveau de dépassement, ou null si la consommation est dans les recommandations.
	 */
	private function createAlert(string $subject, float|array $quantityOrEnergyConsumed, float $quantityOrEnergyAdded, float|array $quantityOrEnergyRecommended, string $fgpAlias = null): null|LevelAlert
	{
		$codeFinishAlertLevel = $this->getLevelAlert($quantityOrEnergyConsumed + $quantityOrEnergyAdded, $quantityOrEnergyRecommended);
		// Calcule le niveau d’alerte final après ajout de la nouvelle quantité.
		// Compare la somme (consommé + ajouté) à la quantité recommandée.

		if (LevelAlert::RECOMMENDED != $codeFinishAlertLevel) {
			$levelAlert = $this->levelAlertRepository->findOneByCode($codeFinishAlertLevel);
			// Si le niveau n’est pas « recommandé », récupère l’objet LevelAlert correspondant dans la base.

			return $levelAlert;
			// Retourne l’alerte à utiliser.
		}

		return null;
		// Si le niveau est recommandé, aucune alerte n’est générée → retourne null.
	}

	/**
	 * Détermine le niveau d’alerte nutritionnelle en fonction de la consommation par rapport à la recommandation.
	 *
	 * Cette fonction calcule le ratio entre la quantité consommée et la quantité recommandée
	 * et retourne un code d’alerte correspondant :
	 * - RECOMMENDED : consommation dans la plage recommandée
	 * - NOT_RECOMMENDED : légère surconsommation
	 * - HIGHLY_NOT_RECOMMENDED : surconsommation importante
	 * - STRONGLY_NOT_RECOMMENDED : surconsommation très importante
	 *
	 * @param float $quantityOrEnergyConsumed   Quantité déjà consommée (ou apport énergétique).
	 * @param float $quantityOrEnergyRecommended Quantité recommandée pour l’utilisateur.
	 *
	 * @return string Code du niveau d’alerte (constantes LevelAlert::*)
	 */
	private function getLevelAlert(float $quantityOrEnergyConsumed, float $quantityOrEnergyRecommended): string
	{
		if ($quantityOrEnergyRecommended <= 0) {
			return LevelAlert::RECOMMENDED;
			// Si la recommandation est nulle ou négative, aucune alerte possible → recommandé par défaut
		}

		$ratio = $quantityOrEnergyConsumed / $quantityOrEnergyRecommended;
		// Calcul du ratio consommation / recommandation

		if ($ratio > LevelAlert::STRONGLY_MULTIPLIER) {
			return LevelAlert::STRONGLY_NOT_RECOMMENDED;
			// Si la consommation dépasse fortement la recommandation → alerte maximale
		}

		if ($ratio > LevelAlert::HIGHLY_MULTIPLIER) {
			return LevelAlert::HIGHLY_NOT_RECOMMENDED;
			// Si la consommation dépasse beaucoup la recommandation → alerte élevée
		}

		if ($ratio > 1 + LevelAlert::RECOMMENDED_WELL_RANGE) {
			return LevelAlert::NOT_RECOMMENDED;
			// Dépassement léger de la recommandation → alerte modérée
		}

		return LevelAlert::RECOMMENDED;
		// Consommation dans la plage acceptable → recommandé
	}

	/**
	 * Calcule toutes les alertes nutritionnelles pour un item spécifique (Dish ou Food) 
	 * avant qu'il ne soit sélectionné pour un repas.
	 *
	 * Cette méthode :
	 *  - Détermine la quantité et l'unité de mesure de l'élément dans le repas actuel.
	 *  - Calcule les quantités consommées des groupes alimentaires parents pour cet item.
	 *  - Vérifie les seuils et génère les alertes pour chaque nutriment : énergie, protéines, lipides, glucides et sodium.
	 *  - Compile toutes les alertes en une structure unique.
	 *
	 * @param int $rankMeal L'index du repas dans la journée.
	 * @param int $rankDish L'index de l'élément (plat ou aliment) dans le repas.
	 * @param object $entity L'entité Dish ou Food à analyser.
	 * @param string $type Type de l'entité : 'Dish' ou 'Food'.
	 * @param array $fgpQuantitiesConsumed Tableau des quantités consommées par groupe alimentaire parent.
	 * @param array $nutrientsConsumed Tableau des nutriments déjà consommés pour ce repas/élément (energy, protein, lipid, carbohydrate, sodium).
	 *
	 * @return array|null Retourne les alertes compilées pour l'élément ou null si aucune alerte.
	 */
	private function calculateAlertsForEntityAboutToBeSelected($rankMeal, $rankDish, $entity, string $type, array $fgpQuantitiesConsumed, array $nutrientsConsumed): ?array
	{
		$alerts = [];
		// Initialise le tableau qui contiendra toutes les alertes pour cet item.

		$session = $this->requestStack->getSession();
		// Récupère la session utilisateur pour accéder aux repas et aux éléments déjà sélectionnés.

		$currentMeal = $session->get('_meal_day_' . $rankMeal);
		// Récupère les informations du repas courant selon son index.

		$currentItem = $currentMeal['dishAndFoods'][$rankDish] ?? null;
		// Récupère l'élément spécifique (Dish ou Food) dans le repas, s'il existe.

		$quantity = 1;
		$unitMeasureAlias = null;
		// Valeurs par défaut pour la quantité et l'unité de mesure de l'élément.

		if ($currentItem) {
			// Si l'élément existe dans le repas actuel.
			if (
				(int)$currentItem['id'] === (int)$entity->getId()
				&& $currentItem['type'] === $type
			) {
				// Vérifie que l'élément correspond à l'entité analysée.
				$quantity = (float)$currentItem['quantity'];
				// Utilise la quantité réelle de l'élément sélectionné.
				if ($type === 'Food') {
					$unitMeasureAlias = $currentItem['unitMeasureAlias'] ?? 'mg';
					// Pour les aliments, récupère l'unité de mesure ou utilise 'mg' par défaut.
				}
			}
		} else {
			// Si l'élément n'est pas encore dans le repas (exemple pour alerte pré-sélection)
			if ($type === 'Food') {
				$unitMeasureAlias = 'mg';
				// Définit l'unité par défaut pour les aliments.
			}
		}

		if ($type === 'Dish') {
			// Cas où l'élément est un plat.
			$fgpQuantitiesForNPortion = $this->dishUtil->getFoodGroupParentQuantitiesForNPortion($entity, $quantity);
			// Calcule les quantités de chaque groupe alimentaire parent pour la portion de plat sélectionnée.

			if ($listAlertFgp = $this->getAlerts('food_group_parent', $entity, $fgpQuantitiesConsumed, $quantity, $fgpQuantitiesForNPortion)) {
				$alerts['food_group_parent'] = $listAlertFgp;
				// Si des alertes pour les groupes alimentaires existent, on les ajoute au tableau d'alertes.
			}
		} else { // Food
			// Cas où l'élément est un aliment simple.
			if ($listAlertFgp = $this->getAlerts('food_group_parent', $entity, $fgpQuantitiesConsumed, $quantity, null, $unitMeasureAlias)) {
				$alerts['food_group_parent'] = $listAlertFgp;
				// Ajoute l'alerte sur le groupe alimentaire parent pour l'aliment.
			}
		}

		// Vérification et génération des alertes pour chaque nutriment.
		foreach (self::NUTRITIONAL_ELEMENTS as $nutrient) {
			$value = $this->extractDataFromDishOrFoodSelected($nutrient, $entity, $quantity, $unitMeasureAlias);
			// Récupère la quantité réelle du nutriment pour l'élément selon la portion.

			if ($listAlert = $this->getAlerts($nutrient, $entity, $nutrientsConsumed[$nutrient], $value)) {
				$alerts[$nutrient] = $listAlert;
				// Si une alerte est générée pour ce nutriment, elle est ajoutée au tableau d'alertes.
			}
		}

		if (empty($alerts)) {
			return null;
			// Si aucune alerte n'est générée, retourne null.
		}

		return $this->compileAlertsInformation($alerts);
		// Compile toutes les alertes pour cet élément en une structure unique et lisible.
	}

	/**
	 * Compile plusieurs alertes en une seule structure consolidée.
	 *
	 * Cette fonction prend un tableau d’alertes individuelles (chaque alerte contient
	 * des messages et des niveaux) et :
	 * 1. Agrège tous les messages dans un seul tableau.
	 * 2. Détermine le niveau d’alerte le plus élevé (priorité minimale) parmi toutes les alertes.
	 *
	 * @param array $alerts Tableau d’alertes individuelles, chacune contenant :
	 *                      - 'messages' : array de chaînes à afficher
	 *                      - 'levels'   : array de priorités d’alerte (int)
	 *
	 * @return array Tableau contenant :
	 *               - 'higher_level' : entité LevelAlert correspondant au niveau le plus élevé
	 *               - 'messages'     : tableau fusionné de tous les messages
	 */
	private function compileAlertsInformation(array $alerts)
	{
		$messages = [];
		// Initialise le tableau qui contiendra tous les messages d’alerte

		foreach ($alerts as $alert) {
			$mins[] = min($alert["levels"]);
			// Récupère la priorité la plus élevée (valeur minimale) pour cette alerte
			$messages = array_merge($messages, $alert["messages"]);
			// Ajoute tous les messages de cette alerte au tableau global
		}

		return [
			"higher_level" => $this->manager->getRepository(LevelAlert::class)->findOneByPriority((int)min($mins)),
			// Récupère l’entité LevelAlert correspondant à la priorité la plus élevée parmi toutes les alertes
			"messages" => $messages
			// Retourne tous les messages d’alerte fusionnés
		];
	}

	/**
	 * Récupère la dernière valeur d'une structure de session multidimensionnelle.
	 *
	 * Cette méthode prend un tableau potentiellement issu de la session représentant
	 * l'évolution des nutriments ou de l'énergie sur les repas et plats de la journée,
	 * et retourne la dernière valeur (dernier plat du dernier repas) ou 0 si vide.
	 *
	 * @param array|null $data Tableau multidimensionnel avec les valeurs des repas/plats
	 * 
	 * @return float Dernière valeur trouvée ou 0 si aucun élément
	 */
	private function getLastValueFromSessionArray(?array $data): float
	{
		if (empty($data)) {
			return 0;
		}

		// Dernier rankMeal
		$lastMeal = end($data);

		if (!is_array($lastMeal) || empty($lastMeal)) {
			return 0;
		}

		// Dernier rankDish
		$lastDishValue = end($lastMeal);

		return (float) $lastDishValue;
	}

	/**
	 * Extrait les codes d'alerte depuis la session pour toutes catégories
	 *
	 * @param array $alertsSession Tableau d'alertes en session
	 * 
	 * @return array Tableau de codes uniques
	 */
	private function extractCodes(array $alertsSession): array
	{
		$codes = [];

		foreach ($alertsSession as $key => $alert) {

			if ($key === 'food_group_parent' && is_array($alert)) {
				// Pour les alertes "food_group_parent", on récupère les codes de chaque sous-alerte
				foreach ($alert as $subAlert) {
					if (isset($subAlert['code'])) {
						$codes[] = $subAlert['code'];
					}
				}
			} elseif (isset($alert['code'])) {
				// Pour les autres alertes, on prend directement le code
				$codes[] = $alert['code'];
			}
		}

		return array_unique($codes); // On retire les doublons
	}

	/**
	 * Compare deux alertes et retourne la plus critique (priorité la plus faible = plus critique)
	 *
	 * @param LevelAlert $a
	 * @param LevelAlert $b
	 * 
	 * @return LevelAlert
	 */
	public function getMoreCritical(LevelAlert $a, LevelAlert $b): LevelAlert
	{
		return $a->getPriority() < $b->getPriority()
			? $a
			: $b;
	}
}
