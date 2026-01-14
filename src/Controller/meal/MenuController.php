<?php

namespace App\Controller\meal;

use App\Repository\FoodGroupParentRepository;
use App\Repository\TypeMealRepository;
use App\Repository\MealRepository;
use App\Entity\Dish;
use App\Entity\Food;
use App\Entity\Meal;
use App\Entity\TypeMeal;
use App\Entity\MealModel;
use App\Service\DishUtil;
use App\Service\FoodUtil;
use App\Service\MealUtil;
use App\Entity\UnitMeasure;
use App\Service\MenuFeature;
use App\Service\AlertFeature;
use App\Service\BalanceSheetFeature;
use App\Service\WeekAlertFeature;
use App\Service\QuantityTreatment;
use App\Entity\RecommendedQuantity;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\FoodGroup\FoodGroupParent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


#[Route('/mes-menus')]
class MenuController extends AbstractController
{
	#[Route('/add', name: 'menu_add', options: ['expose' => true])]
	public function add(Request $request, EntityManagerInterface $manager, WeekAlertFeature $weekAlertFeature)
	{
		$session = $request->getSession();

		if(null !== $meals = $manager->getRepository(Meal::class)->findBy([
				'eatedAt' => $session->get('_meal_day_date'), 
				'user' => $this->getUser()
			])	
		)
		{
			foreach ($meals as $meal) {
				$manager->remove($meal);
			}
		}

		for($n = 0; $n <= $session->get('_meal_day_range'); $n++)
		{
			if(array_key_exists('dishAndFoods', $session->get('_meal_day_' . $n)) && !empty($session->get('_meal_day_' . $n)['dishAndFoods']))
			{
				$alertOnDishes = array_key_exists($n, $session->get('_meal_day_alerts/_dishes_selected')) ? $session->get('_meal_day_alerts/_dishes_selected')[$n] : [];
				$alertOnFoods = array_key_exists($n, $session->get('_meal_day_alerts/_foods_selected')) ? $session->get('_meal_day_alerts/_foods_selected')[$n] : [];

				$typeMeal = $manager->getRepository(TypeMeal::class)->findOneByBackName($session->get('_meal_day_' . $n)['type']);
			
				if (!$session->has('_meal_day_date')) {
					$mealDayDate = new \DateTime();
					$mealDayDate = $mealDayDate->format(\DateTime::W3C);
				}else{
					$mealDayDate = $session->get('_meal_day_date');
				}

				$meal = new Meal('nom', $n, $mealDayDate, $session->get('_meal_day_' . $n)['dishAndFoods'], $typeMeal, $this->getUser(), $alertOnDishes, $alertOnFoods);
				if($session->has('_meal_day_alerts/_final_list')) {
					$meal->setAlertsAllMealsDay($session->get('_meal_day_alerts/_final_list'));
				}
				$meal->setEnergyAllMealsDay($session->get('_meal_day_energy'));
				$meal->setListFgpAllMealsDay($session->get('_meal_day/_list_fgp'));
				$meal->setListFgpRemainingAbsentAllMealsDay($session->get('_meal_day/_list_fgp_remaining_absent'));

				$manager->persist($meal);
			}
		}

		$manager->flush();

		$this->addFlash('notice', 'Les repas ont bien été enregistrés');

		return $this->redirectToRoute('menu_week_menu', [
			'startingDate' => $weekAlertFeature->getStartingDayOfWeek($mealDayDate),
		]);
	}

