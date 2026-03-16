<?php

namespace App\Controller\meal;

use App\Entity\Alert\LevelAlert;
use App\Entity\TypeMeal;
use App\Entity\MealModel;
use App\Service\FoodUtil;
use App\Service\DishUtil;
use App\Service\MealUtil;
use App\Service\AlertFeature;
use App\Service\EnergyHandler;
use App\Repository\DishRepository;
use App\Repository\FoodRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\FoodGroup\FoodGroup;
use App\Repository\UnitMeasureRepository;
use App\Repository\FoodGroupParentRepository;
use App\Repository\SearchRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * DefaultController.php
 *
 * Contrôleur principal de gestion des repas.
 *
 * Ce contrôleur centralise toutes les actions liées :
 * - à l'affichage des repas
 * - à l'ajout / suppression d'aliments ou de plats
 * - au calcul énergétique
 * - à la gestion des groupes alimentaires (FGP)
 * - aux alertes nutritionnelles
 * - aux interactions AJAX de l'interface
 *
 * La plupart des méthodes sont appelées via AJAX afin de mettre à jour
 * dynamiquement l'interface utilisateur sans recharger la page.
 *
 * Les calculs nutritionnels sont délégués aux services métier
 * (MealUtil, BalanceSheetFeature, EnergyCalculator, etc.).
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 * 
 * @package App\Controller\meal
 */
