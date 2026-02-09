<?php

namespace App\Controller\meal;

use App\Entity\Alert\LevelAlert;
use App\Entity\TypeMeal;
use App\Entity\MealModel;
use App\Service\DishUtil;
use App\Service\FoodUtil;
use App\Service\MealUtil;
use App\Service\AlertFeature;
use App\Service\EnergyHandler;
use App\Repository\DishRepository;
use App\Repository\FoodRepository;
use App\Service\QuantityTreatment;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\FoodGroup\FoodGroupParent;
use App\Entity\FoodGroup\FoodGroup;
use App\Repository\UnitMeasureRepository;
use App\Repository\FoodGroupParentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


#[Route('/mes-repas', methods: ['GET'])]
class DefaultController extends AbstractController
{
	#[Route('/repas-du-jour', name: 'meal_day', methods: ['GET'])]
	public function menu(Request $request, EntityManagerInterface $manager, AlertFeature $alertFeature, EnergyHandler $energyHandler, DishRepository $dishRepository, FoodGroupParentRepository $foodGroupParentRepository, FoodRepository $foodRepository, MealUtil $mealUtil)
	{
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
		
		$session = $request->getSession();
			
		// On liste les fgp présents dans les plats/aliments des repas
		$listFgp = [];

		if($session->has('_meal_day_range')) {

				$energyDay = 0;
				// On stocke les énergies augmentées à chaque plat en partant de zéro
				$energies[0][0] = 0;
				
				for($i = 0; $i <= $session->get('_meal_day_range'); $i++)
				{

					$energyMeal = 0;

					$meal = $session->get('_meal_day_' . $i);
					// dump($meal);
					
					if($i === 0) {
						$meal['rankPreviousType'] = null;
					}

					if(null === $previousTypeMeal = $manager->getRepository(TypeMeal::class)->findOneByBackName($session->get('_meal_day_0')['type']))
					{
						// dump()
						$meal = $session->get('_meal_day_0');
						$meal['type'] = null;
						$meal['rankPreviousType'] = null;
						$session->set('_meal_day_0', $meal);
					}
					
					$typeMeal = $manager->getRepository(TypeMeal::class)->findOneByBackName($session->get('_meal_day_' . $i)['type']);

					if($i > 0) {
						$energies[$i][0] = end($energies[$i-1]);
					}

					if(array_key_exists('dishAndFoods', $session->get('_meal_day_' . $i)))
					{
						$dishAndFoods = $session->get('_meal_day_' . $i)['dishAndFoods'];
						$listFgp = array_merge($mealUtil->getListfgp($dishAndFoods), $listFgp);

						foreach($dishAndFoods as $j => $dishOrFood) {
							
							$energyDishOrFood = $energyHandler->getEnergyForDishOrFoodSelected($dishOrFood['id'], $dishOrFood['type'], $dishOrFood['quantity'], $dishOrFood['unitMeasureAlias']);
							$energyMeal += $energyDishOrFood;
							$energyDay += $energyDishOrFood;
							$energies[$i][$j+1] = $energyDay;

						}	
					}

					if ($i > 0) {
						$rankType = null !== $typeMeal ? $typeMeal->getRanking() : null;
						if(null !== $newPreviousTypeMeal = $manager->getRepository(TypeMeal::class)->findOneByBackName($session->get('_meal_day_' . ($i-1))['type'])) {
							$previousTypeMeal = $newPreviousTypeMeal;
						}

						$rankPreviousType = null !== $previousTypeMeal ? $previousTypeMeal->getRanking() : 0;

						if($rankType < $rankPreviousType) {
							$meal['type'] = null;
						}

						$meal['rankPreviousType'] = $rankPreviousType;
					}

					$meal['energy'] = $energyMeal;
					$session->set('_meal_day_' . $i, $meal);

				}

			$session->set('_meal_day_energy_evolution', $energies);
			$session->set('_meal_day_energy', $energyDay);

		}else{

			$session->set('_meal_day_range', 0);
			$meal['type'] = null;
			$meal['rankPreviousType'] = null;
			$meal['dishAndFoods'] = [];
			$session->set('_meal_day_0', $meal);

		}

		if($session->has('rankDish') && null !== $session->get('rankDish'))
			$session->set('rankDish', null);

		if($session->has('_meal_day_dish_alerts') && !empty($session->get('_meal_day_dish_alerts'))) {
			$session->set('_meal_day_dish_alerts', []);
		}

		$alertFeature->setAlertOnDishesAndFoodsAlreadySelected();

		$listFgpRemaining = $foodGroupParentRepository->getIdsPrincipal();

		$session->set('_meal_day/_list_fgp', $listFgp);
		$session->set('_meal_day/_list_fgp_remaining_absent', array_diff($listFgpRemaining, $listFgp));

		if($request->query->get('ajax')) {
			return $this->render('meals/day/_meals_day.html.twig', [
					      'foodGroups' => $manager->getRepository(FoodGroup::class)->findAll([], ['ranking' => 'ASC']),
								'page' => 0,
						   'typeMeals' => $manager->getRepository(TypeMeal::class)->findBy([], ['ranking' => 'ASC']),
							 'listFgp' => $listFgp,
			  'listFgpRemainingAbsent' => array_diff($listFgpRemaining, $listFgp),
				]
			);
		}

		return $this->render('meals/day/index.html.twig', [
					  'foodGroups' => $manager->getRepository(FoodGroup::class)->findAll([], ['ranking' => 'ASC']),
							'page' => 0,
					   'typeMeals' => $manager->getRepository(TypeMeal::class)->findBy([], ['ranking' => 'ASC']),
					     'listFgp' => $listFgp,
		  'listFgpRemainingAbsent' => array_diff($listFgpRemaining, $listFgp),
			]
		);
	}