	#[Route('/week', name: 'menu_week_menu')]
	public function week(Request $request, QuantityTreatment $quantityTreatment, WeekAlertFeature $weekAlertFeature, TypeMealRepository $typeMealRepository, BalanceSheetFeature $balanceSheetFeature)
	{
		// dd($request->getSession()->all());
		$startingDate = $request->query->get('startingDate');
		// dd($quantityTreatment->getMealsPerDay());

		$mealsPerDay = $quantityTreatment->getMealsPerDay($startingDate);
		
		$energyTotalPerDays = [];
		$averageDailyNutrientPerDays = [];
		foreach($mealsPerDay as $day => $mealsOfTheDayPerType) {
			// $averageDailyNutrientPerDays[$day] = $balanceSheetFeature->averageDailyNutrientForAPeriod($day, $day);
			foreach($mealsOfTheDayPerType as $typeMeal => $meals) {
				if(!empty($meals)) {
					$energyTotalPerDays[$day] = $meals[0]->getEnergyAllMealsDay();
				}
			}
		}

		if($request->query->get('ajax')) {

			if("mobile" === $request->query->get('format')) {

					return $this->render("meals/week/mobile/_wrapper_menu_week.html.twig", [
							"days" => $weekAlertFeature->getDaysForWeekMenu($startingDate),
							"meals" => $quantityTreatment->getMealsPerDay($startingDate),
				"quantitiesConsumed" => $quantityTreatment->getQuantitiesConsumedOnWeek($startingDate),
						"typeMeals" => $typeMealRepository->findAll(),
				"energyTotalPerDays" => $energyTotalPerDays,
					]
				);
			}

			return $this->render("meals/week/_wrapper_menu_week.html.twig", [
						"days" => $weekAlertFeature->getDaysForWeekMenu($startingDate),
						"meals" => $quantityTreatment->getMealsPerDay($startingDate),
			"quantitiesConsumed" => $quantityTreatment->getQuantitiesConsumedOnWeek($startingDate),
					"typeMeals" => $typeMealRepository->findAll(),
			"energyTotalPerDays" => $energyTotalPerDays,
				]
			);

		}

		return $this->render("meals/week/index.html.twig", [
			 	         "days" => $weekAlertFeature->getDaysForWeekMenu($startingDate),
			            "meals" => $quantityTreatment->getMealsPerDay($startingDate),
           "quantitiesConsumed" => $quantityTreatment->getQuantitiesConsumedOnWeek($startingDate),
				    "typeMeals" => $typeMealRepository->findAll(),
	       "energyTotalPerDays" => $energyTotalPerDays,
//   "averageDailyNutrientPerDays" => $averageDailyNutrientPerDays,
			    ]
		    );
	}

	#[Route('/get/{date}', name: 'menu_week_get_meals')]
	public function getMeals(Request $request, EntityManagerInterface $manager, MealUtil $mealUtil, BalanceSheetFeature $balanceSheetFeature, AlertFeature $alertFeature, $date = null)
	{
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

		$session = $request->getSession();

		$mealUtil->removeMealsSession();

		if(null === $date)
		{
			if($request->query->get('date')) {
				$date = $request->query->get('date');
			} else {
				$dateDay = new \DateTime;
				$date = $dateDay->format('Y-m-d');
			}
		}
			
		$meals = $manager->getRepository(Meal::class)->findBy([
			'eatedAt' => $date,
			'user' => $this->getUser()
		]);

		if($request->query->get('ajax')) {
			return $this->render('meals/week/_meals_details.html.twig', [
				'meals' => $meals,
				'date' => $date,
				'averageDailyNutrient' => $balanceSheetFeature->averageDailyNutrientForAPeriod($date, $date),
			]);
		}
		
		if(!empty($meals))
		{
			foreach ($meals as $i => $meal) {
				$session->set('_meal_day_' . $i, ['type' => $meal->getType()->getBackName(), 'dishAndFoods' => $meal->getDishAndFoods()]);
			}

			$session->set('_meal_day_range', $i);
		}

		$session->set('_meal_day_date', $date);

		return $this->redirectToRoute('meal_day');
	}