#[Route('/mes-repas', methods: ['GET'])]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class DefaultController extends AbstractController
{
	/**
	 * Affiche l'écran principal de gestion des repas pour une journée donnée.
	 *
	 * Cette méthode :
	 * - vérifie que l'utilisateur est connecté
	 * - récupère la date demandée (ou la date du jour par défaut)
	 * - charge les repas de la journée
	 * - calcule les indicateurs nutritionnels
	 * - prépare les données nécessaires à l'affichage du menu
	 *
	 * Les données retournées permettent d'afficher :
	 * - les repas de la journée
	 * - les aliments/plats associés
	 * - les indicateurs énergétiques
	 * - les éventuelles alertes nutritionnelles
	 *
	 * @return Response Page principale des repas
	 */
	#[Route('/repas-du-jour', name: 'meal_day', methods: ['GET'])]
	public function menu(
		Request $request,
		EntityManagerInterface $manager,
		AlertFeature $alertFeature,
		EnergyHandler $energyHandler,
		FoodGroupParentRepository $foodGroupParentRepository,
		MealUtil $mealUtil
	) {
		// Récupération de la session utilisateur
		$session = $request->getSession();

		// Liste des groupes alimentaires principaux (FGP) présents dans les plats/aliments sélectionnés
		$listFgp = [];

		/**
		 * Si une journée de repas existe déjà en session,
		 * on recharge les données pour recalculer les indicateurs nutritionnels.
		 */
		if ($session->has('_meal_day_range')) {

			// Recharge les données nutritionnelles (énergie + nutriments) en session
			$alertFeature->setEnergyAndNutrientsDataSession();

			// Énergie totale consommée sur la journée
			$energyDay = 0;

			/**
			 * Tableau servant à suivre l'évolution de l'énergie
			 * après chaque plat/aliment ajouté dans la journée.
			 *
			 * Exemple :
			 * repas 0 → énergie cumulée
			 * repas 1 → énergie cumulée
			 */
			$energies[0][0] = 0;

			/**
			 * Boucle sur tous les repas stockés dans la session
			 */
			for ($i = 0; $i <= $session->get('_meal_day_range'); $i++) {

				// Énergie totale du repas courant
				$energyMeal = 0;

				// Récupération du repas en session
				$meal = $session->get('_meal_day_' . $i);

				/**
				 * Initialisation du premier repas
				 */
				if ($i === 0) {
					$meal['rankPreviousType'] = null;

					// Type du premier repas
					$previousTypeMeal = $manager
						->getRepository(TypeMeal::class)
						->findOneByBackName($session->get('_meal_day_0')['type']);
				}

				// Type du repas courant
				$typeMeal = $manager
					->getRepository(TypeMeal::class)
					->findOneByBackName($session->get('_meal_day_' . $i)['type']);

				/**
				 * Pour les repas suivants, on initialise
				 * l'énergie cumulée à partir du repas précédent
				 */
				if ($i > 0) {
					$energies[$i][0] = end($energies[$i - 1]);
				}

				/**
				 * Si le repas contient des plats ou aliments
				 */
				if (array_key_exists('dishAndFoods', $session->get('_meal_day_' . $i))) {

					$dishAndFoods = $session->get('_meal_day_' . $i)['dishAndFoods'];

					// On récupère les FGP présents dans ces aliments/plats
					$listFgp = array_merge($mealUtil->getListfgp($dishAndFoods), $listFgp);

					/**
					 * Calcul de l'énergie pour chaque plat/aliment
					 */
					foreach ($dishAndFoods as $j => $dishOrFood) {

						// Calcul de l'énergie du plat/aliment sélectionné
						$energyDishOrFood = $energyHandler->getEnergyForDishOrFoodSelected(
							$dishOrFood['id'],
							$dishOrFood['type'],
							$dishOrFood['quantity'],
							$dishOrFood['unitMeasureAlias']
						);

						// Ajout à l'énergie du repas
						$energyMeal += $energyDishOrFood;

						// Ajout à l'énergie totale de la journée
						$energyDay += $energyDishOrFood;

						/**
						 * On stocke l'énergie cumulée
						 * pour afficher l'évolution énergétique
						 */
						$energies[$i][$j + 1] = $energyDay;
					}
				}

				/**
				 * Vérification de la cohérence de l'ordre des types de repas
				 * (petit déjeuner → déjeuner → dîner par exemple)
				 */
				if ($i > 0) {

					// Ranking du type de repas courant
					$rankType = null !== $typeMeal ? $typeMeal->getRanking() : null;

					// Type du repas précédent
					if (
						null !== $newPreviousTypeMeal =
						$manager->getRepository(TypeMeal::class)
						->findOneByBackName($session->get('_meal_day_' . ($i - 1))['type'])
					) {
						$previousTypeMeal = $newPreviousTypeMeal;
					}

					// Ranking du repas précédent
					$rankPreviousType = null !== $previousTypeMeal
						? $previousTypeMeal->getRanking()
						: 0;

					/**
					 * Si l'ordre des repas est incohérent
					 * on réinitialise le type du repas
					 */
					if ($rankType < $rankPreviousType) {
						$meal['type'] = null;
					}

					$meal['rankPreviousType'] = $rankPreviousType;
				}

				// Énergie totale du repas
				$meal['energy'] = $energyMeal;

				// Mise à jour du repas en session
				$session->set('_meal_day_' . $i, $meal);
			}

			/**
			 * Sauvegarde des indicateurs énergétiques de la journée
			 */
			$session->set('_meal_day_energy_evolution', $energies);
			$session->set('_meal_day_energy', $energyDay);
		} else {

			/**
			 * Initialisation d'une nouvelle journée de repas
			 */
			$session->set('_meal_day_range', 0);

			$meal['type'] = null;
			$meal['rankPreviousType'] = null;
			$meal['dishAndFoods'] = [];

			$session->set('_meal_day_0', $meal);
		}

		/**
		 * Réinitialisation du rang du plat sélectionné
		 */
		if ($session->has('rankDish') && null !== $session->get('rankDish')) {
			$session->set('rankDish', null);
		}

		/**
		 * Nettoyage des alertes liées aux plats
		 */
		if ($session->has('_meal_day_dish_alerts') && !empty($session->get('_meal_day_dish_alerts'))) {
			$session->set('_meal_day_dish_alerts', []);
		}

		// Réinitialisation des alertes de sélection
		$session->set('_meal_day_alerts/_dishes_not_selected', []);
		$session->set('_meal_day_alerts/_foods_not_selected', []);

		/**
		 * Génération des alertes nutritionnelles
		 * sur les aliments déjà sélectionnés
		 */
		$alertFeature->setAlertOnDishesAndFoodsAlreadySelected();

		/**
		 * Récupération des groupes alimentaires principaux
		 */
		$listFgpRemaining = $foodGroupParentRepository->getIdsPrincipal();

		/**
		 * Sauvegarde en session :
		 * - FGP présents
		 * - FGP absents
		 */
		$session->set('_meal_day/_list_fgp', $listFgp);
		$session->set('_meal_day/_list_fgp_remaining_absent', array_diff($listFgpRemaining, $listFgp));

		/**
		 * Si appel AJAX : on retourne uniquement le fragment HTML
		 */
		if ($request->query->get('ajax')) {

			return $this->render('meals/day/_meals_day.html.twig', [
				'foodGroups' => $manager->getRepository(FoodGroup::class)->findAll([], ['ranking' => 'ASC']),
				'page' => 0,
				'typeMeals' => $manager->getRepository(TypeMeal::class)->findBy([], ['ranking' => 'ASC']),
				'listFgp' => $listFgp,
				'listFgpRemainingAbsent' => array_diff($listFgpRemaining, $listFgp),
			]);
		}

		/**
		 * Affichage de la page complète des repas du jour
		 */
		return $this->render('meals/day/index.html.twig', [
			'foodGroups' => $manager->getRepository(FoodGroup::class)->findAll([], ['ranking' => 'ASC']),
			'page' => 0,
			'typeMeals' => $manager->getRepository(TypeMeal::class)->findBy([], ['ranking' => 'ASC']),
			'listFgp' => $listFgp,
			'listFgpRemainingAbsent' => array_diff($listFgpRemaining, $listFgp),
		]);
	}

	/**
	 * Ajoute un nouveau repas dans la journée stockée en session.
	 *
	 * Cette méthode :
	 * - détermine la position du nouveau repas dans la journée
	 * - initialise un repas vide dans la session
	 * - ou charge un modèle de repas existant si un identifiant est fourni
	 *
	 * Fonctionnement :
	 * 1. On récupère l'index du dernier repas enregistré dans la session (`_meal_day_range`).
	 * 2. Si le dernier repas contient déjà des plats/aliments, on incrémente cet index
	 *    afin de créer un nouveau repas.
	 * 3. On initialise le repas avec :
	 *    - un type de repas (null par défaut ou petit-déjeuner si création de modèle)
	 *    - une liste vide de plats/aliments (`dishAndFoods`).
	 * 4. Si un identifiant de modèle de repas (`idMealModel`) est fourni :
	 *    - on récupère le modèle en base de données
	 *    - on copie ses données dans le repas courant
	 *    - on met à jour les calculs nutritionnels en session.
	 *
	 * Si la requête est AJAX, la méthode retourne une réponse JSON contenant
	 * l'URL de redirection vers la page des repas du jour.
	 *
	 * @param Request $request Requête HTTP contenant notamment la session utilisateur
	 * @param EntityManagerInterface $manager Gestionnaire Doctrine permettant l'accès aux entités
	 * @param AlertFeature $alertFeature Service chargé de recalculer les données nutritionnelles
	 * @param int|null $idMealModel Identifiant optionnel d'un modèle de repas à charger
	 *
	 * @return Response|JsonResponse Redirection vers la page des repas ou réponse JSON en AJAX
	 */
	#[Route('/crud/add/{idMealModel?}', name: 'meal_day_add', requirements: ['idMealModel' => '\d+'], options: ['expose' => true])]
	public function add(Request $request, EntityManagerInterface $manager, AlertFeature $alertFeature, ?int $idMealModel = null)
	{
		// Récupération de la session utilisateur
		$session = $request->getSession();

		// Récupération de l'index du dernier repas enregistré dans la journée
		// Si aucun repas n'existe encore, on démarre à 0
		$rangeMeal = $session->has('_meal_day_range') ? $session->get('_meal_day_range') : 0;

		// Si le dernier repas possède déjà des plats/aliments,
		// on incrémente l'index pour créer un nouveau repas
		if (!empty($session->get('_meal_day_' . $rangeMeal)['dishAndFoods'])) {
			$rangeMeal += 1;
		}

		// Mise à jour de l'index maximal des repas dans la session
		$session->set('_meal_day_range', $rangeMeal);

		// Si la journée correspond à un modèle (création d'un modèle de repas),
		// on initialise automatiquement le type de repas à "petit-déjeuner"
		if ($request->getSession()->get('_meal_day_date') ==  'model') {
			$meal['type'] = 'meal.type.breakfast';
		} else {
			// Sinon le type de repas est vide et sera choisi par l'utilisateur
			$meal['type'] = null;
		}

		// Initialisation de la liste des plats/aliments du repas
		$meal['dishAndFoods'] = [];

		// Sauvegarde du nouveau repas dans la session
		$session->set('_meal_day_' . $rangeMeal, $meal);

		// Si un identifiant de modèle de repas est fourni,
		// on va pré-remplir le repas avec les données du modèle
		if (null !== $idMealModel) {

			// Récupération du modèle de repas en base
			$modelMeal = $manager->getRepository(MealModel::class)->findOneById($idMealModel);

			// Copie des informations du modèle dans le repas courant
			$meal['id'] = $modelMeal->getId();
			$meal['name'] = $modelMeal->getName();
			$meal['type'] = $modelMeal->getType()->getBackName();
			$meal['dishAndFoods'] = $modelMeal->getDishAndFoods();

			// Mise à jour du repas dans la session avec les données du modèle
			$session->set('_meal_day_' . $rangeMeal, $meal);

			// Recalcul des données nutritionnelles (énergie et nutriments)
			$alertFeature->setEnergyAndNutrientsDataSession();

			// Si la requête est une requête AJAX
			if ($request->isXmlHttpRequest()) {

				// On retourne une réponse JSON indiquant le succès
				// avec l'URL de redirection vers la page des repas du jour
				return $this->json([
					'success' => true,
					'redirectUrl' => $this->generateUrl('meal_day')
				]);
			}
		} else {
			// Si aucun modèle n'est utilisé,
			// on met simplement à jour les calculs nutritionnels
			$alertFeature->setEnergyAndNutrientsDataSession();
		}

		// Redirection vers la page principale des repas du jour
		return $this->redirectToRoute('meal_day');
	}

	/**
	 * Supprime un repas de la journée stockée en session.
	 *
	 * Cette méthode permet de retirer un repas spécifique de la journée
	 * en utilisant son index (`rankMeal`) transmis dans les paramètres GET.
	 *
	 * Fonctionnement :
	 * 1. On vérifie si le paramètre `rankMeal` est présent dans la requête.
	 * 2. Si oui, on appelle le service `MealUtil` pour supprimer le repas
	 *    correspondant dans la session utilisateur.
	 * 3. Si aucun `rankMeal` n'est fourni, on redirige vers la page permettant
	 *    de supprimer des repas depuis le menu de la semaine.
	 * 4. Après suppression, l'utilisateur est redirigé vers la page
	 *    des repas du jour.
	 *
	 * @param Request $request Requête HTTP contenant les paramètres GET et la session utilisateur
	 * @param MealUtil $mealUtil Service métier gérant les opérations sur les repas en session
	 *
	 * @return Response Redirection vers la page des repas du jour ou vers la suppression hebdomadaire
	 */
	#[Route('/remove', name: 'meal_day_remove', methods: ['GET'], options: ['expose' => true])]
	public function remove(Request $request, MealUtil $mealUtil)
	{
		/**
		 * Vérifie si l'index du repas à supprimer est présent dans la requête GET.
		 * Le paramètre `rankMeal` correspond à la position du repas dans la session.
		 */
		if ($request->query->has('rankMeal')) {

			// Suppression du repas correspondant dans la session via le service métier
			$mealUtil->removeMealSession($request->query->get('rankMeal'));
		} else {

			/**
			 * Si aucun index de repas n'est fourni,
			 * on redirige vers la page permettant de supprimer des repas
			 * depuis la vue du menu hebdomadaire.
			 */
			return $this->redirectToRoute('menu_week_remove_meals', [
				'date' => $request->getSession()->get('_meal_day_date'),
			]);
		}

		/**
		 * Après suppression du repas,
		 * on redirige l'utilisateur vers la page des repas du jour.
		 */
		return $this->redirectToRoute('meal_day');
	}

	/**
	 * Ajoute ou met à jour un plat ou un aliment dans un repas de la journée.
	 *
	 * Cette méthode permet d'ajouter un élément (plat ou aliment) dans un repas
	 * stocké dans la session utilisateur.
	 *
	 * Fonctionnement :
	 * 1. Récupération de l'identifiant et du type de l'élément (Dish ou Food).
	 * 2. Détermination de l'unité de mesure utilisée (gramme par défaut).
	 * 3. Chargement de l'entité correspondante en base de données.
	 * 4. Calcul des données nutritionnelles nécessaires (notamment l'énergie).
	 * 5. Ajout ou mise à jour de l'élément dans le repas sélectionné en session.
	 * 6. Recalcul des données nutritionnelles globales de la journée.
	 * 7. Mise à jour des alertes nutritionnelles associées aux aliments sélectionnés.
	 *
	 * La méthode peut être appelée en AJAX pour éviter un rechargement complet de la page.
	 *
	 * @param Request $request Requête HTTP contenant les paramètres GET
	 * @param AlertFeature $alertFeature Service chargé des calculs nutritionnels et alertes
	 * @param UnitMeasureRepository $unitMeasureRepository Repository des unités de mesure
	 * @param DishRepository $dishRepository Repository des plats
	 * @param FoodRepository $foodRepository Repository des aliments
	 *
	 * @return Response Redirection vers la page des repas ou réponse AJAX
	 */
	#[Route('/plat/add', name: 'meal_day_add_dish_or_food', methods: ['GET'])]
	public function addDishOrFood(Request $request, AlertFeature $alertFeature, UnitMeasureRepository $unitMeasureRepository, DishRepository $dishRepository, FoodRepository $foodRepository)
	{
		// Récupération de la session utilisateur
		$session = $request->getSession();

		// Identifiant et type de l'élément à ajouter (plat ou aliment)
		$id = $request->query->get('id');
		$type = $request->query->get('type');

		/**
		 * Détermination de l'unité de mesure utilisée pour la quantité.
		 * Si une unité est fournie dans la requête, on la récupère en base.
		 * Sinon on utilise le gramme comme unité par défaut.
		 */
		if ($request->query->has('unitMeasure')) {
			$unitMeasure = $unitMeasureRepository->findOneById((int)$request->query->get('unitMeasure'));
			$unitMeasureAlias = $unitMeasure->getAlias();
		} else {
			$unitMeasure = $unitMeasureRepository->findOneByAlias('g')->getId();
			$unitMeasureAlias = 'g';
		}

		/**
		 * Sélection du repository en fonction du type d'élément :
		 * - Dish (plat)
		 * - Food (aliment)
		 */
		$repo = ('Dish' === $type || 'dish' === $type) ? $dishRepository : $foodRepository;

		/**
		 * Extraction des données énergétiques de l'élément sélectionné
		 * en fonction de sa quantité et de son unité de mesure.
		 */
		$energyElement = $alertFeature->extractDataFromDishOrFoodSelected(
			'energy',
			$repo->findOneById((int)$id),
			(float)$request->query->get('quantity'),
			$unitMeasureAlias
		);

		/**
		 * Création de la structure représentant le plat/aliment sélectionné
		 * qui sera stockée dans la session du repas.
		 */
		$newDishOrFood = [
			'id' => $id,
			'type' => $type,
			'quantity' => $request->query->get('quantity'),
			'unitMeasure' => $request->query->get('unitMeasure'),
			'unitMeasureAlias' => $unitMeasureAlias,
		];

		/**
		 * Détermination du repas concerné :
		 * - soit via le paramètre GET
		 * - soit via la session
		 */
		$rankMeal = $request->query->has('rankMeal') ? $request->query->get('rankMeal') : (int)$session->get('rankMeal');

		$meal = $session->has('_meal_day_' . $rankMeal) ? $session->get('_meal_day_'  . $rankMeal) : [];

		/**
		 * Si un rang de plat est fourni, on met à jour ce plat.
		 * Sinon on ajoute simplement un nouvel élément à la liste.
		 */
		if ($request->query->has('rankDish') && "" != $request->query->get('rankDish') && "all" != $request->query->get('rankDish')) {
			$rankDish = (int)$request->query->get('rankDish');
			$meal['dishAndFoods'][$rankDish] = $newDishOrFood;
		} else {
			$rankDish = 0;
			$meal['dishAndFoods'][] = $newDishOrFood;
		}

		// Mise à jour du repas dans la session
		$session->set('_meal_day_' . $rankMeal, $meal);

		/**
		 * Mise à jour des données nutritionnelles stockées en session :
		 * énergie totale, nutriments, etc.
		 */
		$alertFeature->setEnergyAndNutrientsDataSession();

		/**
		 * Mise à jour des alertes liées aux aliments/plats déjà sélectionnés
		 */
		$alertFeature->setAlertOnDishesAndFoodsAlreadySelected();

		/**
		 * Si la requête est AJAX, on renvoie simplement un statut OK
		 * sans recharger la page.
		 */
		if ($request->query->get('ajax')) {
			return new Response('OK', Response::HTTP_NO_CONTENT);
		}

		// Redirection vers la page des repas du jour
		return $this->redirectToRoute('meal_day');
	}

	/**
	 * Supprime un plat ou un aliment d'un repas de la journée.
	 *
	 * Cette méthode permet de retirer un élément spécifique d'un repas stocké
	 * dans la session utilisateur, soit individuellement, soit tous les éléments
	 * à partir d'un certain rang.
	 *
	 * Fonctionnement :
	 * 1. Récupération du repas et du plat/aliment ciblé via les paramètres GET.
	 * 2. Suppression de l'élément du tableau dishAndFoods :
	 *    - Si fromRankDishToTheEnd est présent, on supprime tous les éléments à partir du rang spécifié.
	 *    - Sinon, on supprime uniquement le plat/aliment ciblé.
	 * 3. Réorganisation des indices du tableau dishAndFoods après suppression.
	 * 4. Mise à jour des données nutritionnelles et alertes en session.
	 * 5. Retour soit via AJAX, soit redirection vers la page des repas.
	 *
	 * @param Request $request Requête HTTP contenant les paramètres GET
	 * @param MealUtil $mealUtil Service utilitaire pour manipuler les repas en session
	 * @param AlertFeature $alertFeature Service chargé des calculs nutritionnels et alertes
	 *
	 * @return Response Redirection vers la page des repas ou réponse AJAX
	 */
	#[Route('/plat/remove', name: 'meal_day_remove_dish', methods: ['GET'], options: ['expose' => true])]
	public function removeDish(Request $request, MealUtil $mealUtil, AlertFeature $alertFeature)
	{
		// Récupération des indices du repas et du plat/aliment
		$rankMeal = $request->query->get('rankMeal');
		$rankDish = $request->query->get('rankDish');

		// Récupération du repas depuis la session
		$session = $request->getSession();
		$meal = $session->get('_meal_day_'  . $rankMeal);

		/**
		 * Suppression du plat/aliment :
		 * - Si 'fromRankDishToTheEnd' est fourni, on supprime tous les éléments
		 *   à partir du rang spécifié.
		 * - Sinon, on supprime uniquement l'élément ciblé et on réindexe le tableau.
		 */
		if ($request->query->get('fromRankDishToTheEnd')) {
			$meal['dishAndFoods'] = array_slice($meal['dishAndFoods'], 0, $rankDish);
		} else {
			unset($meal['dishAndFoods'][$rankDish]);
			$meal['dishAndFoods'] = array_values($meal['dishAndFoods']);
		}

		// Mise à jour du repas dans la session
		$session->set('_meal_day_'  . $rankMeal, $meal);

		// Mise à jour des données nutritionnelles et alertes
		$alertFeature->setEnergyAndNutrientsDataSession();
		$alertFeature->setAlertOnDishesAndFoodsAlreadySelected();

		/**
		 * Si la requête est AJAX, on renvoie juste un statut OK
		 * sans recharger la page.
		 */
		if ($request->query->get('ajax')) {
			return new Response('OK', Response::HTTP_NO_CONTENT);
		}

		// Redirection vers la page des repas
		return $this->redirectToRoute('meal_day');
	}

	/**
	 * Supprime plusieurs plats ou aliments sélectionnés d'un repas de la journée.
	 *
	 * Cette méthode permet de retirer un ou plusieurs éléments d'un repas
	 * identifié par son rang dans la session. Les indices des éléments à
	 * supprimer sont fournis via le paramètre GET 'rankDishes', séparés par des virgules.
	 *
	 * Fonctionnement :
	 * 1. Récupération du repas ciblé depuis la session.
	 * 2. Conversion de la chaîne 'rankDishes' en tableau d'indices.
	 * 3. Suppression des éléments correspondants dans le tableau dishAndFoods.
	 * 4. Réindexation du tableau dishAndFoods après suppression.
	 * 5. Mise à jour des données nutritionnelles et alertes.
	 * 6. Retour soit via AJAX, soit redirection vers la page des repas.
	 *
	 * @param Request $request Requête HTTP contenant les paramètres GET
	 * @param AlertFeature $alertFeature Service chargé des calculs nutritionnels et alertes
	 * @param int $rankMeal Rang du repas dans la session
	 *
	 * @return Response Redirection vers la page des repas ou réponse AJAX
	 */
	#[Route('/plat/remove-selection/{rankMeal}', name: 'meal_day_remove_dish_selection', methods: ['GET'], requirements: ['rankMeal' => '\d+'])]
	public function removeDishSelection(Request $request, AlertFeature $alertFeature, int $rankMeal)
	{
		// Récupération des indices des plats/aliments à supprimer
		$rankDishes = $request->query->get('rankDishes');

		// Récupération du repas depuis la session
		$session = $request->getSession();
		$meal = $session->get('_meal_day_' . $rankMeal);

		// Conversion de la chaîne en tableau et suppression des éléments ciblés
		$rankDishes = explode(',', $rankDishes);
		$dishAndFoods = $meal['dishAndFoods'];
		foreach ($rankDishes as $rankDish) {
			unset($dishAndFoods[(int)$rankDish]);
		}

		// Réindexation du tableau dishAndFoods et mise à jour du repas en session
		$meal['dishAndFoods'] = array_values($dishAndFoods);
		$session->set('_meal_day_' . $rankMeal, $meal);

		// Mise à jour des données nutritionnelles et alertes
		$alertFeature->setEnergyAndNutrientsDataSession();
		$alertFeature->setAlertOnDishesAndFoodsAlreadySelected();

		// Retour AJAX si demandé
		if ($request->query->get('ajax')) {
			return new Response('OK', Response::HTTP_NO_CONTENT);
		}

		// Redirection vers la page des repas
		return $this->redirectToRoute('meal_day');
	}

	/**
	 * Supprime une ou plusieurs sélections de repas de la journée.
	 *
	 * Cette méthode permet de retirer plusieurs repas identifiés par leurs rangs
	 * dans la session. Les rangs sont transmis via le paramètre GET 'rankMeals',
	 * séparés par des virgules.
	 *
	 * Fonctionnement :
	 * 1. Vérifie la présence de 'rankMeals' dans la requête.
	 * 2. Pour chaque rang, supprime le repas correspondant en session.
	 * 3. Met à jour les données nutritionnelles et les alertes.
	 * 4. Retourne une réponse AJAX ou redirige vers la page des repas.
	 * 5. Si aucun rang n'est fourni, redirige vers la page de suppression hebdomadaire.
	 *
	 * @param Request $request Requête HTTP contenant les paramètres GET
	 * @param EntityManagerInterface $manager Gestionnaire d'entités Doctrine
	 * @param MealUtil $mealUtil Service utilitaire pour manipuler les repas en session
	 * @param AlertFeature $alertFeature Service pour gérer les alertes et les données nutritionnelles
	 *
	 * @return Response Redirection vers la page des repas ou réponse AJAX
	 */
	#[Route('/remove-selection', name: 'meal_day_remove_selection', methods: ['GET'])]
	public function removeSelection(Request $request, EntityManagerInterface $manager, MealUtil $mealUtil, AlertFeature $alertFeature)
	{
		$session = $request->getSession();

		// Vérifie si des rangs de repas sont fournis
		if ($request->query->has('rankMeals')) {
			$rankMeals = explode(',', $request->query->get('rankMeals'));

			// Suppression de chaque repas correspondant en session
			foreach ($rankMeals as $rankMeal) {
				$mealUtil->removeMealSession($rankMeal);
			}

			// Mise à jour des données nutritionnelles et alertes
			$alertFeature->setEnergyAndNutrientsDataSession();
			$alertFeature->setAlertOnDishesAndFoodsAlreadySelected();
		} else {
			// Redirection vers la page de suppression hebdomadaire si aucun rang fourni
			return $this->redirectToRoute('menu_week_remove_meals', [
				'date' => $session->get('_meal_day_date'),
			]);
		}

		// Retour AJAX si demandé
		if ($request->query->get('ajax')) {
			return new Response('OK', Response::HTTP_NO_CONTENT);
		}

		// Redirection vers la page des repas
		return $this->redirectToRoute('meal_day');
	}

	/**
	 * Met à jour le type d'un repas spécifique et réinitialise les types des repas suivants.
	 *
	 * Cette méthode permet de modifier le type d'un repas donné (par exemple : petit-déjeuner,
	 * déjeuner, dîner) identifié par son rang dans la session. Tous les repas suivants
	 * verront leur type réinitialisé à null afin de maintenir l'ordre des types de repas.
	 *
	 * @param Request $request Requête HTTP contenant les paramètres GET :
	 *                        - 'rankMeal' : rang du repas à modifier
	 *                        - 'type' : nouveau type de repas
	 *
	 * @return Response Redirection vers la page des repas ou réponse AJAX
	 */
	#[Route('/plat/update-type', name: 'meal_day_update_type_meal', methods: ['GET'])]
	public function updateTypeMeal(Request $request)
	{
		$rankMeal = $request->query->get('rankMeal');
		$type = $request->query->get('type');

		$session = $request->getSession();

		// Récupère le repas correspondant au rang
		$meal = $session->has('_meal_day_' . $rankMeal) ? $session->get('_meal_day_' . $rankMeal) : [];

		// Mise à jour du type du repas
		$meal['type'] = $type;
		$session->set('_meal_day_' . $rankMeal, $meal);

		// Réinitialisation du type des repas suivants
		for ($i = $rankMeal + 1; $i <= $session->get('_meal_day_range'); $i++) {
			$meal = $session->get('_meal_day_' . $i);
			$meal['type'] = null;
			$session->set('_meal_day_' . $i, $meal);
		}

		// Retour AJAX si demandé
		if ($request->query->get('ajax')) {
			return new Response('OK', Response::HTTP_NO_CONTENT);
		}

		// Redirection vers la page principale des repas
		return $this->redirectToRoute('meal_day');
	}

	/**
	 * Liste les plats et aliments pour le module "repas du jour" via AJAX.
	 *
	 * Permet de rechercher et de filtrer les plats/aliments selon différents critères :
	 * mot-clé, groupe d'aliments, restrictions alimentaires (sans lactose, sans gluten),
	 * type d'élément (plat ou aliment), et seulement les éléments autorisés.
	 *
	 * La méthode gère également la pagination en session avec un offset réel pour éviter
	 * de récupérer plusieurs fois les mêmes éléments.
	 *
	 * @param Request $request Requête HTTP contenant les paramètres GET :
	 *                        - 'rankMeal' : rang du repas à mettre à jour (optionnel)
	 *                        - 'updateDish' : rang du plat à mettre à jour (optionnel)
	 *                        - 'fg' : tableau de groupes d'aliments filtrés (optionnel)
	 *                        - 'q' : mot-clé de recherche (optionnel)
	 *                        - 'freeLactose', 'freeGluten', 'onlyAllowed' : filtres booléens
	 *                        - 'typeItem' : type d'élément recherché (plat ou aliment)
	 *                        - 'page' : numéro de page pour la pagination (optionnel)
	 *
	 * @param SearchRepository $searchRepository Repository pour rechercher plats et aliments
	 * @param AlertFeature $alertFeature Service pour gérer les alertes sur la sélection
	 * @param UnitMeasureRepository $unitMeasureRepository Repository des unités de mesure
	 *
	 * @return Response Retourne le rendu Twig des résultats ou une réponse AJAX
	 */
	#[Route('/list/list-ajax', name: 'meal_day_list_ajax', methods: ['GET'], options: ['expose' => true])]
	public function listAjax(
		Request $request,
		SearchRepository $searchRepository,
		AlertFeature $alertFeature,
		UnitMeasureRepository $unitMeasureRepository,
		FoodRepository $foodRepository,
		DishRepository $dishRepository,
		FoodUtil $foodUtil, 
		DishUtil $dishUtil,
	): Response {

		$session = $request->getSession();

		// Détermination du rang du repas et du plat à mettre à jour
		// Vérifie si le paramètre 'rankMeal' est présent dans la requête GET
		// Ce paramètre indique quel repas de la journée est en train d'être modifié ou consulté
		if ($request->query->has('rankMeal')) {
			$rankMeal = $request->query->get('rankMeal'); // Récupère le rang du repas (ex: 0 pour le premier repas)

			// Vérifie si l'on met à jour un plat existant dans ce repas
			// 'updateDish' peut contenir le rang du plat à mettre à jour ou la valeur "none"
			if ("none" !== $request->query->get('updateDish')) {
				$rankDish = $request->query->get('updateDish'); // Rang du plat à mettre à jour
				$update = true; // Flag indiquant qu'il s'agit d'une modification
			} else {
				// Aucun plat précis n'est sélectionné pour mise à jour
				// On considère donc que l'on va ajouter un nouveau plat à la fin du repas
				$rankDish = count($session->get('_meal_day_' . $rankMeal)['dishAndFoods']);
				$update = false; // Flag indiquant qu'il s'agit d'un ajout et non d'une modification
			}
		}

		// Filtrage par groupe d'aliments
		$fglist = $request->query->has('fg') ? $request->query->all()['fg'] : [];

		// Si aucun groupe sélectionné, on retourne immédiatement un rendu vide
		if (empty($fglist) && isset($rankMeal)) {
			return $this->render(
				"meals/day/list-ajax.html.twig",
				[
					"results" => null,
					"lastResults" => true,
					"unitMeasures" => $unitMeasureRepository->findAll(),
					"rankMeal" => $rankMeal,
					"rankDish" => $rankDish,
				]
			);
		}

		// Paramètres de recherche
		$keyword      = $request->query->get('q');
		$freeLactose  = (bool) $request->query->get('freeLactose', false);
		$freeGluten   = (bool) $request->query->get('freeGluten', false);
		$onlyAllowed  = (bool) $request->query->get('onlyAllowed', false);
		$typeItem     = $request->query->get('typeItem');

		$limit  = 12;
		$page   = (int) $request->query->get('page', 0);

		// Reset session pour nouvelle recherche ou première page
		if ($page === 0) {
			$session->set('real_offset', 0);
			$session->set('_meal_day_alerts/_foods_not_selected', []);
			$session->set('_meal_day_alerts/_dishes_not_selected', []);
		}

		$realOffset = $session->get('real_offset', 0);
		$validItems = [];

		// Tant que le nombre d'éléments valides collectés est inférieur à la limite souhaitée
		while (count($validItems) < $limit) {

			// On interroge le repository pour récupérer un batch de plats et aliments depuis la base de données
			// La requête prend en compte les filtres : mot-clé, groupes d'aliments sélectionnés, restrictions alimentaires, type d'élément
			// Limit et offset servent à paginer les résultats
			$result = $searchRepository->searchFoodAndDish(
				keyword: $keyword,
				fglist: $fglist,
				freeLactose: $freeLactose,
				freeGluten: $freeGluten,
				typeItem: $typeItem,
				limit: $limit,
				offset: $realOffset
			);

			// Si la requête ne renvoie aucun élément, on sort de la boucle
			if (empty($result['data'])) {
				break; // Plus d'éléments disponibles dans la base
			}

			// Parcours des éléments récupérés dans ce batch
			foreach ($result['data'] as $item) {
				$realOffset++; // Incrémente l'offset réel pour la prochaine requête si nécessaire

				if (isset($rankMeal) && isset($rankDish)) {
					// Vérifie si l'élément déclenche des alertes (ex : déjà sélectionné, incompatibilité, etc.)
					$alerts = $alertFeature->setAlertOnSingleDishOrFoodAboutTobeSelected($rankMeal, $rankDish, $item);

					// On ajoute l'élément à la liste des valides seulement si :
					// - on ne filtre pas uniquement les éléments autorisés, ou
					// - il n'y a pas d'alerte
					if (!$onlyAllowed || !$alerts) {
						$validItems[] = $item;
					}
				} else {
					// Si on n'est pas en train de mettre à jour un plat précis, tous les éléments sont valides
					$validItems[] = $item;
				}

				// Si la limite de résultats valides est atteinte, on sort de la boucle pour éviter d'aller plus loin
				if (count($validItems) >= $limit) {
					break;
				}
			}
		}

		// On vérifie si les items sont restreints par l'utilisateur (aliments non consommés, régimes)
		$itemAnalysis = [];

		foreach ($validItems as $item) {

			if ($item['item_type'] === 'Food') {

				$food = $foodRepository->find($item['id']);
				$reasons = $foodUtil->getForbiddenReasons($food);

			} else {

				$dish = $dishRepository->find($item['id']);
				$reasons = $dishUtil->getForbiddenReasons($dish);

			}

			if(!empty($reasons)) {
				$itemAnalysis[] = [
					'id' => $item['id'],
					'name' => $item['name'],
					'type' => $item['item_type'],
					'forbidden' => !empty($reasons),
					'reasons' => $reasons
				];
			}
		}

		// Mise à jour de l'offset réel en session
		$session->set('real_offset', $realOffset);

		// Rendu Twig selon contexte
		if (isset($rankMeal) && isset($rankDish)) {
			return $this->render(
				"meals/day/list-ajax.html.twig",
				[
					"results" => $validItems,
					"itemAnalysis" => $itemAnalysis,
					"keyword" => $keyword,
					"rankMeal" => $rankMeal,
					"rankDish" => $rankDish,
					"update" => $update,
					"lastResults" => count($validItems) < $limit,
					"page" => $page,
					"unitMeasures" => $unitMeasureRepository->getIdAliasArray(),
				]
			);
		}

		// Génère la réponse HTML en utilisant le template Twig "_searchResult.html.twig"
		// Ce template affichera la liste des résultats de recherche filtrés pour les plats et aliments
		$response = $this->render("navigation/_searchResult.html.twig", [
			"results" => $validItems,               // Les plats/aliments valides récupérés après filtrage
			"keyword" => $keyword,                  // Le mot-clé recherché par l'utilisateur
			"lastResults" => count($validItems) < $limit, // Boolean indiquant si nous avons atteint la fin des résultats
		]);

		// Définit un en-tête HTTP personnalisé "X-Last-Results"
		// Cet en-tête peut être utilisé côté front pour savoir si la pagination peut continuer
		// "1" signifie qu'il n'y a plus de résultats à charger, "0" signifie qu'il y a potentiellement d'autres résultats
		$response->headers->set('X-Last-Results', count($validItems) < $limit ? '1' : '0');

		return $response;
	}

	/**
	 * Ajoute un repas à partir d'un modèle de repas existant.
	 *
	 * Cette méthode récupère le modèle de repas identifié par $idModelMeal et
	 * l'ajoute à la session de l'utilisateur comme un nouveau repas de la journée.
	 * Le rang du repas est automatiquement incrémenté par rapport aux repas déjà présents.
	 *
	 * @Route("/add-meal-from-list-model/{idModelMeal}", name="add_meal_from_list_model", methods={"GET"}, requirements={"idModelMeal"="\d+"})
	 *
	 * @param EntityManagerInterface $manager  Gestionnaire d'entités pour accéder aux modèles de repas
	 * @param Request                $request  Requête HTTP pour récupérer la session
	 * @param int                    $idModelMeal  ID du modèle de repas à ajouter
	 *
	 * @return Response Redirection vers la page principale des repas du jour
	 */
	#[Route('/add-meal-from-list-model/{idModelMeal}', name: 'add_meal_from_list_model', methods: ['GET'], requirements: ['idModelMeal' => '\d+'])]
	public function addMealFromlistModelMeal(EntityManagerInterface $manager, Request $request, $idModelMeal): Response
	{
		// Récupération de la session utilisateur
		$session = $request->getSession();

		// On récupère le modèle de repas correspondant à l'ID fourni
		$modelMeal = $manager->getRepository(MealModel::class)->findOneById($idModelMeal);

		// Détermine le prochain rang de repas dans la journée
		$rankMeal = $session->get('_meal_day_range') + 1;
		$session->set('_meal_day_range', $rankMeal);

		// Prépare le tableau représentant le repas à partir du modèle
		$meal['name'] = $modelMeal->getName(); // Nom du repas
		$meal['type'] = $modelMeal->getType()->getBackName(); // Type de repas (ex: breakfast, lunch)
		$meal['dishAndFoods'] = $modelMeal->getDishAndFoods(); // Plats et aliments associés

		// Stocke ce repas dans la session à la position correspondante
		$session->set('_meal_day_' . $rankMeal, $meal);

		// Redirige vers la page principale des repas du jour pour afficher le repas ajouté
		return $this->redirectToRoute('meal_day');
	}

	/**
	 * Récupère les quantités par groupe alimentaire parent (FGP) pour un plat ou un aliment donné.
	 *
	 * Cette méthode est utilisée pour calculer et afficher les quantités de chaque
	 * groupe alimentaire parent associées à un plat ou un aliment, en fonction
	 * de la portion sélectionnée.
	 *
	 * @param DishRepository               $dishRepository             Repository pour accéder aux plats
	 * @param FoodRepository               $foodRepository             Repository pour accéder aux aliments
	 * @param FoodGroupParentRepository    $foodGroupParentRepository  Repository des groupes alimentaires parents
	 * @param DishUtil                     $dishUtils                  Service utilitaire pour les calculs sur les plats
	 * @param array                        $item                       Tableau contenant les informations du plat ou aliment sélectionné (clé 'type' et 'id', et 'quantity')
	 *
	 * @return Response Rendu du template affichant les quantités par groupe alimentaire
	 */
	#[Route('/fgpQuantitiesByDishOrFood', name: 'app_meal_fgp_quantities_by_dish_or_food', methods: ['GET'])]
	public function fgpQuantitiesByDishOrFood(
		DishRepository $dishRepository,
		FoodRepository $foodRepository,
		FoodGroupParentRepository $foodGroupParentRepository,
		DishUtil $dishUtils,
		array $item
	): Response {
		// Vérifie si l'élément sélectionné est un plat
		if ('Dish' == $item['type']) {
			// Récupère le plat correspondant à l'ID fourni
			$dishOrFood = $dishRepository->findOneById($item['id']);
			// Calcule les quantités par groupe alimentaire parent pour la portion choisie
			$quantities = null !== $dishOrFood
				? $dishUtils->getFoodGroupParentQuantitiesForNPortion($dishOrFood, $item['quantity'])
				: [];
		}
		// Si l'élément sélectionné est un aliment
		elseif ('Food' == $item['type']) {
			// Récupère l'aliment correspondant à l'ID fourni
			$dishOrFood = $foodRepository->findOneById($item['id']);
			// Associe la quantité à son groupe alimentaire parent
			$quantities[$dishOrFood->getFoodGroup()->getParent()->getAlias()] = $item['quantity'];
		}

		// Rendu du template Twig avec les quantités et informations nécessaires
		return $this->render('meals/day/fgp-quantities-by-dish-or-food.html.twig', [
			'quantities' => $quantities,  // Quantités calculées pour chaque FGP
			'foodGroupParents' => $foodGroupParentRepository->findByIsPrincipal(1), // Liste des FGP principaux
			'type' => $item['type'],      // Type de l'élément ('Dish' ou 'Food')
			'dishOrFood' => $dishOrFood   // L'entité correspondant au plat ou à l'aliment
		]);
	}

	/**
	 * Affiche la liste totale des quantités par groupe alimentaire parent (FGP) pour un ensemble d'éléments.
	 *
	 * Cette méthode est utilisée pour rendre un template Twig qui affiche toutes
	 * les quantités cumulées pour les groupes alimentaires parents fournis.
	 *
	 * @param array $listFgp Tableau associatif des quantités par groupe alimentaire parent
	 * @param FoodGroupParentRepository $foodGroupParentRepository Repository des groupes alimentaires parents
	 *
	 * @return Response Rendu du template affichant les FGP avec leurs quantités
	 */
	#[Route('/fgpQuantitiesTotal', name: 'app_meal_fgp_quantities_total', methods: ['GET'])]
	public function fgpQuantitiesTotal(
		array $listFgp,
		FoodGroupParentRepository $foodGroupParentRepository
	): Response {
		// Rendu du template Twig avec :
		// - 'listFgp' : les quantités par groupe alimentaire parent fournies
		// - 'foodGroupParents' : liste des groupes alimentaires principaux (isPrincipal = 1)
		return $this->render('meals/partials/_list_fgp.html.twig', [
			'listFgp' => $listFgp,
			'foodGroupParents' => $foodGroupParentRepository->findByIsPrincipal(1),
		]);
	}

	/**
	 * Calcule et affiche l'énergie d'un plat ou d'un aliment donné.
	 *
	 * Cette méthode sélectionne le bon repository (Dish ou Food) en fonction du type
	 * fourni dans $item, récupère l'entité correspondante, puis utilise AlertFeature
	 * pour extraire l'énergie correspondant à la quantité et l'unité spécifiées.
	 *
	 * @param array $item Tableau contenant les informations sur l'élément :
	 *                    - 'type' : 'Dish' ou 'Food'
	 *                    - 'id' : identifiant de l'élément
	 *                    - 'quantity' : quantité choisie
	 *                    - 'unitMeasureAlias' : alias de l'unité de mesure
	 * @param AlertFeature $alertFeature Service pour calculer l'énergie et les nutriments
	 * @param DishRepository $dishRepository Repository des plats
	 * @param FoodRepository $foodRepository Repository des aliments
	 *
	 * @return Response Rendu du template Twig affichant l'énergie calculée
	 */
	#[Route('/energy-for-dish-or-food', name: 'app_energy_dish_or_food', methods: ['GET'])]
	public function energyForDishOrFood(
		array $item,
		AlertFeature $alertFeature,
		DishRepository $dishRepository,
		FoodRepository $foodRepository
	): Response {
		// Choix du repository en fonction du type (Dish ou Food)
		$repo = 'Dish' === $item['type'] ? $dishRepository : $foodRepository;

		// Rendu du template Twig avec l'énergie calculée pour l'élément
		return $this->render('meals/partials/_energy_dish_or_food.html.twig', [
			'energy' => $alertFeature->extractDataFromDishOrFoodSelected(
				'energy',
				$repo->findOneById((int)$item['id']),   // Récupère l'entité correspondante
				(float)$item['quantity'],               // Quantité choisie
				(string)$item['unitMeasureAlias']       // Unité de mesure
			)
		]);
	}

	/**
	 * Affiche l'énergie totale consommée pour la journée et le reste à consommer.
	 *
	 * Cette méthode récupère la session pour obtenir l'énergie totale des repas de la journée,
	 * calcule l'énergie restante par rapport aux besoins énergétiques de l'utilisateur,
	 * et renvoie un rendu Twig avec ces informations.
	 *
	 * @param Request $request Objet Request pour accéder à la session et aux paramètres GET
	 * @param AlertFeature $alertFeature Service pour calculer et vérifier l'équilibre énergétique
	 * @param string|null $page Numéro de page optionnel pour l'affichage (pagination)
	 *
	 * @return Response Rendu du template Twig affichant l'énergie totale et l'énergie restante
	 */
	#[Route('/show-total-energy/{page?}', name: 'meal_day_show_total_energy', methods: ['GET'], requirements: ['page' => '\d+'])]
	public function showTotalEnergy(Request $request, AlertFeature $alertFeature, ?string $page): Response
	{
		/** @var \App\Entity\User|null $user */
		$user = $this->getUser(); // L'utilisateur courant

		$session = $request->getSession();

		// Énergie totale consommée aujourd'hui, arrondie à l'entier inférieur
		$mealDayEnergy = floor($session->get('_meal_day_energy'));

		// Énergie restante par rapport aux besoins de l'utilisateur
		$remainingMealDayEnergy = abs($user->getEnergy() - $mealDayEnergy);

		// Rendu du template Twig
		return $this->render('meals/day/show_total_energy.html.twig', [
			'mealDayEnergy' => $mealDayEnergy, // Énergie consommée
			'remainingMealDayEnergy' => $remainingMealDayEnergy, // Énergie restante
			'alert' => $alertFeature->isWellBalanced($mealDayEnergy, $user->getEnergy()), // Vérifie si l'énergie est équilibrée
			'page' => $page, // Page actuelle pour l'affichage
			'showPopover' => $request->query->get('show_popover', true), // Contrôle l'affichage du popover
			'bgColor' => $request->query->get('bg', 'bg-white'), // Couleur de fond optionnelle
			'sizeIcon' => 7, // Taille de l'icône dans le template
		]);
	}

	/**
	 * Affiche l'énergie totale consommée pour la journée sur un élément de liste.
	 *
	 * Cette méthode calcule l'énergie totale des repas stockés en session et la compare
	 * à l'énergie quotidienne recommandée de l'utilisateur connecté. Elle renvoie ensuite
	 * un rendu HTML qui peut être utilisé dans une liste de repas.
	 *
	 * @param Request $request L'objet Request HTTP, utilisé pour accéder aux paramètres GET et à la session
	 * @param AlertFeature $alertFeature Service pour calculer les alertes nutritionnelles et vérifier l'équilibre énergétique
	 * @param string|null $page Optionnel, numéro de page pour le rendu dans un contexte paginé
	 *
	 * @return Response Retourne le rendu HTML de l'énergie totale et de l'état de l'équilibre énergétique
	 */
	#[Route('/show-total-energy-on-list-item/{page?}', name: 'meal_day_show_total_energy_on_list_item', methods: ['GET'], requirements: ['page' => '\d+'])]
	public function showTotalEnergyOnListItem(Request $request, AlertFeature $alertFeature, ?string $page): Response
	{
		/** @var App\Entity\User|null $user Récupère l'utilisateur connecté */
		$user = $this->getUser();

		$session = $request->getSession();

		// Récupère l'énergie totale des repas de la journée stockée en session et arrondie à l'entier inférieur
		$mealDayEnergy = floor($session->get('_meal_day_energy'));

		// Calcul de l'énergie restante par rapport à l'objectif quotidien de l'utilisateur
		$remainingMealDayEnergy = abs($user->getEnergy() - $mealDayEnergy);

		return $this->render('meals/day/show_total_energy.html.twig', [
			'mealDayEnergy' => $mealDayEnergy,                       // Energie déjà consommée
			'remainingMealDayEnergy' => $remainingMealDayEnergy,     // Energie restante pour atteindre l'objectif
			'alert' => $alertFeature->isWellBalanced($mealDayEnergy, $user->getEnergy()), // Alertes sur l'équilibre énergétique
			'page' => $page,                                         // Page actuelle (pour les contextes paginés)
			'showPopover' => $request->query->get('show_popover', false), // Affiche ou non un popover d'information
			'bgColor' => $request->query->get('bgColor', 'bg-white'),      // Couleur de fond du composant
			'paddingX' => 0,                                         // Padding horizontal pour l'affichage
		]);
	}

	/**
	 * Calcule l'énergie totale estimée si un nouvel élément (plat ou aliment) 
	 * est ajouté ou remplace un élément existant dans le repas du jour.
	 *
	 * Cette méthode permet de simuler l'impact énergétique d'une sélection avant
	 * de la valider, et renvoie le rendu HTML mis à jour avec l'énergie totale et
	 * les alertes d'équilibre nutritionnel.
	 *
	 * @param Request $request L'objet Request HTTP pour accéder aux paramètres GET
	 * @param SessionInterface $session La session pour récupérer les repas du jour
	 * @param DishRepository $dishRepository Repository pour accéder aux plats
	 * @param FoodRepository $foodRepository Repository pour accéder aux aliments
	 * @param string $typeAddItem Type de l'élément ajouté ('dish' ou 'food')
	 * @param EnergyHandler $energyHandler Service pour calculer l'énergie des éléments
	 * @param AlertFeature $alertFeature Service pour vérifier l'équilibre énergétique
	 *
	 * @return Response Rend le template avec l'énergie totale et les alertes
	 */
	#[Route('/total-with-new-selection/{typeAddItem}', name: 'meal_day_energy_estimate_with_new_selection', methods: ['GET'], requirements: ['typeAddItem' => 'dish|food'], options: ['expose' => true])]
	public function calculateEnergyWithNewSelection(
		Request $request,
		SessionInterface $session,
		DishRepository $dishRepository,
		FoodRepository $foodRepository,
		string $typeAddItem,
		EnergyHandler $energyHandler,
		AlertFeature $alertFeature
	): Response {

		/** @var App\Entity\User|null $user Récupère l'utilisateur connecté */
		$user = $this->getUser();

		// Récupération des indices du repas et du plat/aliment ciblé depuis les paramètres GET
		$rankMeal = $request->query->get('rankMeal'); // Indice du repas du jour dans la session (_meal_day_X)
		$rankDish = $request->query->get('rankDish'); // Indice du plat ou aliment dans le tableau 'dishAndFoods' du repas

		// Calcul de l'énergie du nouvel élément ajouté, selon qu'il s'agit d'un plat ou d'un aliment
		if ('dish' === $typeAddItem) {
			$nPortion = $request->query->get('nPortion'); // Nombre de portions sélectionnées
			$dish = $dishRepository->findOneById($request->query->get('id')); // Récupération du plat depuis le repository
			// Calcul de l'énergie pour ce plat selon le nombre de portions
			$energyNewItem = $energyHandler->getEnergyForDishOrFoodSelected($dish, 'Dish', $nPortion);
		} elseif ('food' === $typeAddItem) {
			$quantity = $request->query->get('quantity'); // Quantité de l'aliment sélectionnée
			$unitMeasure = $request->query->get('unitMeasure'); // Unité de mesure (g, ml, etc.)
			$food = $foodRepository->findOneById($request->query->get('id')); // Récupération de l'aliment depuis le repository
			// Calcul de l'énergie pour cet aliment selon la quantité et l'unité choisies
			$energyNewItem = $energyHandler->getEnergyForDishOrFoodSelected($food, 'Food', $quantity, $unitMeasure);
		}

		// Vérifie si le repas existe dans la session et si un élément est déjà présent à ce rang
		if (
			$session->has('_meal_day_' . $rankMeal)
			&& array_key_exists((int)$rankDish, $session->get('_meal_day_' . $rankMeal)['dishAndFoods'])
		) {

			// Récupération de l'élément existant à remplacer
			$itemToReplace = $session->get('_meal_day_' . $rankMeal)['dishAndFoods'][(int)$rankDish];

			// Calcul de l'énergie de l'élément existant
			if ('Food' === $itemToReplace["type"]) {
				$food = $foodRepository->findOneById((int)$itemToReplace["id"]);
				$energyItemToReplace = $energyHandler->getEnergyForDishOrFoodSelected(
					$food,
					'Food',
					$itemToReplace["quantity"],
					$itemToReplace["unitMeasure"]
				);
			} else {
				$dish = $dishRepository->findOneById((int)$itemToReplace["id"]);
				$energyItemToReplace = $energyHandler->getEnergyForDishOrFoodSelected(
					$dish,
					'Dish',
					$itemToReplace["quantity"]
				);
			}

			// Calcul de la nouvelle énergie totale du repas en remplaçant l'ancien élément par le nouveau
			$newEnergyTotal = (int)$session->get('_meal_day_energy') + $energyNewItem - $energyItemToReplace;
		} else {
			// Aucun élément existant à ce rang : on ajoute simplement la nouvelle énergie à l'énergie totale du repas
			$newEnergyTotal = (int)$session->get('_meal_day_energy') + $energyNewItem;
		}

		// Calcul de l'énergie restante pour atteindre l'objectif énergétique quotidien de l'utilisateur
		$remainingMealDayEnergy = abs($user->getEnergy() - $newEnergyTotal);

		// Rend le template affichant l'énergie totale estimée et les alertes d'équilibre
		return $this->render('meals/day/show_total_energy.html.twig', [
			'mealDayEnergy' => round($newEnergyTotal), // Energie totale du repas du jour après ajout/remplacement
			'remainingMealDayEnergy' => $remainingMealDayEnergy, // Energie restante pour atteindre l'objectif de l'utilisateur
			'alert' => $alertFeature->isWellBalanced($newEnergyTotal, $user->getEnergy()), // Indique si l'énergie est bien équilibrée
			'showPopover' => $request->query->get('show_popover', false), // Contrôle l'affichage d'une info-bulle
			'bgColor' => $request->query->get('bg', 'bg-white'), // Couleur de fond optionnelle
			'paddingX' => 0, // Pas de padding horizontal
		]);
	}

	/**
	 * Pré-sélectionne un plat ou un aliment pour le repas du jour.
	 *
	 * Cette méthode ajoute un élément à la session '_meal_day_preselected_items' et permet
	 * d'afficher immédiatement cet élément via AJAX ou dans la sidebar.
	 *
	 * @param Request $request Objet contenant les paramètres GET (rankMeal, rankDish, quantity, unitMeasure, ajax)
	 * @param UnitMeasureRepository $unitMeasureRepository Pour récupérer les unités de mesure
	 * @param AlertFeature $alertFeature Pour gérer les alertes éventuelles sur l'élément
	 * @param int|null $id ID du plat ou aliment à pré-sélectionner
	 * @param string|null $type Type de l'élément ('Dish' ou 'Food')
	 * 
	 * @return Response
	 */
	#[Route('/sidebar-preselect-item/{id?}/{type?}', name: 'meal_sidebar_preselect_item', requirements: ['id' => '\d+', 'type' => 'Dish|Food'])]
	public function preSelectItem(Request $request, UnitMeasureRepository $unitMeasureRepository, AlertFeature $alertFeature, ?int $id, ?string $type)
	{
		// Récupère la session utilisateur afin de stocker ou modifier les éléments pré-sélectionnés
		$session = $request->getSession();

		// Vérifie si une unité de mesure spécifique a été passée dans la requête GET
		if ($request->query->has('unitMeasure')) {
			// Récupère l'objet UnitMeasure correspondant à l'ID fourni
			$unitMeasure = $unitMeasureRepository->findOneById((int)$request->query->get('unitMeasure'));
			// Récupère l'alias (abréviation) de cette unité, par ex. 'g' pour grammes
			$unitMeasureAlias = $unitMeasure->getAlias();
		} else {
			// Par défaut, on utilise l'unité 'g' (grammes)
			$unitMeasure = $unitMeasureRepository->findOneByAlias('g')->getId();
			$unitMeasureAlias = 'g';
		}

		// Prépare le tableau représentant l'élément pré-sélectionné
		$newDishOrFood = [
			// Rang du repas dans la journée (0 = premier repas)
			'rankMeal' => $request->query->get('rankMeal'),
			// Rang du plat ou aliment dans le repas
			'rankDish' => $request->query->get('rankDish'),
			// ID de l'aliment ou du plat
			'id' => $id,
			// Type : 'Dish' ou 'Food'
			'type' => $type,
			// Quantité sélectionnée pour cet élément
			'quantity' => $request->query->get('quantity'),
			// ID de l'unité de mesure sélectionnée
			'unitMeasure' => $request->query->get('unitMeasure'),
			// Alias de l'unité de mesure ou 'portion' si c'est un plat
			'unitMeasureAlias' => 'Dish' === $type ? 'portion' : $unitMeasureAlias,
		];

		// Récupère la liste actuelle des éléments pré-sélectionnés depuis la session
		if ($session->has('_meal_day_preselected_items')) {
			$preSelectedItems = $session->get('_meal_day_preselected_items');
		} else {
			// Si aucun élément pré-sélectionné n'existe encore, initialise un tableau vide
			$preSelectedItems = [];
		}

		// Ajoute le nouvel élément à la liste des éléments pré-sélectionnés
		$preSelectedItems[] = $newDishOrFood;

		// Met à jour la session avec la liste complète incluant le nouvel élément
		$session->set('_meal_day_preselected_items', $preSelectedItems);

		// Si la requête provient d'un appel AJAX
		if ($request->query->get('ajax')) {
			// Retourne uniquement le template de l'élément pré-sélectionné pour affichage immédiat
			return $this->render('meals/day/_item_preselected.html.twig', [
				'item' => $newDishOrFood,
				'rankMeal' => $request->query->get('rankMeal'),
				'rankDish' => $request->query->get('rankDish'),
				'alertColor' => $request->query->get('alertColor'),
				'alertText' => $request->query->get('alertText'),
			]);
		}

		// Sinon, si ce n'est pas une requête AJAX, retourne le template complet de la sidebar
		return $this->render('meals/day/_sidebar_list_item_preselected.html.twig', [
			'rankMeal' => $request->query->get('rankMeal'),
			'rankDish' => $request->query->get('rankDish'),
		]);
	}

	/**
	 * Supprime un élément pré-sélectionné spécifique d'un repas.
	 *
	 * Met à jour les rangs des éléments restants et renvoie la liste mise à jour.
	 *
	 * @param Request $request Objet contenant les paramètres GET (rankMeal, rankDish)
	 * @param int|null $id ID du plat ou aliment
	 * @param string|null $type Type de l'élément ('Dish' ou 'Food')
	 * 
	 * @return Response
	 */
	#[Route('/sidebar-remove-preselect-item/{id?}/{type?}', name: 'meal_sidebar_remove_preselect_item', methods: ['GET'], requirements: ['id' => '\d+', 'type' => 'Dish|Food'])]
	public function removePreSelectItem(Request $request, ?int $id, ?string $type)
	{
		// Récupère la session utilisateur pour accéder aux éléments pré-sélectionnés
		$session = $request->getSession();

		// Récupère la liste actuelle des éléments pré-sélectionnés
		$preSelectedItems = $session->get('_meal_day_preselected_items');

		// Parcourt tous les éléments pré-sélectionnés pour trouver celui à supprimer
		foreach ($preSelectedItems as $index => $item) {

			// Vérifie si l'élément correspond au rang du repas et au rang du plat/aliment fourni dans la requête
			if (
				$request->query->get('rankDish') == $item['rankDish']
				&& $request->query->get('rankMeal') == $item['rankMeal']
			) {

				// Décalage des rangs des éléments suivants pour éviter les "trous" dans la liste
				$i = $index + 1;
				while (isset($preSelectedItems[$i])) {
					// On décrémente le rangDish des éléments suivants pour maintenir l'ordre
					$preSelectedItems[$i]['rankDish']--;
					$i++;
				}

				// Supprime l'élément correspondant au rang fourni
				unset($preSelectedItems[$index]);

				// On sort de la boucle car l'élément à supprimer a été trouvé
				break;
			}
		}

		// Réindexe le tableau pour avoir des clés consécutives (0, 1, 2, ...)
		$preSelectedItems = array_values($preSelectedItems);

		// Met à jour la session avec la nouvelle liste d'éléments pré-sélectionnés
		$session->set('_meal_day_preselected_items', $preSelectedItems);

		// Retourne le rendu du template contenant la liste mise à jour des éléments pré-sélectionnés
		return $this->render('meals/day/_list_item_preselected.html.twig', [
			'items' => $preSelectedItems,
		]);
	}

	/**
	 * Supprime tous les éléments pré-sélectionnés pour les repas du jour.
	 *
	 * Vide simplement la session '_meal_day_preselected_items'.
	 *
	 * @param Request $request
	 * 
	 * @return Response
	 */
	#[Route('/sidebar-remove-preselect-items', name: 'meal_sidebar_remove_preselect_items', methods: ['GET'])]
	public function removePreSelectItems(Request $request)
	{
		$session = $request->getSession();

		$session->remove('_meal_day_preselected_items');

		return new Response('OK', Response::HTTP_NO_CONTENT);
	}

	/**
	 * Récupère l'alias d'une unité de mesure.
	 *
	 * @param Request $request
	 * @param UnitMeasureRepository $unitMeasureRepository
	 * 
	 * @return Response L'alias de l'unité de mesure (ex: "g", "portion", etc.)
	 */
	#[Route('/get-unitmeasure-alias', name: 'meal_get_unitmeasure_alias', methods: ['GET'])]
	public function getUnitMeasureAlias(Request $request, UnitMeasureRepository $unitMeasureRepository)
	{
		$unitMeasure = $unitMeasureRepository->findOneById($request->query->get('id'));
		return new Response($unitMeasure->getAlias());
	}

	/**
	 * Affiche un popover d'information énergétique pour le repas du jour.
	 *
	 * Si aucune valeur d'énergie n'est passée, prend l'énergie totale du repas depuis la session.
	 * Affiche un message selon l'équilibre énergétique de l'utilisateur.
	 *
	 * @param Request $request
	 * @param AlertFeature $alertFeature
	 * @param int|null $energy Énergie à afficher (optionnelle)
	 * 
	 * @return Response HTML du popover
	 */
	#[Route('/popover-energy/{energy?}', name: 'meal_popover_energy', methods: ['GET'], requirements: ['energy' => '\d+'])]
	public function popoverEnergy(Request $request, AlertFeature $alertFeature, ?int $energy): Response
	{
		/** @var App\Entity\User|null $user Récupère l'utilisateur connecté */
		$user = $this->getUser();

		// Récupère la session courante de l'utilisateur
		$session = $request->getSession();

		// Détermine l'énergie du repas à afficher
		// Si une valeur spécifique ($energy) est passée en paramètre, on l'utilise
		// Sinon, on prend l'énergie totale du repas stockée en session
		$mealDayEnergy = null === $energy ? $session->get('_meal_day_energy') : $energy;

		// Calcule l'énergie restante pour atteindre l'objectif calorique quotidien de l'utilisateur
		// abs() garantit un résultat positif, round() arrondit à l'entier le plus proche
		$remainingMealDayEnergy = abs(round($user->getEnergy() - $mealDayEnergy));

		// Détermine le niveau d'alerte en fonction de l'équilibre énergétique actuel
		// Retourne un objet LevelAlert indiquant si l'utilisateur est bien équilibré, en déficit ou en excès
		$alert = $alertFeature->isWellBalanced($mealDayEnergy, $user->getEnergy());

		// Prépare le titre et le message à afficher dans le popover selon le code d'alerte
		if (LevelAlert::BALANCE_WELL === $alert->getCode()) {
			// Cas idéal : consommation correcte
			$title = "Super, vous êtes bon !";
			$message = "Votre consommation calorique est bonne";
		} elseif (in_array($alert->getCode(), LevelAlert::LOW_ALERTS, true)) {
			// Cas de déficit énergétique : l'utilisateur doit consommer plus
			$title = "Consommez plus !";
			$message = "Vous devriez consommer environ $remainingMealDayEnergy Kcal supplémentaires";
		} else {
			// Cas de surplus énergétique : l'utilisateur a dépassé l'objectif
			$title = "Consommez moins !";
			$message = "Vous dépassez d'environ $remainingMealDayEnergy Kcal nos recommandations";
		}

		// Rendu du popover HTML avec les informations calculées
		return $this->render('partials/_content_popover_info.html.twig', [
			'title' => $title,                       // Titre du popover
			'message' => $message ?? null,           // Message explicatif
			'alert' => $alert,                       // Objet alert avec le code et les détails
		]);
	}

	/**
	 * Renvoie l'énergie totale d'un repas spécifique.
	 *
	 * @param MealModel $meal
	 * @param MealUtil $mealUtil
	 * 
	 * @return Response L'énergie totale en Kcal
	 */
	#[Route('/energy-meal', name: 'meal_energy', methods: ['GET'])]
	public function getEnergy(MealModel $meal, MealUtil $mealUtil)
	{
		// Calcul de l'énergie totale du repas donné ($meal) en utilisant le service MealUtil
		// round() arrondit la valeur à l'entier le plus proche
		// abs() garantit que l'énergie est toujours positive, même en cas d'erreur
		$totalEnergy = abs(round($mealUtil->getEnergy($meal)));

		// Retourne la valeur sous forme de texte simple avec l'unité "Kcal" dans la réponse HTTP
		return new Response(sprintf("%d Kcal", $totalEnergy));
	}

	/**
	 * Affiche le contenu complet de la session pour debug.
	 *
	 * @param Request $request
	 * 
	 * @return void
	 */
	#[Route('/session', name: 'meal_session', methods: ['GET'])]
	public function getSession(Request $request)
	{
		dd($request->getSession()->all());
	}


	/**
	 * Génère les alertes finales pour les repas du jour.
	 *
	 * Calcule toutes les alertes globales, détermine si la journée est équilibrée et renvoie le rendu du template.
	 *
	 * @param Request $request
	 * @param AlertFeature $alertFeature
	 * 
	 * @return Response HTML avec les alertes finales
	 */
	#[Route('/final-alerts', name: 'meal_final_alerts')]
	public function finalAlerts(Request $request, AlertFeature $alertFeature): Response
	{
		// On calcule tous les niveaux d'alerte pour le repas du jour
		// Cela inclut les alertes liées aux plats et aliments sélectionnés
		$alerts = $alertFeature->computeMealGlobalAlerts();

		// On vérifie si le repas du jour est totalement équilibré sur la base des alertes calculées
		$isFullyBalanced = $alertFeature->isMealFullyBalanced($alerts);

		// On renvoie la vue Twig dédiée aux alertes finales du repas du jour
		// Cette vue affichera :
		//  - 'highest_alert' : le niveau d'alerte le plus critique pour le repas
		//  - 'alerts' : toutes les alertes calculées pour chaque plat ou aliment
		//  - 'isFullyBalanced' : booléen indiquant si le repas est parfaitement équilibré
		return $this->render('meals/day/_final_alerts.html.twig', [
			'highest_alert' => $alertFeature->getHighestAlertLevelMealDay(),
			'alerts' => $alerts,
			'isFullyBalanced' => $isFullyBalanced,
		]);
	}
}