	#[Route('/crud/add/{idMealModel?}', name:'meal_day_add', requirements: ['idMealModel' => '\d+'], options:['expose' => true])]
	public function add(Request $request, EntityManagerInterface $manager, AlertFeature $alertFeature, ?int $idMealModel = null)
	{
		$session = $request->getSession();

		$rangeMeal = $session->has('_meal_day_range') ? $session->get('_meal_day_range') : 0;

		if(!empty($session->get('_meal_day_' . $rangeMeal)['dishAndFoods'])){
			$rangeMeal += 1;
		}

		$session->set('_meal_day_range', $rangeMeal);
		if($request->getSession()->get('_meal_day_date') ==  'model') {
			$meal['type'] = 'meal.type.breakfast';
		}else{
			$meal['type'] = null;
		}
		$meal['dishAndFoods'] = [];
		$session->set('_meal_day_' . $rangeMeal, $meal);

		if(null !== $idMealModel) {

			$modelMeal = $manager->getRepository(MealModel::class)->findOneById($idMealModel);
			$meal['id'] = $modelMeal->getId();
			$meal['name'] = $modelMeal->getName();
			$meal['type'] = $modelMeal->getType() !== null ? $modelMeal->getType()->getBackName() : null;
			$meal['dishAndFoods'] = $modelMeal->getDishAndFoods();

			$session->set('_meal_day_' . $rangeMeal, $meal);

			$alertFeature->setEnergyAndNutrientsDataSession();

			if ($request->isXmlHttpRequest()) {

				return $this->json([
					'success' => true,
					'redirectUrl' => $this->generateUrl('meal_day')
				]);

			}

		}

		return $this->redirectToRoute('meal_day');
	}

	#[Route('/remove', name:'meal_day_remove', methods: ['GET'], options: ['expose' => true])]
	public function remove(Request $request, EntityManagerInterface $manager, MealUtil $mealUtil)
	{
		$session = $request->getSession();
		
		if($request->query->has('rankMeal')) {

		    $mealUtil->removeMealSession($request->query->get('rankMeal'));

		} else {
		
			return $this->redirectToRoute('menu_week_remove_meals', [
				'date' => $request->getSession()->get('_meal_day_date'),
			]);
		}

		return $this->redirectToRoute('meal_day');
	}