	#[Route('/get/{id}/{date}', name: 'menu_get_by_date_and_type')]
	public function getMeal(Request $request, EntityManagerInterface $manager, TypeMealRepository $typeMealRepository, MealRepository $mealRepository, MealUtil $mealUtil, TypeMeal $typeMealToAdd, string $date)
	{
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

		$session = $request->getSession();
		$mealUtil->removeMealsSession();

		$typeMeals = $typeMealRepository->findAll([], ['order' => 'ranking']);
		$range = 0;

		if( null !== $meals = $mealRepository->findBy([
			'eatedAt' => $date,
			'user' => $this->getUser()
		], ['rankView' => 'ASC'])) 
		{
			foreach($typeMeals as $typeMeal) {
				if($typeMeal->getId() === $typeMealToAdd->getId()) {
					$session->set('_meal_day_' . $range, ['type' => $typeMeal->getBackName(), 'dishAndFoods' => []]);
					$range++;
				}
				foreach($meals as $meal) {
					if($meal->getType()->getId() === $typeMeal->getId()) {
						$session->set('_meal_day_' . $range, ['type' => $meal->getType()->getBackName(), 'dishAndFoods' => $meal->getDishAndFoods()]);
						$range++;
					}
				}
			}
		}
		$session->set('_meal_day_range', $range - 1);
		// dd($request->getSession()->all());

		// foreach($typeMeals as $type) {
		// 	if( null !== $meal = $mealRepository->findOneBy([
		// 			'eatedAt' => $date,
		// 			'user' => $this->getUser(),
		// 			'type' => $type,
		// 		])) 
		// 	{
		// 		dump($meal);
		// 		// if($typeMeal == $type) {
		// 		// 	$session->set('_meal_day_' . $range, ['type' => $typeMeal->getBackName(), 'dishAndFoods' => []]);
		// 		// }else{
		// 		$session->set('_meal_day_' . $meal->getRankView(), ['type' => $meal->getType()->getBackName(), 'dishAndFoods' => $meal->getDishAndFoods()]);
		// 		$session->set('_meal_day_range', (int)$meal->getRankView());
		// 		// // }
		// 		// $range++;
		// 	}
		// }

		
		// $session->set('_meal_day_0', ['type' => $typeMeal->getBackName(), 'dishAndFoods' => []]);


		$session->set('_meal_day_date', $date);

		return $this->redirectToRoute('meal_day');
	}


	#[Route('/remove/{date}/{id?}', name: 'menu_week_remove_meals')]
	public function removeMeals(?TypeMeal $typeMeal, Request $request, EntityManagerInterface $manager, TokenStorageInterface $tokenStorage, MealUtil $mealUtil, string $date)
	{
		$mealUtil->removeMealsSession();

		if($typeMeal) {
			
			$meals = $manager->getRepository(Meal::class)->findBy([
				'eatedAt' => $date,
				'type' => $typeMeal,
				'user' => $this->getUser()
			]);

		} else {

			$meals = $manager->getRepository(Meal::class)->findBy([
				'eatedAt' => $date, 
				'user' => $this->getUser()
			]);

		}

		if(!empty($meals))
		{
			foreach ($meals as $meal) {
				$manager->remove($meal);
			}
		} 

		$manager->flush();

		$this->addFlash('notice', 'Les plats ont bien été supprimés');

		if($request->query->has('from_menu')) {
			return $this->redirectToRoute('menu_week_menu');
		}

		return $this->redirectToRoute('meal_day');
	}

	 #[Route('/listfgp/{meals}', name: 'menu_week_list_fgp')]
	public function getListFgpMeals(Request $request, MealUtil $mealUtil, FoodGroupParentRepository $foodGroupParentRepository, EntityManagerInterface $manager, $meals)
	{
		$listFgp = [];

		foreach ($meals as $day => $mealsByDay) {

			foreach ($mealsByDay as $typeMeal => $mealsByType) {

				if (!empty($mealsByType)) {

					foreach ($mealsByType as $meal) {

						$dishAndFoods = ($meal instanceof Meal) ? $meal->getDishAndFoods() : $meal['dishAndFoods'];

						foreach ($mealUtil->getListfgp($dishAndFoods) as $fgp) {

							if (!in_array($fgp, $listFgp)) {
								$listFgp[] = $fgp;
							}

						}

					}

				}

			}

		}

		return $this->render("meals/partials/_list-fgp.html.twig", 
			[
				"listFgp" => $listFgp,
				"allFgp" => $foodGroupParentRepository->getIdsPrincipal()(),
			]
		);
	}

