<?php

namespace App\Controller\meal;

use App\Entity\Dish;
use App\Entity\Food;
use App\Entity\User;
use App\Entity\MealModel;
use App\Service\MealUtil;
use App\Service\AlertFeature;
use App\Form\Type\ParameterType;
use App\Service\QuantityTreatment;
use App\Entity\RecommendedQuantity;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\FoodGroup\FoodGroupParent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @author Florent Cussatlegras <florent.cussatlegras@gmail.com>
 */

#[Route('les-alertes')]
class AlertController extends AbstractController
{
	#[Route('/show/{class}/{dishOrFoodId}/{isSelected}/{rankMeal}/{rankDish}', name: 'alert_meal_list', options: ['expose' => true])]
	public function show(Request $request, $class, $dishOrFoodId, $isSelected, $rankMeal = null, $rankDish = null)
	{
	
		$alerts = [];
		$session = $request->getSession();

		// dd($session->get('_meal_day_alerts/_foods_selected'));
		// if($dishOrFoodId === 713) {
		// 	dd('Ananas');
		// }

		if(true == $isSelected)
		{	

			if('App\Entity\Dish' === $class || 'Dish' === $class)
			{
				if(!empty($session->get('_meal_day_alerts/_dishes_selected')) && array_key_exists($rankMeal, $session->get('_meal_day_alerts/_dishes_selected')) && array_key_exists($rankDish, $session->get('_meal_day_alerts/_dishes_selected')[$rankMeal]))
					$alerts = $session->get('_meal_day_alerts/_dishes_selected')[$rankMeal][$rankDish];

			}elseif('App\Entity\Food' === $class || 'Food' === $class){
			
				if(!empty($session->get('_meal_day_alerts/_foods_selected')) && array_key_exists($rankMeal, $session->get('_meal_day_alerts/_foods_selected')) && array_key_exists($rankDish, $session->get('_meal_day_alerts/_foods_selected')[$rankMeal]))
					$alerts = $session->get('_meal_day_alerts/_foods_selected')[$rankMeal][$rankDish];


			}

			// dd($alerts['higher_level']);
			if($request->query->get('showMessages')) {
				return $this->render('meals/day/_list_alert_messages.html.twig', [
					'alerts' => $alerts,
				]);
			}

			if(!empty($alerts))
			{
				$session->set('color_current_alert', $alerts['higher_level']->getColor());
			}else{
				$session->remove('color_current_alert');
			}

			return $this->render('meals/day/list-ajax-alerts.html.twig', [
					'alerts' => $alerts,
					'class' => $class,
					'dishOrFoodId' => $dishOrFoodId,
					'isSelected' => $isSelected,
					'rankMeal' => $rankMeal,
					'rankDish' => $rankDish,
				]
			);

		}else{
			
			if('App\Entity\Dish' === $class || 'Dish' === $class) {
				
				$alertsOnDishesNotSelected = $session->get('_meal_day_alerts/_dishes_not_selected');
				
			} elseif('App\Entity\Food' === $class || 'Food' === $class) {
				
				$alertsOnDishesNotSelected = $session->get('_meal_day_alerts/_foods_not_selected');

			}

			if(!empty($alertsOnDishesNotSelected) && array_key_exists($dishOrFoodId, $alertsOnDishesNotSelected))
			{
				$alerts = $alertsOnDishesNotSelected[$dishOrFoodId];
				// dd($alerts);
				$session->set('color_current_alert', $alerts['higher_level']->getColor());
			}else{
				$session->remove('color_current_alert');
			}

			if($request->query->get('showMessages')) {
				return $this->render('meals/day/_list_alert_messages.html.twig', [
					'alerts' => $alerts,
				]);
			}

			return $this->render('meals/day/list-ajax-alerts.html.twig', [
					'alerts' => $alerts,
					'class' => $class,
					'dishOrFoodId' => $dishOrFoodId,
					'isSelected' => $isSelected,
					'rankMeal' => $rankMeal,
					'rankDish' => $rankDish,
				]
			);

		}
	}

	#[Route('/update-alert-on-dish-on-update-portion/{id}/{nPortion}', name: 'meal_day_update_alert_on_dish_on_update_portion', options: ['expose' => true])]
	public function updateAlertOnDishOnUpdatePortion(Request $request, Dish $dish, $nPortion, AlertFeature $alertFeature)
	{
		$alertFeature->setAlertOnDishOrFoodQuantityUpdated($dish, $nPortion, null, $request->query->get('rankMeal'), $request->query->get('rankDish'));

		return $this->redirectToRoute('alert_meal_list', [
						       'class' => 'Dish',
						'dishOrFoodId' => $dish->getId(),
						  'isSelected' => 0
			]
		);
	}

	#[Route('/update-alert-on-food-on-update-quantity/{id}/{quantity}/{unitMeasure}', name: 'meal_day_update_alert_on_food_on_update_quantity', options: ['expose' => true])]
	public function updateAlertOnFoodOnUpdateQuantity(Request $request, Food $food, $quantity, $unitMeasure, AlertFeature $alertFeature)
	{
		$alertFeature->setAlertOnDishOrFoodQuantityUpdated($food, $quantity, $unitMeasure, $request->query->get('rankMeal'), $request->query->get('rankDish'));

		return $this->redirectToRoute('alert_meal_list', [
			 				   'class' => 'Food',
						'dishOrFoodId' => $food->getId(),
						  'isSelected' => 0
			]
		);
	}

	#[Route('/show-on-week', name: 'alert_show_on_week', options: ['expose' => true])]
	public function showOnWeek(Request $request, EntityManagerInterface $manager, TokenStorageInterface $tokenStorage, AlertFeature $alertFeature, $quantitiesConsumed)
	{
   		// $recommendedQuantities = $manager->getRepository(RecommendedQuantity::class)->findByEnergy($tokenStorage->getToken()->getUser()->getEnergy());
   		$fgpLevel = [];

   		foreach ($manager->getRepository(FoodGroupParent::class)->findAll() as $fgp) {
			$fgpLevel[
					$alertFeature->getLevel(
									$quantitiesConsumed[$fgp->getAlias()]/7, 
									$fgp->getAlias()
								)
				]
			[] = $fgp->getId();
   		}

		return $this->render('meals/week/alerts_on_week.html.twig', [
			'fgpLevel' => $fgpLevel,
  'quantitiesConsumed' => $quantitiesConsumed
			]		
		);
	}

	#[Route('/show-session', name: 'alert_show_session')]
	public function showSession(Request $request)
	{
		// dump($request->getSession()->get('_meal_day_0'));
		// dd($request->getSession()->get('_meal_day_alerts/_dishes_selected'));
		dd($request->getSession()->all());
	}
}