	#[Route('/plat/add', name:'meal_day_add_dish_or_food', methods: ['GET'])]
	public function addDishOrFood(Request $request, AlertFeature $alertFeature, UnitMeasureRepository $unitMeasureRepository, DishRepository $dishRepository, FoodRepository $foodRepository)
	{
		$session = $request->getSession();

		$id = $request->query->get('id');
		$type = $request->query->get('type');

		if($request->query->has('unitMeasure')) {
			$unitMeasure = $unitMeasureRepository->findOneById((int)$request->query->get('unitMeasure'));
			$unitMeasureAlias = $unitMeasure->getAlias();
		}else{
			$unitMeasure = $unitMeasureRepository->findOneByAlias('g')->getId();
			$unitMeasureAlias = 'g';
		}

		$repo = ('Dish' === $type || 'dish' === $type ) ? $dishRepository : $foodRepository;
	
		$energyElement = $alertFeature->extractDataFromDishOrFoodSelected('energy', $repo->findOneById((int)$id), (float)$request->query->get('quantity'), $unitMeasureAlias);

		$newDishOrFood = [
			'id' => $id, 
			'type' => $type, 
			'quantity' => $request->query->get('quantity'), 
			'unitMeasure' => $request->query->get('unitMeasure'),
			'unitMeasureAlias' => $unitMeasureAlias,
		];

		$rankMeal = $request->query->has('rankMeal') ? $request->query->get('rankMeal') : (int)$session->get('rankMeal');
		$meal = $session->has('_meal_day_' . $rankMeal) ? $session->get('_meal_day_'  . $rankMeal) : [];
		if($request->query->has('rankDish') && "" != $request->query->get('rankDish') && "all" != $request->query->get('rankDish'))
		{
			$rankDish = (int)$request->query->get('rankDish');
			$meal['dishAndFoods'][$rankDish] = $newDishOrFood;
		}else{
			$rankDish = 0;
			$meal['dishAndFoods'][] = $newDishOrFood;
		}

		$session->set('_meal_day_' . $rankMeal, $meal);

		// dd($session->get('_meal_day_alerts/_dishes_selected'));

		// ON MET A JOUR LA VARIABLE DE SESSION STOCKANT L'ENERGIE PLAT APRES PLAT

		$alertFeature->setEnergyAndNutrientsDataSession();
		
		$alertFeature->setAlertOnDishesAndFoodsAlreadySelected();

		if($request->query->get('ajax')) {
			return new Response('OK', Response::HTTP_NO_CONTENT);
		}

		return $this->redirectToRoute('meal_day');
	}
	
	#[Route('/plat/remove', name:'meal_day_remove_dish', methods: ['GET'], options:['expose' => true])]
	public function removeDish(Request $request, MealUtil $mealUtil, AlertFeature $alertFeature)
	{
		$rankMeal = $request->query->get('rankMeal');
		$rankDish = $request->query->get('rankDish');

		$session = $request->getSession();
		$meal = $session->get('_meal_day_'  . $rankMeal);

		if($request->query->get('fromRankDishToTheEnd')) {
			$meal['dishAndFoods'] = array_slice($meal['dishAndFoods'], 0, $rankDish);
		} else{
			unset($meal['dishAndFoods'][$rankDish]);
			$meal['dishAndFoods'] = array_values($meal['dishAndFoods']);
		}
		$session->set('_meal_day_'  . $rankMeal, $meal);

		$alertFeature->setEnergyAndNutrientsDataSession();

		$alertFeature->setAlertOnDishesAndFoodsAlreadySelected();

		if($request->query->get('ajax')) {
			return new Response('OK', Response::HTTP_NO_CONTENT);
		}

		return $this->redirectToRoute('meal_day');
	}

	#[Route('/plat/remove-selection/{rankMeal}', name:'meal_day_remove_dish_selection', methods: ['GET'], requirements: ['rankMeal' => '\d+'])]
	public function removeDishSelection(Request $request, AlertFeature $alertFeature, int $rankMeal)
	{
		$rankDishes = $request->query->get('rankDishes');
		
		$session = $request->getSession();
	
		$meal = $session->get('_meal_day_'  . $rankMeal);
		
		$rankDishes = explode(',', $rankDishes);
		$dishAndFoods = $meal['dishAndFoods'];

		foreach($rankDishes as $rankDish) {
			unset($dishAndFoods[(int)$rankDish]);
		}
		$meal['dishAndFoods'] = array_values($dishAndFoods);
		$session->set('_meal_day_'  . $rankMeal, $meal);
		$alertFeature->setEnergyAndNutrientsDataSession();
		$alertFeature->setAlertOnDishesAndFoodsAlreadySelected();

		if($request->query->get('ajax')) {
			return new Response('OK', Response::HTTP_NO_CONTENT);
		}

		return $this->redirectToRoute('meal_day');
	}

	#[Route('/remove-selection', name:'meal_day_remove_selection', methods: ['GET'])]
	public function removeMealSelection(Request $request, EntityManagerInterface $manager, MealUtil $mealUtil, AlertFeature $alertFeature)
	{
		$session = $request->getSession();

		if($request->query->has('rankMeals')) {

			$rankMeals = explode(',', $request->query->get('rankMeals'));

			foreach($rankMeals as $rankMeal) {
				$mealUtil->removeMealSession($rankMeal);
			}
			$alertFeature->setEnergyAndNutrientsDataSession();
			$alertFeature->setAlertOnDishesAndFoodsAlreadySelected();

		} else {

			return $this->redirectToRoute('menu_week_remove_meals', [
				'date' => $request->getSession()->get('_meal_day_date'),
			]);

		}

		if($request->query->get('ajax')) {
			return new Response('OK', Response::HTTP_NO_CONTENT);
		}

		return $this->redirectToRoute('meal_day');
	}

