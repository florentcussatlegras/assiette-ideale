<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\Food;
use App\Entity\Meal;
use App\Entity\TypeMeal;
use App\Service\DishUtil;
use App\Service\FoodUtil;
use App\Repository\MealRepository;
use App\Entity\UnitMeasure;
use App\Service\QuantityUtil;
use App\Service\WeekAlertFeature;
use App\Entity\RecommendedQuantity;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\FoodGroup\FoodGroupParent;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
	){}

	public function calculRecommendedQuantities(UserInterface $user)
	{
		$user = $this->security->getUser();
		
		$quantities = $ratioObjectNegative = $ratioObjectPositive = $ratioNegativeMax = $ratioPositiveMax = [];

		foreach($this->manager->getRepository(FoodGroupParent::class)->findAll() as $fgp)
		{
			$quantities[$fgp->getAlias()] = $quantitiesOriginal[$fgp->getAlias()] = (int)$this->manager->getRepository(RecommendedQuantity::class)->findOneBy([
					'foodGroupParent' => $fgp,
					         'energy' => $user->getEnergy()
					]
				)->getQuantity();

			if(!$user->getDiets()->isEmpty())
			{
				foreach($user->getDiets() as $diet)
				{
					if($diet->getSubDiets()->isEmpty())
					{
						foreach ($diet->getRatios() as $ratioObject) 
						{
							if($ratioObject->getFoodGroupParent() == $fgp) 
							{
								if($ratioObject->getRatio() < 0)
									$ratioObjectNegative[$fgp->getAlias()][] = $ratioObject->getRatio();
								else
									$ratioObjectPositive[$fgp->getAlias()][] = $ratioObject->getRatio();
								// $quantities[$fgp->getAlias()] += $quantities[$fgp->getAlias()] * ($ratioObject->getRatio()/100);
							}
						}
					}else{
						foreach($user->getSubDiets() as $subDiet)
						{
							foreach ($subDiet->getRatios() as $ratioObject) 
							{
								if($ratioObject->getFoodGroupParent() == $fgp) 
								{
									if($ratioObject->getRatio() < 0)
										$ratioObjectNegative[$fgp->getAlias()][] = $ratioObject->getRatio();
									else
										$ratioObjectPositive[$fgp->getAlias()][] = $ratioObject->getRatio();
									// $quantities[$fgp->getAlias()] += $quantities[$fgp->getAlias()] * ($ratioObject->getRatio()/100);
								}
							}
						}
					}
				}
			}
		}

		//Pour chaque groupe modifié on garde le ratio le plus faible ou élevé

		foreach ($ratioObjectNegative as $fgpCode => $ratios) {
			sort($ratios);
			$ratioNegativeMax[$fgpCode] = $ratios[0];
		}

		foreach ($ratioObjectPositive as $fgpCode => $ratios) {
			sort($ratios);
			$ratioPositiveMax[$fgpCode] = end($ratios);
		}

		if(!empty($ratioNegativeMax) || !empty($ratioPositiveMax))
		{
			foreach($this->manager->getRepository(FoodGroupParent::class)->findAll() as $fgp)
			{
				if(array_key_exists($fgp->getAlias(), $ratioNegativeMax))
				{

					$quantities[$fgp->getAlias()] += $quantities[$fgp->getAlias()] * ($ratioNegativeMax[$fgp->getAlias()]/100);

				}elseif(array_key_exists($fgp->getAlias(), $ratioPositiveMax)){

					$quantities[$fgp->getAlias()] += $quantities[$fgp->getAlias()] * ($ratioPositiveMax[$fgp->getAlias()]/100);

				}

			}
		}

		return $quantities;
	}

	public function getQuantitiesConsumedNull()
	{
		$results = [];
		foreach($this->manager->getRepository(FoodGroupParent::class)->findAll() as $fgp)
		{	
			$results[$fgp->getAlias()] = 0;
		}

		return $results;
	}

	public function getQuantitiesConsumed($element, $quantitiesConsumed)
	{
		switch ($element['type']) {
			case 'Dish':
				$dishOrFood = $this->manager->getRepository(Dish::class)->findOneById((int)$element['id']);
				foreach($this->dishUtil->getFoodGroupParentQuantitiesForNPortion($dishOrFood, $element['quantity']) as $fgpCode => $quantity)
				{
					array_key_exists($fgpCode, $quantitiesConsumed) ? $quantitiesConsumed[$fgpCode] += $quantity : $quantitiesConsumed[$fgpCode] = $quantity;
				}
				break;
			default:
				$dishOrFood = $this->manager->getRepository(Food::class)->findOneById((int)$element['id']);
				$fgpCode = $dishOrFood->getFoodGroup()->getParent()->getAlias();
				$unitMeasure = $this->manager->getRepository(UnitMeasure::class)->findOneById((int)$element['unitMeasure']);
				$quantity = $this->foodUtil->convertInGr((float)$element['quantity'], $dishOrFood, $unitMeasure);
				array_key_exists($fgpCode, $quantitiesConsumed) ? $quantitiesConsumed[$fgpCode] += $quantity : $quantitiesConsumed[$fgpCode] = $quantity;
				break;
		}

		return $quantitiesConsumed;
	}

	public function getQuantitiesConsumedInSessionDishes($rankMeal = null, $rankDish = 'all')
	{
		$session = $this->requestStack->getSession();

		$quantitiesConsumed = $this->getQuantitiesConsumedNull();
		$rankLastMeal = null === $rankMeal ? $session->get('_meal_day_range') : (int)$rankMeal;
		$rankDish = 'all' !== $rankDish  ? (int)$rankDish : null;

		for ($n = 0; $n <= $rankLastMeal; $n++)
		{
			if($session->has('_meal_day_' . $n) && !empty($session->get('_meal_day_' . $n)['dishAndFoods']))
			{	
				foreach($session->get('_meal_day_' . $n)['dishAndFoods'] as $rankElement => $element)
				{
					if($rankLastMeal === $n && 'all' !== $rankDish && $rankElement === $rankDish){
						break 2;
					}else{
						// dump('on ajoute la quantité du plat ' . $element['type'] . $element['id']);
					}

					$quantitiesConsumed = $this->getQuantitiesConsumed($element, $quantitiesConsumed);
					
				}
			}
		}

		return $quantitiesConsumed;
	}

	public function getMealsPerDay($startingDate)
	{
		$user = $this->security->getUser();

		foreach ($this->weekAlertFeature->get_lundi_vendredi_from_week($startingDate) as $dateOfDay) {

			foreach($this->manager->getRepository(TypeMeal::class)->findAll() as $typeMeal)
			{
				if(null === $listMeal = $this->mealRepository->findBy(
																[
																	'eatedAt' => $dateOfDay['Y-m-d'],
																	'type' => $typeMeal,
																	'user' => $user
																]
															)
					)
				{
					if($this->session->has('_meal_' . $dateOfDay['Y-m-d']) && !empty($this->session->get('_meal_' . $dateOfDay['Y-m-d'])))
					{
						$meals[$dateOfDay['l']] = $this->session->get('_meal_' . $dateOfDay['Y-m-d']);
					}
				}else{
					$meals[$dateOfDay['l']][$typeMeal->getBackName()] = $listMeal;
				}

			}

		}

		return $meals;
	}

	public function getQuantitiesConsumedOnWeek($startingDate)
	{
		$quantitiesConsumed = $this->getQuantitiesConsumedNull();

		foreach($this->getMealsPerDay($startingDate) as $day => $listMeal)
		{
			foreach($listMeal as $typeMeal => $meals)
			{
				if (!empty($meals)) 
				{
					foreach($meals as $meal)
					{
						foreach($meal->getDishAndFoods() as $element)
						{
							$quantitiesConsumed = $this->getQuantitiesConsumed($element, $quantitiesConsumed);
						}
					}
				}
			}
		}

		return $quantitiesConsumed;
	}

	public function getRemainingQuantitiesOnWeek($startingDate)
	{
		$quantitiesConsumedOnWeek = $this->getQuantitiesConsumedOnWeek($startingDate);

		foreach($this->user->getQuantitiesRecommended() as $fgpCode => $quantity)
		{
			$remainingQuantities[$fgpCode] = ($quantity * 7) - $quantitiesConsumedOnWeek[$fgpCode];
		}

		return $remainingQuantities;
	}

	public function countDayWithNoMeal($startingDate)
	{	
		$i = 0;

		foreach($this->getMealsPerDay($startingDate) as $listMeal)
		{
			if(empty($listMeal))
				$i++;
		}

		return $i;
	}

	public function remainingQuantitiesPerDay($startingDate)
	{
		/**
		* Ici on donne la quantité conseillée pour cahque groupe par jour en fonction de ce qui a été consommé jusqu'à preésent sur toute la semaine
		*
		*/
		$countDayWithNoMeal = $this->countDayWithNoMeal($startingDate);
		$quantitiesRecommended = $this->user->getQuantitiesRecommended();
		// dump($this->getRemainingQuantitiesOnWeek());

		foreach ($this->getRemainingQuantitiesOnWeek() as $fgpCode => $quantity) {

			// if($quantity > $this->getQuantitiesRecommended()[$fgpCode])
			// {
				if(($quantity/$countDayWithNoMeal) > ($quantitiesRecommended[$fgpCode] + ($quantitiesRecommended[$fgpCode]/2)))
				{
					$remainingQuantitiesPerDay[$fgpCode] = $quantitiesRecommended[$fgpCode] + ($quantitiesRecommended[$fgpCode]/2);
				}else{
					$remainingQuantitiesPerDay[$fgpCode] = $quantity/$countDayWithNoMeal;
				}
			// }else{
			// 	$remainingQuantitiesPerDay[$fgpCode] = $quantity;
			// }

		}

		return $remainingQuantitiesPerDay;
	}



	// public function updateRemainingQuantitiesRecommended($rankMeal = null, $rankDish = null)
	// {
	// 	$quantitiesConsumed = $this->getQuantitiesConsumed($targetedList, $rankMeal, $rankDish);

	// 	foreach($this->getQuantitiesRecommended() as $fgpCode => $quantityRecommended){
	// 		$remainingQuantitiesRecommended[$fgpCode] = $quantityRecommended - $quantitiesConsumed[$fgpCode];
	// 	}
		
	// 	$this->session->set('_meal_day_remaining_quantities_recommended', $remainingQuantitiesRecommended);

	// 	return true;
	// }
}	