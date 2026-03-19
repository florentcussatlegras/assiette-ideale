<?php

namespace App\Controller\meal;

use App\Repository\FoodGroupParentRepository;
use App\Repository\TypeMealRepository;
use App\Repository\MealRepository;
use App\Entity\Meal;
use App\Entity\TypeMeal;
use App\Repository\LevelAlertRepository;
use App\Service\MealUtil;
use App\Service\AlertFeature;
use App\Service\BalanceSheetFeature;
use App\Service\WeekAlertFeature;
use App\Service\QuantityTreatment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * MenuController.php
 *
 * Contrôleur principal pour la gestion des menus de l'utilisateur.
 *
 * Ce contrôleur gère l'ensemble des fonctionnalités liées aux menus quotidiens et hebdomadaires,
 * y compris :
 *  - la création et modification des repas,
 *  - l'ajout et suppression des plats et aliments,
 *  - le calcul des alertes nutritionnelles et énergétiques,
 *  - la gestion des pré-sélections dans la sidebar.
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 * 
 * @package App\Controller\meal
 */
#[Route('/mes-menus')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class MenuController extends AbstractController
{
	/**
	 * Ajoute ou met à jour les repas du jour pour l'utilisateur connecté.
	 *
	 * Cette méthode :
	 *  - Supprime d'abord tous les repas existants pour la date sélectionnée dans la session.
	 *  - Parcourt chaque repas du jour en session (_meal_day_{n}) et crée un nouvel objet Meal
	 *    si des plats ou aliments sont présents.
	 *  - Associe les alertes, l'énergie totale et les listes de groupes d'aliments (FGP) à chaque repas.
	 *  - Persiste tous les repas dans la base de données via Doctrine.
	 *  - Ajoute un message flash de confirmation et redirige vers la page du menu de la semaine.
	 *
	 * @param Request $request
	 * @param EntityManagerInterface $manager
	 * @param WeekAlertFeature $weekAlertFeature
	 * @param AlertFeature $alertFeature
	 * 
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	#[Route('/add', name: 'menu_add', options: ['expose' => true], methods: ['GET', 'POST'])]
	public function add(
		Request $request,
		EntityManagerInterface $manager,
		WeekAlertFeature $weekAlertFeature
	): Response {
		$session = $request->getSession();

		// Supprime les repas existants pour la date en cours
		$meals = $manager->getRepository(Meal::class)->findBy([
			'eatedAt' => $session->get('_meal_day_date'),
			'user' => $this->getUser()
		]);

		if (null !== $meals) {
			foreach ($meals as $meal) {
				$manager->remove($meal);
			}
		}

		// Parcourt chaque repas en session pour le persister
		for ($n = 0; $n <= $session->get('_meal_day_range'); $n++) {
			$mealSessionKey = '_meal_day_' . $n;

			// Vérifie qu'il y a des plats ou aliments pour ce repas
			if (
				array_key_exists('dishAndFoods', $session->get($mealSessionKey)) &&
				!empty($session->get($mealSessionKey)['dishAndFoods'])
			) {

				// Récupération des alertes spécifiques aux plats et aliments
				$alertOnDishes = array_key_exists($n, $session->get('_meal_day_alerts/_dishes_selected'))
					? $session->get('_meal_day_alerts/_dishes_selected')[$n] : [];
				$alertOnFoods = array_key_exists($n, $session->get('_meal_day_alerts/_foods_selected'))
					? $session->get('_meal_day_alerts/_foods_selected')[$n] : [];

				// Récupération du type de repas (petit-déj, déjeuner, dîner, etc.)
				$typeMeal = $manager->getRepository(TypeMeal::class)
					->findOneByBackName($session->get($mealSessionKey)['type']);

				// Détermination de la date du repas
				$mealDayDate = $session->has('_meal_day_date')
					? $session->get('_meal_day_date')
					: (new \DateTime())->format(\DateTime::W3C);

				// Création de l'objet Meal avec toutes les informations nécessaires
				$meal = new Meal(
					'nom', // nom temporaire, pourrait être remplacé par un nom personnalisé
					$n,
					$mealDayDate,
					$session->get($mealSessionKey)['dishAndFoods'],
					$typeMeal,
					$this->getUser(),
					$alertOnDishes,
					$alertOnFoods
				);

				// Associe les alertes finales du jour si elles existent
				if ($session->has('_meal_day_alerts/_final_list')) {
					$meal->setAlertsAllMealsDay($session->get('_meal_day_alerts/_final_list'));
				}

				// Associe l'énergie totale du jour et les listes de FGP
				$meal->setEnergyAllMealsDay($session->get('_meal_day_energy'));
				$meal->setListFgpAllMealsDay($session->get('_meal_day/_list_fgp'));
				$meal->setListFgpRemainingAbsentAllMealsDay($session->get('_meal_day/_list_fgp_remaining_absent'));

				// Prépare l'entité pour persistance
				$manager->persist($meal);
			}
		}

		// 3Écriture en base
		$manager->flush();

		// 4Message flash de confirmation
		$this->addFlash('notice', 'Les repas ont bien été enregistrés');

		// Redirection vers la page du menu de la semaine correspondante
		return $this->redirectToRoute('menu_week_menu', [
			'startingDate' => $weekAlertFeature->getStartingDayOfWeek($mealDayDate),
		]);
	}

	/**
	 * Affiche le menu hebdomadaire de l'utilisateur.
	 *
	 * Cette méthode récupère tous les repas de la semaine à partir d'une date de début,
	 * calcule l'énergie totale par jour, ainsi que le plus haut niveau d'alerte nutritionnelle
	 * pour chaque jour. Elle prend également en charge l'affichage via AJAX pour différents formats
	 * (mobile ou desktop).
	 *
	 * @param Request $request L'objet Request contenant les paramètres de la requête
	 * @param QuantityTreatment $quantityTreatment Service pour récupérer les repas et quantités
	 * @param AlertFeature $alertFeature Service pour calculer les alertes nutritionnelles
	 * @param WeekAlertFeature $weekAlertFeature Service pour obtenir les jours d'une semaine et leurs alertes
	 * @param TypeMealRepository $typeMealRepository Repository pour récupérer les types de repas
	 *
	 * @return Response
	 */
	#[Route('/week', name: 'menu_week_menu', methods: ['GET'])]
	public function week(
		Request $request,
		QuantityTreatment $quantityTreatment,
		AlertFeature $alertFeature,
		WeekAlertFeature $weekAlertFeature,
		TypeMealRepository $typeMealRepository
	): Response {
		/** @var App\Entity\User $user */
		$user = $this->getUser();

		// Récupère la date de début de semaine depuis la requête (optionnelle)
		$startingDate = $request->query->get('startingDate');

		// Récupère tous les repas de la semaine, organisés par jour et par type de repas
		$mealsPerDay = $quantityTreatment->getMealsPerDay($startingDate);

		// Initialisation des tableaux pour stocker l'énergie totale par jour
		$energyTotalPerDays = [];
		$averageDailyNutrientPerDays = [];

		// Parcours tous les repas pour chaque jour afin de calculer l'énergie totale quotidienne
		foreach ($mealsPerDay as $day => $mealsOfTheDayPerType) {
			foreach ($mealsOfTheDayPerType as $typeMeal => $meals) {
				if (!empty($meals)) {
					// On suppose que tous les repas du jour ont la même énergie totale globale (stockée sur le premier repas)
					$energyTotalPerDays[$day] = $meals[0]->getEnergyAllMealsDay();
				}
			}
		}

		// Initialisation du tableau pour stocker l'alerte la plus élevée par jour
		$highestAlertPerDay = [];

		// Détermine le niveau d'alerte nutritionnelle pour chaque jour
		foreach ($mealsPerDay as $day => $mealsOfTheDayPerType) {
			$energyTotal = $energyTotalPerDays[$day] ?? 0;
			if ($energyTotal > 0) {
				$highestAlertPerDay[$day] = $alertFeature
					->getHighestAlertLevelForDay(
						$mealsOfTheDayPerType,   // Tous les repas de la journée
						$energyTotal,            // Energie totale de la journée
						$user->getEnergy()       // Objectif énergétique de l'utilisateur
					);
			}
		}

		// Gestion des requêtes AJAX
		if ($request->query->get('ajax')) {

			// Cas spécifique pour affichage mobile
			if ("mobile" === $request->query->get('format')) {

				return $this->render(
					"meals/week/mobile/_wrapper_menu_week.html.twig",
					[
						// Jours de la semaine affichés
						"days" => $weekAlertFeature->getDaysForWeekMenu($startingDate),
						// Tous les repas par jour et type
						"meals" => $quantityTreatment->getMealsPerDay($startingDate),
						// Quantités consommées par jour
						"quantitiesConsumed" => $quantityTreatment->getQuantitiesConsumedOnWeek($startingDate),
						// Liste des types de repas disponibles
						"typeMeals" => $typeMealRepository->findAll(),
						// Energie totale par jour
						"energyTotalPerDays" => $energyTotalPerDays,
						// Niveau d'alerte le plus élevé par jour
						"highestAlertPerDay" => $highestAlertPerDay,
					]
				);
			}

			// Affichage classique pour AJAX (desktop ou autre)
			return $this->render(
				"meals/week/_wrapper_menu_week.html.twig",
				[
					"days" => $weekAlertFeature->getDaysForWeekMenu($startingDate),
					"meals" => $quantityTreatment->getMealsPerDay($startingDate),
					"quantitiesConsumed" => $quantityTreatment->getQuantitiesConsumedOnWeek($startingDate),
					"typeMeals" => $typeMealRepository->findAll(),
					"energyTotalPerDays" => $energyTotalPerDays,
					"highestAlertPerDay" => $highestAlertPerDay,
				]
			);
		}

		// Affichage complet de la page semaine (non AJAX)
		return $this->render(
			"meals/week/index.html.twig",
			[
				"days" => $weekAlertFeature->getDaysForWeekMenu($startingDate),
				"meals" => $quantityTreatment->getMealsPerDay($startingDate),
				"quantitiesConsumed" => $quantityTreatment->getQuantitiesConsumedOnWeek($startingDate),
				"typeMeals" => $typeMealRepository->findAll(),
				"energyTotalPerDays" => $energyTotalPerDays,
				"highestAlertPerDay" => $highestAlertPerDay,
			]
		);
	}

	#[Route('/get/{date}', name: 'menu_week_get_meals', methods: ['GET'], requirements: ['date' => '\d{4}-\d{2}-\d{2}'])]
	public function getMeals(Request $request, EntityManagerInterface $manager, MealUtil $mealUtil, BalanceSheetFeature $balanceSheetFeature, AlertFeature $alertFeature, $date = null)
	{
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

		$session = $request->getSession();

		if (null === $date) {
			if ($request->query->get('date')) {
				$date = $request->query->get('date');
			} else {
				$dateDay = new \DateTime;
				$date = $dateDay->format('Y-m-d');

				if ($session->has('_meal_day_date') && $session->get('_meal_day_date') === $date && $session->get('_meal_day_energy') > 0) {
					return $this->redirectToRoute('meal_day');
				}
			}
		}

		$mealUtil->removeMealsSession();

		$meals = $manager->getRepository(Meal::class)->findBy([
			'eatedAt' => $date,
			'user' => $this->getUser()
		]);

		if ($request->query->get('ajax')) {
			return $this->render('meals/week/_meals_details.html.twig', [
				'meals' => $meals,
				'date' => $date,
				'highestAlertPerDay' => $request->query->get('highestAlertPerDay'),
				'averageDailyNutrient' => $balanceSheetFeature->averageDailyNutrientForAPeriod($date, $date),
			]);
		}

		if (!empty($meals)) {
			foreach ($meals as $i => $meal) {
				$session->set('_meal_day_' . $i, ['type' => $meal->getType()->getBackName(), 'dishAndFoods' => $meal->getDishAndFoods()]);
			}

			$session->set('_meal_day_range', $i);
		}

		$session->set('_meal_day_date', $date);

		return $this->redirectToRoute('meal_day');
	}

	/**
	 * Récupère les repas d'un jour donné pour l'utilisateur connecté.
	 *
	 * Cette méthode initialise la session des repas pour le jour demandé,
	 * supprime les repas précédemment stockés en session, et peut renvoyer
	 * un rendu AJAX détaillé ou rediriger vers la vue principale du jour.
	 *
	 * @param Request $request
	 * @param EntityManagerInterface $manager
	 * @param MealUtil $mealUtil
	 * @param BalanceSheetFeature $balanceSheetFeature
	 * @param AlertFeature $alertFeature
	 * @param string|null $date Date au format 'Y-m-d' (optionnelle)
	 * 
	 * @return Response
	 */
	#[Route('/get/{id}/{date}', name: 'menu_get_by_date_and_type', methods: ['GET'], requirements: ['id' => '\d+', 'date' => '\d{4}-\d{2}-\d{2}'])]
	public function getMeal(
		Request $request,
		TypeMealRepository $typeMealRepository,
		MealRepository $mealRepository,
		MealUtil $mealUtil,
		TypeMeal $typeMealToAdd,
		string $date
	): Response {
		// Récupère la session de l'utilisateur
		$session = $request->getSession();

		// Supprime les repas précédemment stockés en session pour éviter les doublons
		$mealUtil->removeMealsSession();

		// Récupère tous les types de repas (petit-déjeuner, déjeuner, dîner...) triés par classement
		$typeMeals = $typeMealRepository->findAll([], ['order' => 'ranking']);

		// Initialise le compteur pour indexer les repas dans la session
		$range = 0;

		// Récupère tous les repas de la date donnée pour l'utilisateur connecté, triés par ordre d'affichage
		if (null !== $meals = $mealRepository->findBy([
			'eatedAt' => $date,
			'user' => $this->getUser()
		], ['rankView' => 'ASC'])) {

			// Parcourt tous les types de repas possibles
			foreach ($typeMeals as $typeMeal) {

				// Si le type de repas à ajouter correspond à ce type, on l'initialise vide dans la session
				if ($typeMeal->getId() === $typeMealToAdd->getId()) {
					$session->set('_meal_day_' . $range, [
						'type' => $typeMeal->getBackName(),
						'dishAndFoods' => []
					]);
					$range++; // incrémente l'index des repas
				}

				// Parcourt tous les repas récupérés pour la date
				foreach ($meals as $meal) {
					// Si le repas correspond au type actuel, on l'ajoute dans la session
					if ($meal->getType()->getId() === $typeMeal->getId()) {
						$session->set('_meal_day_' . $range, [
							'type' => $meal->getType()->getBackName(),
							'dishAndFoods' => $meal->getDishAndFoods()
						]);
						$range++; // incrémente l'index des repas
					}
				}
			}
		}

		// Stocke le nombre total de repas (index maximal) dans la session
		$session->set('_meal_day_range', $range - 1);

		// Stocke la date du jour dans la session pour référence
		$session->set('_meal_day_date', $date);

		// Redirige vers la page principale du repas du jour
		return $this->redirectToRoute('meal_day');
	}

	/**
	 * Supprime les repas pour une date donnée et éventuellement pour un type de repas spécifique.
	 *
	 * @param TypeMeal|null $typeMeal Type de repas à supprimer (facultatif)
	 * @param Request $request Objet Request pour récupérer les paramètres et la session
	 * @param EntityManagerInterface $manager Gestionnaire d'entités pour interagir avec la base
	 * @param TokenStorageInterface $tokenStorage Gestion des tokens utilisateur (non utilisé ici mais injecté)
	 * @param MealUtil $mealUtil Service utilitaire pour gérer les repas en session
	 * @param string $date Date des repas à supprimer (format YYYY-MM-DD)
	 * 
	 * @return Response Redirection vers la page des menus ou du jour
	 */
	#[Route('/remove/{date}/{id?}', name: 'menu_week_remove_meals', methods: ['GET', 'POST'], requirements: ['date' => '\d{4}-\d{2}-\d{2}', 'id' => '\d+'])]
	public function removeMeals(
		?TypeMeal $typeMeal,
		Request $request,
		EntityManagerInterface $manager,
		MealUtil $mealUtil,
		string $date
	): Response {
		// Supprime tous les repas stockés en session pour éviter les conflits avec la base
		$mealUtil->removeMealsSession();

		// Si un type de repas spécifique est fourni, on ne supprime que ce type pour la date
		if ($typeMeal) {
			$meals = $manager->getRepository(Meal::class)->findBy([
				'eatedAt' => $date,
				'type' => $typeMeal,
				'user' => $this->getUser()
			]);
		} else {
			// Sinon, on récupère tous les repas pour cette date pour l'utilisateur connecté
			$meals = $manager->getRepository(Meal::class)->findBy([
				'eatedAt' => $date,
				'user' => $this->getUser()
			]);
		}

		// Parcourt tous les repas récupérés et les supprime de la base
		if (!empty($meals)) {
			foreach ($meals as $meal) {
				$manager->remove($meal);
			}
		}

		// Applique toutes les suppressions en base de données
		$manager->flush();

		// Ajoute un message flash pour confirmer la suppression à l'utilisateur
		$this->addFlash('notice', 'Les plats ont bien été supprimés');

		// Si la suppression provient de la vue hebdomadaire des menus, redirige vers le menu de la semaine
		if ($request->query->has('from_menu')) {
			return $this->redirectToRoute('menu_week_menu');
		}

		// Sinon, redirige vers la page principale du repas du jour
		return $this->redirectToRoute('meal_day');
	}

	/**
	 * Récupère la liste des Food Group Parents (FGP) utilisés dans les repas fournis.
	 * 
	 * Cette méthode parcourt tous les repas pour chaque jour et chaque type de repas,
	 * extrait les plats et aliments, puis construit une liste unique de FGP.
	 *
	 * @param Request $request Objet Request pour accéder aux paramètres et session
	 * @param MealUtil $mealUtil Service utilitaire pour manipuler les repas
	 * @param FoodGroupParentRepository $foodGroupParentRepository Répository pour récupérer les Food Group Parents
	 * @param EntityManagerInterface $manager Gestionnaire d'entités Doctrine
	 * @param array $meals Tableau des repas organisés par jour et type de repas
	 * 
	 * @return Response Rendu de la vue Twig contenant la liste unique des FGP
	 */
	#[Route('/listfgp/{meals}', name: 'menu_week_list_fgp', methods: ['GET'])]
	public function getListFgpMeals(
		MealUtil $mealUtil,
		FoodGroupParentRepository $foodGroupParentRepository,
		$meals
	): Response {
		// Tableau pour stocker tous les Food Group Parents uniques trouvés dans les repas
		$listFgp = [];

		// Parcours de chaque jour dans le tableau des repas
		foreach ($meals as $day => $mealsByDay) {

			// Parcours de chaque type de repas (petit-déjeuner, déjeuner, dîner, etc.)
			foreach ($mealsByDay as $typeMeal => $mealsByType) {

				// Vérifie que le type de repas contient effectivement des repas
				if (!empty($mealsByType)) {

					// Parcours de chaque repas dans ce type
					foreach ($mealsByType as $meal) {

						// Récupère les plats et aliments du repas
						// Si $meal est une entité Meal, utilise getDishAndFoods(), sinon récupère directement l'array
						$dishAndFoods = ($meal instanceof Meal) ? $meal->getDishAndFoods() : $meal['dishAndFoods'];

						// Récupère la liste des FGP pour ce repas
						foreach ($mealUtil->getListfgp($dishAndFoods) as $fgp) {

							// Ajoute à la liste uniquement si le FGP n'est pas déjà présent
							if (!in_array($fgp, $listFgp)) {
								$listFgp[] = $fgp;
							}
						}
					}
				}
			}
		}

		// Retourne le rendu de la vue Twig avec la liste unique des FGP et tous les FGP principaux
		return $this->render(
			"meals/partials/_list-fgp.html.twig",
			[
				"listFgp" => $listFgp,
				"allFgp" => $foodGroupParentRepository->getIdsPrincipal()(), // IDs de tous les FGP principaux
			]
		);
	}

	#[Route('/listfgp/{id}', name: 'menu_week_meal_list_fgp', methods: ['GET'], requirements: ['id' => '\d+'])]
	public function getListFgpMeal(
		MealUtil $mealUtil,
		FoodGroupParentRepository $foodGroupParentRepository,
		Meal $meal,
		int $sizeTabletColorFgp = 5
	): Response {

		// Récupère la liste des Food Group Parents présents dans les plats et aliments du repas
		$listFgp = $mealUtil->getListfgp($meal->getDishAndFoods());

		// Récupère tous les Food Group Parents principaux pour les comparer ou les afficher
		$foodGroupParents = $foodGroupParentRepository->findByIsPrincipal(1);

		// Rendu de la vue Twig avec :
		// - listFgp : FGP utilisés dans le repas
		// - foodGroupParents : tous les FGP principaux
		// - size : taille pour l'affichage des couleurs
		return $this->render(
			"meals/partials/_list_fgp.html.twig",
			[
				"listFgp" => $listFgp,
				"foodGroupParents" => $foodGroupParents,
				"size" => $sizeTabletColorFgp
			]
		);
	}

	/**
	 * Affiche l'énergie totale des repas pour une journée spécifique de la semaine.
	 *
	 * Cette méthode permet d'afficher le total calorique d'une journée de repas
	 * et d'évaluer si l'énergie consommée est bien équilibrée par rapport à l'objectif de l'utilisateur.
	 * Elle peut être utilisée dans le contexte d'une vue de menu hebdomadaire.
	 *
	 * @param Request $request Objet Request pour récupérer les paramètres GET (taille du texte, couleurs, etc.)
	 * @param AlertFeature $alertFeature Service permettant de calculer si l'énergie est équilibrée
	 * @param LevelAlertRepository $levelAlertRepository Répository pour récupérer les niveaux d'alerte
	 * @param float $energyMeals Total d'énergie (en Kcal) consommée pour la journée
	 * @param string $highestAlertPerDay Code du niveau d'alerte le plus élevé pour cette journée
	 * 
	 * @return Response Rendu de la vue Twig affichant l'énergie et le niveau d'alerte
	 */
	#[Route('/meals-energy/{energyMeals}/{highestAlertPerDay}', name: 'menu_meals_show_total_energy', methods: ['GET'], requirements: ['energyMeals' => '\d+(\.\d+)?'])]
	public function mealsEnergy(
		Request $request,
		AlertFeature $alertFeature,
		LevelAlertRepository $levelAlertRepository,
		float $energyMeals,
		string $highestAlertPerDay
	): Response {

		/** @var App/Entity/User|null */
		$user = $this->getUser();

		// Rendu de la vue Twig '_show_total_energy.html.twig' avec :
		// - mealDayEnergy : l'énergie totale consommée pour la journée
		// - alert : objet indiquant si la consommation est bien équilibrée
		// - sizeText : option pour ajuster la taille du texte (paramètre GET)
		// - fromMenuWeek : booléen indiquant si la vue vient du menu hebdomadaire
		// - page : identifiant de la page pour le rendu (ici 'menu_detail')
		// - bgColor : couleur de fond, par défaut 'light-blue'
		// - showDetails : booléen pour afficher plus de détails si nécessaire
		// - highestAlertPerDay : objet LevelAlert correspondant au code fourni
		return $this->render('meals/week/_show_total_energy.html.twig', [
			'mealDayEnergy' => $energyMeals,
			'alert' => $alertFeature->isWellBalanced($energyMeals, $user->getEnergy()),
			'sizeText' => $request->query->get('sizeText'),
			'fromMenuWeek' => $request->query->has('fromMenuWeek') ? true : false,
			'page' => 'menu_detail',
			'bgColor' => $request->query->get('bgColor', 'light-blue'),
			'showDetails' => $request->query->get('showDetails', false),
			'highestAlertPerDay' => $levelAlertRepository->findOneBy(['code' => $highestAlertPerDay]),
		]);
	}

	// #[Route('/adviced-menu/{date}', name: 'menu_week_adviced_menu', methods: ['GET'], requirements: ['date' => '\d{4}-\d{2}-\d{2}'])]
	// public function getAdvicedMenu(Request $request, EntityManagerInterface $manager, TokenStorageInterface $tokenStorage, QuantityTreatment $quantityTreatment, DishUtil $dishUtil, MenuFeature $menuFeature, $date)
	// {
	// 	$user = $tokenStorage->getToken()->getUser();

	// 	//BREAKFAST
	// 	$breakfast = $menuFeature->getRandomBreakfast($date);

	// 	$remainingQuantitiesForDay = $quantityTreatment->remainingQuantitiesPerDay();

	// 	$quantitiesConsumed = $quantityTreatment->getQuantitiesConsumedNull();

	// 	$dishesSelected = $foodsSelected = [];

	// 	foreach ($breakfast->getDishAndFoods() as $element) {
	// 		if ('Dish' === $element['type']) {
	// 			$dishesSelected[] = (int)$element['id'];
	// 		} else {
	// 			$foodsSelected[] = (int)$element['id'];
	// 		}
	// 		$quantitiesConsumed = $quantityTreatment->getQuantitiesConsumed($element, $quantitiesConsumed);
	// 	}

	// 	foreach ($quantitiesConsumed as $fgpCode => $quantityConsumed) {
	// 		$remainingQuantitiesForDay[$fgpCode] -= $quantityConsumed;
	// 	}

	// 	//On répartie les quantités restantes dans les (éventuelles) collations et repas

	// 	$morningSnack = $user->getSnacks()->contains($manager->getRepository(TypeMeal::class)->findOneByBackName('morning_snack'));
	// 	$afternoonSnack = $user->getSnacks()->contains($manager->getRepository(TypeMeal::class)->findOneByBackName('afternoon_snack'));
	// 	$eveningSnack = $user->getSnacks()->contains($manager->getRepository(TypeMeal::class)->findOneByBackName('evening_snack'));

	// 	foreach ($remainingQuantitiesForDay as $fgpCode => $remainingQuantity) {
	// 		$firstPartOfDay[$fgpCode] = $secondPartOfDay[$fgpCode] = $remainingQuantity / 2;

	// 		if ($morningSnack) {
	// 			$quantitiesForMorningSnack[$fgpCode] = $firstPartOfDay[$fgpCode] / 3;
	// 			// $quantitiesForLunch[$fgpCode] = ($firstPartOfDay[$fgpCode] * 2) / 3;
	// 		} else {
	// 			$quantitiesForMorningSnack[$fgpCode] = 0;
	// 			$quantitiesForLunch[$fgpCode] = $firstPartOfDay[$fgpCode];
	// 		}

	// 		if ($afternoonSnack) {
	// 			$quantitiesForAfternoonSnack[$fgpCode] = $secondPartOfDay[$fgpCode] / 3;
	// 		} else {
	// 			$quantitiesForAfternoonSnack[$fgpCode] = 0;
	// 			$quantitiesForDinner[$fgpCode] = $secondPartOfDay[$fgpCode];
	// 		}
	// 	}

	// 	//COLLATION DU MATIN
	// 	if ($morningSnack) {

	// 		$morningSnackElements = $menuFeature->getSnack('morning_snack', $quantitiesForMorningSnack, $date, $foodsSelected, $dishesSelected);

	// 		$quantitiesConsumed = $quantityTreatment->getQuantitiesConsumedNull();

	// 		foreach ($morningSnackElements['meal']->getDishAndFoods() as $element) {
	// 			$quantitiesConsumed = $quantityTreatment->getQuantitiesConsumed($element, $quantitiesConsumed);
	// 		}

	// 		foreach ($quantitiesConsumed as $fgpCode => $quantityConsumed) {
	// 			$quantitiesForLunch[$fgpCode] = $firstPartOfDay[$fgpCode] - $quantityConsumed;
	// 		}
	// 	} else {

	// 		$morningSnackElements = ['foodsSelected' => $foodsSelected, 'dishesSelected' => $dishesSelected, 'meal' => null];
	// 	}

	// 	//DEJEUNER
	// 	$lunchElements = $menuFeature->getMeal('lunch', $quantitiesForLunch, $date, $foodsSelected, $dishesSelected);

	// 	//COLLATION DE L'APRES MIDI
	// 	if ($afternoonSnack) {
	// 		$afternoonSnackElements = $menuFeature->getSnack('afternoon_snack', $quantitiesForAfternoonSnack, $date, $foodsSelected, $dishesSelected);

	// 		$quantitiesConsumed = $quantityTreatment->getQuantitiesConsumedNull();

	// 		foreach ($afternoonSnackElements['meal']->getDishAndFoods() as $element) {
	// 			$quantitiesConsumed = $quantityTreatment->getQuantitiesConsumed($element, $quantitiesConsumed);
	// 		}

	// 		foreach ($quantitiesConsumed as $fgpCode => $quantityConsumed) {
	// 			$quantitiesForDinner[$fgpCode] = $secondPartOfDay[$fgpCode] - $quantityConsumed;
	// 		}
	// 	} else {

	// 		$afternoonSnackElements = ['foodsSelected' => $foodsSelected, 'dishesSelected' => $dishesSelected, 'meal' => null];
	// 	}

	// 	//DINNER
	// 	$dinnerElements = $menuFeature->getMeal('dinner', $quantitiesForDinner, $date, $lunchElements['foodsSelected'], $lunchElements['dishesSelected']);

	// 	$request->getSession()->set('_meal_' . $date, [$breakfast, $morningSnackElements['meal'], $lunchElements['meal'], $afternoonSnackElements['meal'], $dinnerElements['meal']]);

	// 	return $this->redirectToRoute('menu_week_menu');
	// }

	// #[Route('/adviced-menu/cancel/{date}', name: 'menu_week_adviced_menu_cancel', methods: ['POST'], requirements: ['date' => '\d{4}-\d{2}-\d{2}'])]
	// public function cancelAdvicedMenu(Request $request, $date)
	// {
	// 	$request->getSession()->remove('_meal_' . $date);

	// 	return $this->redirectToRoute('menu_week_menu');
	// }

	// #[Route('/adviced-menu/save/{date}', name: 'menu_week_adviced_menu_save', methods: ['POST'], requirements: ['date' => '\d{4}-\d{2}-\d{2}'])]
	// public function saveAdvicedMenu(Request $request, EntityManagerInterface $manager, TokenStorageInterface $tokenStorage, $date)
	// {
	// 	$user = $tokenStorage->getToken()->getUser();

	// 	foreach ($request->getSession()->get('_meal_' . $date) as $meal) {
	// 		if (null !== $meal) {
	// 			$type = $manager->getRepository(TypeMeal::class)->findOneById($meal->getType()->getId());
	// 			$meal->setType($type);
	// 			$meal->setUser($user);

	// 			$manager->persist($meal);
	// 		}
	// 	}

	// 	$request->getSession()->remove('_meal_' . $date);

	// 	$manager->flush();

	// 	return $this->redirectToRoute('menu_week_menu');
	// }
}
