<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\Food;
use App\Entity\Meal;
use App\Entity\MealModel;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\FoodGroupParentRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class MealUtil
{
	public function __construct(
		private RequestStack $requestStack, 
		private EntityManagerInterface $manager, 
		private WeekAlertFeature $weekAlertFeature, 
		private EnergyHandler $energyHandler,
		private FoodUtil $foodUtil,
		private DishUtil $dishUtil,
		private FoodGroupParentRepository $foodGroupParentRepository,
	)
	{}

	public function getListfgp($dishAndFoods)
	{
		$results = [];
	
		foreach($dishAndFoods as $element)
		{
			if(null !== $element)
			{	
				switch ($element['type']) {
					case 'Dish':
						foreach($this->manager->getRepository(Dish::class)->findOneById((int)$element['id'])->getDishFoodGroupParents() as $dishFoodGroupParent)
						{
							$fgp = $dishFoodGroupParent->getFoodGroupParent();
							if(!in_array($fgp->getId(), $results) && true === $fgp->getIsPrincipal())
							{
								$results[] = $fgp->getId();
							}
						}
						break;
					default:
						$fgp = $this->manager->getRepository(Food::class)->findOneById((int)$element['id'])->getFoodGroup()->getParent();
						if(!in_array($fgp->getId(), $results) && true === $fgp->getIsPrincipal())
						{
							$results[] = $fgp->getId();
						}
						break;
				}
			}
		}

		return $results;
	}

	public function removeMealSession($rankMeal)
	{
		$session = $this->requestStack->getSession();

		$session->remove('_meal_day_' . $rankMeal);
		if($session->get('_meal_day_range') > 0)
		{
			for($n = ($rankMeal+1); $n <= $session->get('_meal_day_range'); $n++)
			{
				$meal = $session->get('_meal_day_' . $n);
				$session->remove('_meal_day_' . $n);
				$session->set('_meal_day_' . ($n-1), $meal);
			}
			$rangeMeal = $session->get('_meal_day_range') - 1;
			$session->set('_meal_day_range', $rangeMeal);
		} else {
			$session->remove('_meal_day_range');
			$session->set('_meal_day_energy', 0);
			$session->remove('_meal_day_energy_evolution');
			$session->remove('_meal_day_alerts/_dishes_selected');
			$session->remove('_meal_day_alerts/_foods_selected');
			$session->remove('_meal_day_alerts/_dishes_not_selected');
			$session->remove('_meal_day_alerts/_foods_not_selected');
		}
	}

	public function removeMealsSession()
	{
		$session = $this->requestStack->getSession();

		if($session->has('_meal_day_range')){
			for($rank = 0; $rank <= $session->get('_meal_day_range'); $rank++)
			{
				$session->remove('_meal_day_' . $rank);
			}
			$session->remove('_meal_day_range');
			$session->set('_meal_day_energy', 0);
			$session->remove('_meal_day_energy_evolution');
			$session->remove('_meal_day_alerts/_dishes_selected');
			$session->remove('_meal_day_alerts/_foods_selected');
			$session->remove('_meal_day_alerts/_dishes_not_selected');
			$session->remove('_meal_day_alerts/_foods_not_selected');
		}

		foreach ($this->weekAlertFeature->get_lundi_vendredi_from_week() as $dateOfDay) {
			if($session->has('_meal_' . $dateOfDay['Y-m-d']))
				$session->remove('_meal_' . $dateOfDay['Y-m-d']);
		}
	}

	public function getEnergy(Meal|MealModel $meal)
	{	
		$energy = 0;
		foreach($meal->getDishAndFoods() as $element) {
			// dump($this->energyHandler->getEnergyForDishOrFoodSelected($element['id'], $element['type'], $element['quantity'], $element['unitMeasureAlias']));
			$energy += $this->energyHandler->getEnergyForDishOrFoodSelected($element['id'], $element['type'], $element['quantity'], $element['unitMeasureAlias']);
		}

		return $energy;
	}

	public function getNutrients(Meal $meal)
	{
		$results = [
			'protein' => 0,
			'lipid' => 0,
			'carbohydrate' => 0,
			'sodium' => 0,
		];

		foreach($meal->getDishAndFoods() as $element) {
			$nutrients = $this->foodUtil->getNutrientsForDishOrFoodSelected($element['id'], $element['type'], $element['quantity'], $element['unitMeasureAlias']);
			$results['protein'] += $nutrients['protein']; 
			$results['lipid'] += $nutrients['lipid']; 
			$results['carbohydrate'] += $nutrients['carbohydrate'];
			$results['sodium'] += $nutrients['sodium'];
		}

		return $results;
	}

	public function getFoodGroupParents(Meal $meal)
	{
		$foodGroupParents = $this->foodGroupParentRepository->findAll();

		foreach($foodGroupParents as $foodGroupParent) {
			$results[$foodGroupParent->getAlias()] = 0;
		}

		foreach($meal->getDishAndFoods() as $element) {
			if('Dish' === $element['type']) {
				$dish = $this->manager->getRepository(Dish::class)->findOneById((int)$element['id']);
				$fgpValues = $this->dishUtil->getFoodGroupParentQuantitiesForNPortion($dish, $element['quantity']);
				foreach($foodGroupParents as $foodGroupParent) {
					$results[$foodGroupParent->getAlias()] += $fgpValues[$foodGroupParent->getAlias()];
				}
			}else{
				$food = $this->manager->getRepository(Food::class)->findOneById((int)$element['id']);
				if('g' !== $element['unitMeasureAlias']) {
					$quantityG = $this->foodUtil->convertInGr($element['quantity'], $food, $element['unitMeasureAlias']);
				}else{
					$quantityG = $element['quantity'];
				}
				$results[$food->getFoodGroup()->getParent()->getAlias()] += $quantityG;
			}
		}

		return $results;
	}

}