	#[Route('/plat/update-type', name: 'meal_day_update_type_meal', methods: ['GET'])]
	public function updateTypeMeal(Request $request)
	{
		$rankMeal = $request->query->get('rankMeal');
		$type = $request->query->get('type');

		$session = $request->getSession();
		$meal = $session->has('_meal_day_'  . $rankMeal) ? $session->get('_meal_day_'  . $rankMeal) : [];
		$meal['type'] = $type;
		$session->set('_meal_day_' . $rankMeal, $meal);

		for($i = $rankMeal + 1; $i <= $session->get('_meal_day_range'); $i++)
		{
			$meal = $session->get('_meal_day_'  . $i);
			$meal['type'] = null;
			$session->set('_meal_day_'  . $i, $meal);
		}

		if($request->query->get('ajax')) {
			return new Response('OK', Response::HTTP_NO_CONTENT);
		}

		return $this->redirectToRoute('meal_day');
	}

	#[Route('/list/list-ajax', name: 'meal_day_list_ajax', methods: ['GET'], options: ['expose' => true])]
	public function listAjax(Request $request, EntityManagerInterface $manager, QuantityTreatment $quantityTreatment, AlertFeature $alertFeature, DishUtil $dishUtil, FoodUtil $foodUtil, DishRepository $dishRepository, FoodRepository $foodRepository, TokenStorageInterface $tokenStorage)
	{
		$user = $this->getUser();
		
		$fglist = $request->query->has('fg') ? $request->query->all()['fg'] : [];
		if(empty($fglist) && $request->query->has('rankMeal')) {
			return $this->render("meals/day/list-ajax.html.twig", array(
				"results" => null,
				"lastResults" => true
				)
			);
		}

		$keyword = $request->query->has('q') ? $request->query->get('q') : null;
		$freeLactose = !empty($request->query->get('freeLactose')) ? $request->query->get('freeLactose') : false;
		$freeGluten = !empty($request->query->get('freeGluten')) ? $request->query->get('freeGluten') : false;

		$limit = 12;
		
		$offset = $request->query->get('page') * $limit;
		$dishes = $foods = [];

		if($request->query->has('rankMeal') && null !== $request->query->get('rankMeal')) {
			// On cherche les plats/aliments pour les ajouter à un repas, donc on exclu les interdits (régimes, goûts personnels etc)
			// On vient de la page de saisie des repas
			// $dishes = $dishUtil->myFindByKeywordAndFGExcludeForbidden($keyword, $fglist, 'ASC', $offset, $limit);
			// $foods = $foodUtil->myFindByKeywordAndFGExcludeForbidden($keyword, $fglist, 'ASC', $offset, $limit);
			if($request->query->has('typeItem') && !empty($request->query->get('typeItem'))) {
				if(in_array('dish', explode(',', $request->query->get('typeItem')))) {
					$dishes = $dishUtil->myFindByKeywordAndFGAndTypeAndLactoseAndGlutenExcludeForbidden($keyword, $fglist, $freeLactose, $freeGluten);
				}
				if(in_array('food', explode(',', $request->query->get('typeItem')))) {
					$foods = $foodUtil->myFindByKeywordAndFGAndLactoseAndGlutenExcludeForbidden($keyword, $fglist, $freeLactose, $freeGluten);
				}
			}else{
				$dishes = $dishUtil->myFindByKeywordAndFGAndTypeAndLactoseAndGlutenExcludeForbidden($keyword, $fglist, $freeLactose, $freeGluten);
				$foods = $foodUtil->myFindByKeywordAndFGAndLactoseAndGlutenExcludeForbidden($keyword, $fglist, $freeLactose, $freeGluten);
			}
		}else{
			// On affiche tous les résultats sans discriminations
			// $dishes = $dishRepository->myFindByKeywordAndFG($keyword, $fglist, 'ASC', $offset, $limit);
			// $foods = $foodRepository->myFindByKeywordAndFG($keyword, $fglist, 'ASC', $offset, $limit);
			$dishes = $dishRepository->myFindByKeywordAndFG($keyword, $fglist, 'ASC');
			$foods = $foodRepository->myFindByKeywordAndFG($keyword, $fglist, 'ASC');
		}

		$allResults = array_merge($dishes, $foods);
		usort($allResults, function($element1, $element2) {
			if($element1->getName() <= $element2->getName()) {
				return -1;
			}else{
				return 1;
			}
		});

		$lastResults = false;

		if(!empty($allResults)) {
			$results = array_slice($allResults, $offset, $limit);
			if(count($results) < 10) {
				$lastResults = true;
			}
			if(10 === count($results)) {
				$lastResult = array_pop($results);
				$lastAllResult = array_pop($allResults);
				if($lastResult->getId() == $lastAllResult->getId()) {
					$lastResults = true;
				}
			}
		}else{
			$results = [];
			$lastResults = true;
		}

		if($request->query->has('rankMeal')) {
			
			$rankMeal = $request->query->get('rankMeal');

			if("none" !== $request->query->get('updateDish')) {
				$rankDish = $request->query->get('updateDish');
				$update = true;
			}else{
				$rankDish = count($request->getSession()->get('_meal_day_' . $rankMeal)['dishAndFoods']);
				$update = false;
			}
			
			$alertFeature->setAlertOnDishesAndFoodsAboutTobeSelected($request->query->get('rankMeal'), $rankDish);

			return $this->render("meals/day/list-ajax.html.twig", array(
							"results" => $results,
							"keyword" => $keyword,
						   "rankMeal" => $request->query->get('rankMeal'),
							 "rankDish" => $rankDish,
							 "update" => $update,
							"lastResults" => $lastResults,
				)
			);
		}

		return $this->render("navigation/_searchResult.html.twig", [
			"results" => $results,
			"keyword" => $keyword,
		]);

	}