	#[Route('/listfgp/{id}', name: 'menu_week_meal_list_fgp')]
	public function getListFgpMeal(Request $request, MealUtil $mealUtil, FoodGroupParentRepository $foodGroupParentRepository, EntityManagerInterface $manager, Meal $meal, int $sizeTabletColorFgp = 5)
	{
		return $this->render("meals/partials/_list_fgp.html.twig", 
			[
			    "listFgp" => $mealUtil->getListfgp($meal->getDishAndFoods()),
				"foodGroupParents" => $foodGroupParentRepository->findByIsPrincipal(1),
				"size" => $sizeTabletColorFgp
			]
		);
	}

	 #[Route('/adviced-menu/{date}', name: 'menu_week_adviced_menu')]
	public function getAdvicedMenu(Request $request, EntityManagerInterface $manager, TokenStorageInterface $tokenStorage, QuantityTreatment $quantityTreatment, DishUtil $dishUtil, MenuFeature $menuFeature, $date)
	{
		$user = $tokenStorage->getToken()->getUser();

		//BREAKFAST
		$breakfast = $menuFeature->getRandomBreakfast($date);

	  	$remainingQuantitiesForDay = $quantityTreatment->remainingQuantitiesPerDay();
	  	
	  	$quantitiesConsumed = $quantityTreatment->getQuantitiesConsumedNull();

	  	$dishesSelected = $foodsSelected = [];

		foreach($breakfast->getDishAndFoods() as $element)
		{
			if('Dish' === $element['type'])
			{
				$dishesSelected[] = (int)$element['id'];
			}else{
				$foodsSelected[] = (int)$element['id'];
			}
			$quantitiesConsumed = $quantityTreatment->getQuantitiesConsumed($element, $quantitiesConsumed);
		}

		foreach($quantitiesConsumed as $fgpCode => $quantityConsumed)
		{
			$remainingQuantitiesForDay[$fgpCode] -= $quantityConsumed;
		}

		//On répartie les quantités restantes dans les (éventuelles) collations et repas

		$morningSnack = $user->getSnacks()->contains($manager->getRepository(TypeMeal::class)->findOneByBackName('morning_snack'));
		$afternoonSnack = $user->getSnacks()->contains($manager->getRepository(TypeMeal::class)->findOneByBackName('afternoon_snack'));
		$eveningSnack = $user->getSnacks()->contains($manager->getRepository(TypeMeal::class)->findOneByBackName('evening_snack'));

		foreach($remainingQuantitiesForDay as $fgpCode => $remainingQuantity)
		{
			$firstPartOfDay[$fgpCode] = $secondPartOfDay[$fgpCode] = $remainingQuantity/2;

			if($morningSnack)
			{
				$quantitiesForMorningSnack[$fgpCode] = $firstPartOfDay[$fgpCode] / 3;
				// $quantitiesForLunch[$fgpCode] = ($firstPartOfDay[$fgpCode] * 2) / 3;
			}else{
				$quantitiesForMorningSnack[$fgpCode] = 0;
				$quantitiesForLunch[$fgpCode] = $firstPartOfDay[$fgpCode];
			}

			if($afternoonSnack)
			{
				$quantitiesForAfternoonSnack[$fgpCode] = $secondPartOfDay[$fgpCode] / 3;				
			}else{
				$quantitiesForAfternoonSnack[$fgpCode] = 0;
				$quantitiesForDinner[$fgpCode] = $secondPartOfDay[$fgpCode];
			}
		}

		//COLLATION DU MATIN
		if ($morningSnack){

			$morningSnackElements = $menuFeature->getSnack('morning_snack', $quantitiesForMorningSnack, $date, $foodsSelected, $dishesSelected);

			$quantitiesConsumed = $quantityTreatment->getQuantitiesConsumedNull();

			foreach($morningSnackElements['meal']->getDishAndFoods() as $element)
			{
				$quantitiesConsumed = $quantityTreatment->getQuantitiesConsumed($element, $quantitiesConsumed);
			}
			
			foreach($quantitiesConsumed as $fgpCode => $quantityConsumed)
			{
				$quantitiesForLunch[$fgpCode] = $firstPartOfDay[$fgpCode] - $quantityConsumed;
			}

		}else{

			$morningSnackElements = ['foodsSelected' => $foodsSelected, 'dishesSelected' => $dishesSelected, 'meal' => null];
		}

		//DEJEUNER
		$lunchElements = $menuFeature->getMeal('lunch', $quantitiesForLunch, $date, $foodsSelected, $dishesSelected);

		//COLLATION DE L'APRES MIDI
		if ($afternoonSnack)
		{
			$afternoonSnackElements = $menuFeature->getSnack('afternoon_snack', $quantitiesForAfternoonSnack, $date, $foodsSelected, $dishesSelected);
		
			$quantitiesConsumed = $quantityTreatment->getQuantitiesConsumedNull();

			foreach($afternoonSnackElements['meal']->getDishAndFoods() as $element)
			{
				$quantitiesConsumed = $quantityTreatment->getQuantitiesConsumed($element, $quantitiesConsumed);
			}

			foreach($quantitiesConsumed as $fgpCode => $quantityConsumed)
			{
				$quantitiesForDinner[$fgpCode] = $secondPartOfDay[$fgpCode] - $quantityConsumed;
			}

		}else{
		
			$afternoonSnackElements = ['foodsSelected' => $foodsSelected, 'dishesSelected' => $dishesSelected, 'meal' => null];
		
		}

		//DINNER
		$dinnerElements = $menuFeature->getMeal('dinner', $quantitiesForDinner, $date, $lunchElements['foodsSelected'], $lunchElements['dishesSelected']);

		$request->getSession()->set('_meal_' . $date, [$breakfast, $morningSnackElements['meal'], $lunchElements['meal'], $afternoonSnackElements['meal'], $dinnerElements['meal']]);
	  	
	  	return $this->redirectToRoute('menu_week_menu');
	}