	#[Route('/reorder-list-ajax', name: 'meal_day_reorder_list_ajax', methods: ['POST'], options: ['expose' => true])]
	public function reorderListAjax(Request $request, EntityManagerInterface $manager)
	{
		$session = $request->getSession();

		$meal = $session->get('_meal_day_' . $request->query->get('rankMeal'));
		$list = $meal['dishAndFoods'];

		foreach($request->query->get('orderlist') as $num)
		{
			$listreorder[] = $list[$num];
		}

		$meal['dishAndFoods'] = $listreorder;
		$session->set('_meal_day_' . $request->query->get('rankMeal'), $meal);

		return new Response('OK');
	}


	 #[Route('/listfgp/{rankMeal}', name: 'meal_day_list_fgp', methods: ['GET'], requirements: ['rankMeal' => '\d+'], options: ['expose' => true])]
	public function fgpCheckedInMeal(Request $request, MealUtil $mealUtil, EntityManagerInterface $manager, $rankMeal)
	{
		$session = $request->getSession();
		$dishAndFoods = array_key_exists('dishAndFoods', $session->get('_meal_day_' . $rankMeal)) ? $session->get('_meal_day_' . $rankMeal)['dishAndFoods'] : [];
			 ;

		return $this->render("meals/day/meal/fgp-checked-in-meal.html.twig", 
			[
				"foodGroupParents" => $manager->getRepository(FoodGroupParent::class)->findAll(),
					     "listfgp" => $mealUtil->getListfgp($dishAndFoods)
			]
		);
	}

	#[Route('/session/alerts', name: 'meal_day_session_alerts', methods: ['GET'])]
	public function sessionAlert(Request $request)
	{
		dump($request->getSession()->get('_meal_day_alerts/_dishes_not_selected'));
		dump($request->getSession()->get('_meal_day_alerts/_foods_not_selected'));

		exit;
	}


	 #[Route('/add-meal-from-list-model/{idModelMeal}', name: 'add_meal_from_list_model', methods: ['GET'], requirements: ['idModelMeal' => '\d+'])]
	public function addMealFromlistModelMeal(EntityManagerInterface $manager, Request $request, $idModelMeal)
	{
		$session = $request->getSession();
		$modelMeal = $manager->getRepository(MealModel::class)->findOneById($idModelMeal);

		$rankMeal = $session->get('_meal_day_range') + 1;
		$session->set('_meal_day_range', $rankMeal);

		$meal['name'] = $modelMeal->getName();
		$meal['type'] = $modelMeal->getType()->getBackName();
		$meal['dishAndFoods'] = $modelMeal->getDishAndFoods();

		$session->set('_meal_day_' . $rankMeal, $meal);

		return $this->redirectToRoute('meal_day');
	}

	#[Route('/fgpQuantitiesByDishOrFood', name: 'app_meal_fgp_quantities_by_dish_or_food', methods: ['GET'])]
	public function fgpQuantitiesByDishOrFood(DishRepository $dishRepository, FoodRepository $foodRepository, FoodGroupParentRepository $foodGroupParentRepository, DishUtil $dishUtils, array $item)
	{
		if('Dish' == $item['type']) {
			$dishOrFood = $dishRepository->findOneById($item['id']);
			$quantities = null !== $dishOrFood ? $dishUtils->getFoodGroupParentQuantitiesForNPortion($dishOrFood, $item['quantity']) : [];
		}elseif ('Food' == $item['type']){
			$dishOrFood = $foodRepository->findOneById($item['id']);
			$quantities[$dishOrFood->getFoodGroup()->getParent()->getAlias()] = $item['quantity'];
		}

		return $this->render('meals/day/fgp-quantities-by-dish-or-food.html.twig', [
			'quantities' => $quantities,
			'foodGroupParents' => $foodGroupParentRepository->findByIsPrincipal(1),
			'type' => $item['type'],
			'dishOrFood' => $dishOrFood
		]);
	}

	#[Route('/fgpQuantitiesTotal', name: 'app_meal_fgp_quantities_total', methods: ['GET'])]
	public function fgpQuantitiesTotal(array $listFgp, FoodGroupParentRepository $foodGroupParentRepository)
	{
		return $this->render('meals/partials/_list_fgp.html.twig', [
			'listFgp' => $listFgp,
			'foodGroupParents' => $foodGroupParentRepository->findByIsPrincipal(1),
		]);
	}

	#[Route('/energy-for-dish-or-food', name: 'app_energy_dish_or_food', methods: ['GET'])]
    public function energyForDishOrFood(array $item, AlertFeature $alertFeature, DishRepository $dishRepository, FoodRepository $foodRepository)
    {
		$repo = 'Dish' == $item['type'] ? $dishRepository : $foodRepository;
		
        return $this->render('meals/partials/_energy_dish_or_food.html.twig', [
            'energy' => $alertFeature->extractDataFromDishOrFoodSelected('energy', $repo->findOneById((int)$item['id']), (float)$item['quantity'], (string)$item['unitMeasureAlias'])
        ]);
    }

	#[Route('/quantities-consumed', methods: ['GET'])]
	public function quantitiesConsumed(QuantityTreatment $quantityTreatment)
	{		
		return $this->render('meals/day/quantities_consumed.html.twig', [
			'quantities_consumed' => $quantityTreatment->getQuantitiesConsumedInSessionDishes()
		]);
	}

	#[Route('/show-total-energy/{page?}', name: 'meal_day_show_total_energy', methods: ['GET'],  requirements: ['page' => '\d+'])]
	public function showTotalEnergy(Request $request, AlertFeature $alertFeature, ?string $page)
	{
		$session = $request->getSession();

		$mealDayEnergy = floor($session->get('_meal_day_energy'));
		$remainingMealDayEnergy = abs($this->getUser()->getEnergy() - $mealDayEnergy);

		return $this->render('meals/day/show_total_energy.html.twig', [
			         'mealDayEnergy' => $mealDayEnergy,
			'remainingMealDayEnergy' => $remainingMealDayEnergy,
			'alert' => $alertFeature->isWellBalanced($mealDayEnergy, $this->getUser()->getEnergy()),
			'page' => $page,
			'showPopover' => $request->query->get('show_popover', true),
			'bgColor' => $request->query->get('bg', 'bg-white'),
			'sizeIcon' => 7,
		]);
	}

	#[Route('/show-total-energy-on-list-item/{page?}', name: 'meal_day_show_total_energy_on_list_item', methods: ['GET'], requirements: ['page' => '\d+'])]
	public function showTotalEnergyOnListItem(Request $request, AlertFeature $alertFeature, ?string $page)
	{
		$session = $request->getSession();

		$mealDayEnergy = floor($session->get('_meal_day_energy'));
		$remainingMealDayEnergy = abs($this->getUser()->getEnergy() - $mealDayEnergy);

		return $this->render('meals/day/show_total_energy.html.twig', [
			         'mealDayEnergy' => $mealDayEnergy,
			'remainingMealDayEnergy' => $remainingMealDayEnergy,
			'alert' => $alertFeature->isWellBalanced($mealDayEnergy, $this->getUser()->getEnergy()),
			'page' => $page,
			'showPopover' => $request->query->get('show_popover', false),
			'bgColor' => $request->query->get('bgColor', 'bg-white'),
			'paddingX' => 0,
		]);
	}