	 #[Route('/adviced-menu/cancel/{date}', name: 'menu_week_adviced_menu_cancel')]
	public function cancelAdvicedMenu(Request $request, $date)
	{
		$request->getSession()->remove('_meal_' . $date);

		return $this->redirectToRoute('menu_week_menu');
	}

	 #[Route('/adviced-menu/save/{date}', name: 'menu_week_adviced_menu_save')]
	public function saveAdvicedMenu(Request $request, EntityManagerInterface $manager, TokenStorageInterface $tokenStorage, $date)
	{
		$user = $tokenStorage->getToken()->getUser();

		foreach($request->getSession()->get('_meal_' . $date) as $meal)
		{
			if(null !== $meal)
			{
				$type = $manager->getRepository(TypeMeal::class)->findOneById($meal->getType()->getId());
				$meal->setType($type);
				$meal->setUser($user);

				$manager->persist($meal);
			}
		}

		$request->getSession()->remove('_meal_' . $date);

		$manager->flush();

		return $this->redirectToRoute('menu_week_menu');
	}

	#[Route('/meals-energy/{energyMeals}', name: 'menu_meals_show_total_energy')]
	public function mealsEnergy(Request $request, AlertFeature $alertFeature, $energyMeals)
	{
		$session = $request->getSession();
	
		// $mealDayEnergy = $energyMeals;
		// $remainingMealDayEnergy = abs(round($this->getUser()->getEnergy() - $mealDayEnergy));

		return $this->render('meals/week/_show_total_energy.html.twig', [
			         'mealDayEnergy' => $energyMeals,
			// 'remainingMealDayEnergy' => $remainingMealDayEnergy,
			'alert' => $alertFeature->isWellBalanced($energyMeals, $this->getUser()->getEnergy()),
			'sizeIcon' => $request->query->get('sizeIcon'),
			'fromMenuWeek' => $request->query->has('fromMenuWeek') ? true : false,
			'page' => 'menu_detail',
			'bgColor' => $request->query->get('bgColor', 'light-blue'),
		]);
	}
}