	#[Route('/total-with-new-selection/{typeAddItem}', name: 'meal_day_energy_estimate_with_new_selection', methods: ['GET'], requirements: ['typeAddItem' => 'dish|food'], options: ['expose' => true])]
    public function calculateEnergyWithNewSelection(Request $request, SessionInterface $session, DishRepository $dishRepository, FoodRepository $foodRepository, string $typeAddItem, EnergyHandler $energyHandler, AlertFeature $alertFeature)
    {
		$rankMeal = $request->query->get('rankMeal');
		$rankDish = $request->query->get('rankDish');

		if('dish' === $typeAddItem) {
			// dump('select dish');
			$nPortion = $request->query->get('nPortion');
			// dump('portion :' . $nPortion);
			$dish = $dishRepository->findOneById($request->query->get('id'));
			// dump($dish);
			$energyNewItem =  $energyHandler->getEnergyForDishOrFoodSelected($dish, 'Dish', $nPortion);
			// dump('energy du plat:' . $energyNewItem);
		} elseif ('food' === $typeAddItem) {
			// dump('select food');
			$quantity = $request->query->get('quantity');
			// dump('quantité: ' . $quantity);
			$unitMeasure = $request->query->get('unitMeasure');
			// dump('unité de mesure:' .$unitMeasure);
			$food = $foodRepository->findOneById($request->query->get('id'));
			// dump($food);
			$energyNewItem =  $energyHandler->getEnergyForDishOrFoodSelected($food, 'Food', $quantity, $unitMeasure);
			// dump('energie de l\'aliment:' . $energyNewItem);
		}
		// dump($session->all());
		// dump($session->get('_meal_day_' . $rankMeal));
		if($session->has('_meal_day_' . $rankMeal) && array_key_exists((int)$rankDish, $session->get('_meal_day_' . $rankMeal)['dishAndFoods'])) {
			// il y a un item à cette place, on essaye de le modifier
			// dump('il y a un item à cette place, on essaye de le modifier');
			// dump('on modifie un item du repas ' . $rankMeal . ' au rang ' . $rankDish);
			// $energyItemToReplace = $session->get('_meal_day_' . $rankMeal)['dishAndFoods'][(int)$rankDish]['energy'];
			$itemToReplace = $session->get('_meal_day_' . $rankMeal)['dishAndFoods'][(int)$rankDish];
			if('Food' === $itemToReplace["type"]) {
				// dump('on remplace un food');
				$food = $foodRepository->findOneById((int)$itemToReplace["id"]);
				$energyItemToReplace = $energyHandler->getEnergyForDishOrFoodSelected($food, 'Food', $itemToReplace["quantity"], $itemToReplace["unitMeasure"]);
			}else{
				// dump('on remplace un dish');
				$dish = $dishRepository->findOneById((int)$itemToReplace["id"]);
				$energyItemToReplace = $energyHandler->getEnergyForDishOrFoodSelected($dish, 'Dish', $itemToReplace["quantity"]);
			}
			$newEnergyTotal = (int)$session->get('_meal_day_energy') + $energyNewItem - $energyItemToReplace;
			// dd($newEnergyTotal);
		} else {
			// dd('la2');
			// on ajoute un item au repas du jour
			// dump('on ajoute un item au repas ' . $rankMeal . ' au rang ' . $rankDish);
			$newEnergyTotal = (int)$session->get('_meal_day_energy') + $energyNewItem;
		}

        $remainingMealDayEnergy = abs($this->getUser()->getEnergy() - $newEnergyTotal);

		return $this->render('meals/day/show_total_energy.html.twig', [
			         'mealDayEnergy' => round($newEnergyTotal),
			'remainingMealDayEnergy' => $remainingMealDayEnergy,
			'alert' => $alertFeature->isWellBalanced($newEnergyTotal, $this->getUser()->getEnergy()),
			'showPopover' => $request->query->get('show_popover', false),
			'bgColor' => $request->query->get('bg', 'bg-white'),
			'paddingX' => 0,
		]);

    }

	#[Route('/sidebar-preselect-item/{id?}/{type?}', name: 'meal_sidebar_preselect_item', requirements: ['id' => '\d+', 'type' => 'Dish|Food'])]
	public function preSelectItem(Request $request, UnitMeasureRepository $unitMeasureRepository, AlertFeature $alertFeature, ?int $id, ?string $type)
	{
		$session = $request->getSession();

		if($request->query->has('unitMeasure')) {
			$unitMeasure = $unitMeasureRepository->findOneById((int)$request->query->get('unitMeasure'));
			$unitMeasureAlias = $unitMeasure->getAlias();
		}else{
			$unitMeasure = $unitMeasureRepository->findOneByAlias('g')->getId();
			$unitMeasureAlias = 'g';
		}

		$newDishOrFood = [
			'rankMeal' => $request->query->get('rankMeal'),
			'rankDish' => $request->query->get('rankDish'),
			'id' => $id, 
			'type' => $type, 
			'quantity' => $request->query->get('quantity'), 
			'unitMeasure' => $request->query->get('unitMeasure'),
			'unitMeasureAlias' => 'Dish' === $type ? 'portion' : $unitMeasureAlias,
		];

		if($session->has('_meal_day_preselected_items')) {
			$preSelectedItems = $session->get('_meal_day_preselected_items');
		}else{
			$preSelectedItems = [];
		}

		$preSelectedItems[] = $newDishOrFood;

		$session->set('_meal_day_preselected_items', $preSelectedItems);

		if($request->query->get('ajax')) {
			return $this->render('meals/day/_item_preselected.html.twig', [
				'item' => $newDishOrFood,
				'rankMeal' => $request->query->get('rankMeal'),
				'rankDish' => $request->query->get('rankDish'),
				'alertColor' => $request->query->get('alertColor'),
				'alertText' => $request->query->get('alertText'),
				// 'session' => $request->getSession()->all(),
			]);
		}

		return $this->render('meals/day/_sidebar_list_item_preselected.html.twig', [
			'rankMeal' => $request->query->get('rankMeal'),
			'rankDish' => $request->query->get('rankDish'),
		]);
	}

	#[Route('/sidebar-remove-preselect-item/{id?}/{type?}', name: 'meal_sidebar_remove_preselect_item', methods: ['GET'], requirements: ['id' => '\d+', 'type' => 'Dish|Food'])]
	public function removePreSelectItem(Request $request, ?int $id, ?string $type)
	{
		$session = $request->getSession();

		$preSelectedItems = $session->get('_meal_day_preselected_items');

		foreach($preSelectedItems as $index => $item) {
			if($request->query->get('rankDish') == $item['rankDish'] && $request->query->get('rankMeal') == $item['rankMeal']) {
				
				$i = $index + 1;
				while(isset($preSelectedItems[$i])) {
					$preSelectedItems[$i]['rankDish']--;
					$i++;   
				} 
				unset($preSelectedItems[$index]);

				break;
			}
		}
		$preSelectedItems = array_values($preSelectedItems);

		$session->set('_meal_day_preselected_items', $preSelectedItems);

		return $this->render('meals/day/_list_item_preselected.html.twig', [
			'items' => $preSelectedItems,
		]);
	}

	#[Route('/sidebar-remove-preselect-items', name: 'meal_sidebar_remove_preselect_items', methods: ['GET'])]
	public function removePreSelectItems(Request $request)
	{
		$session = $request->getSession();

		$session->remove('_meal_day_preselected_items');

		return new Response('OK', Response::HTTP_NO_CONTENT);
	}

	#[Route('/get-unitmeasure-alias', name: 'meal_get_unitmeasure_alias', methods: ['GET'])]
	public function getUnitMeasureAlias(Request $request, UnitMeasureRepository $unitMeasureRepository)
	{
		$unitMeasure = $unitMeasureRepository->findOneById($request->query->get('id'));

		return new Response($unitMeasure->getAlias());
	}

	#[Route('/popover-energy/{energy?}', name: 'meal_popover_energy', methods: ['GET'], requirements: ['energy' => '\d+'])]
	public function popoverEnergy(Request $request, AlertFeature $alertFeature, ?int $energy)
	{
		$session = $request->getSession();
		$mealDayEnergy = null === $energy ? $session->get('_meal_day_energy') : $energy;
		
		$remainingMealDayEnergy = abs(round($this->getUser()->getEnergy() - $mealDayEnergy));
		$alert = $alertFeature->isWellBalanced($mealDayEnergy, $this->getUser()->getEnergy());

		if(LevelAlert::BALANCE_WELL === $alert->getCode()) {
			$title = "Super, vous êtes bon !";
			$message = "Votre consommation calorique est bonne";
		} elseif(LevelAlert::BALANCE_LACK === $alert->getCode()
				||
				LevelAlert::BALANCE_VERY_LACK === $alert->getCode()
				||
				LevelAlert::BALANCE_CRITICAL_LACK === $alert->getCode()
		){
			$title = "Consommez plus !";
			$message = "Vous devriez consommer environ $remainingMealDayEnergy Kcal supplémentaires";
		} else {
			$title = "Consommez moins !";
			$message = "Vous dépassez d'environ $remainingMealDayEnergy Kcal nos recommandations";
		}

		return $this->render('partials/_content_popover_info.html.twig', [
			'title' => $title,
			'message' => $message ?? null,
			'alert' => $alert,
		]);
	}

	#[Route('/energy-meal', name: 'meal_energy', methods: ['GET'])]
	public function getEnergy(MealModel $meal, MealUtil $mealUtil)
	{
		return new Response(sprintf("%d Kcal", abs(round($mealUtil->getEnergy($meal)))));
	}

	#[Route('/session', name: 'meal_session', methods: ['GET'])]
	public function getSession(Request $request)
	{
		dd($request->getSession()->all());
	}
}