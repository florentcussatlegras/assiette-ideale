<?php

namespace App\Service;

use App\Service\MealUtil;
use App\Repository\MealRepository;
use App\Repository\DishRepository;
use App\Repository\FoodRepository;
use App\Repository\FoodGroupParentRepository;
use Symfony\Component\Security\Core\Security;

class BalanceSheetFeature
{
    public function __construct(
        private Security $security,
        private MealRepository $mealRepository,
        private DishRepository $dishRepository,
        private FoodRepository $foodRepository,
        private MealUtil $mealUtil,
        private FoodUtil $foodUtil,
        private FoodGroupParentRepository $foodGroupParentRepository,
    )
    {}

    public function averageDailyEnergyForAPeriod($start, $end)
    {
        $dateStart = new \DateTime($start);
        $dateEnd = new \DateTime($end);
        // $dateEnd = $dateEnd->modify('+1 day');
        // dd($dateStart, $dateEnd);

        $meals = $this->getMealsForAPeriod($dateStart, $dateEnd);
        if(empty($meals)) {
            return null;
        }
        $energy = 0;
        foreach ($meals as $dateDay => $list) {

            foreach($list as $meal) {
                $energy += $this->mealUtil->getEnergy($meal);
            }
        }
        // dump($energy);
        
        $countDays = $dateStart->diff($dateEnd)->format("%a") + 1;
        // dd($countDays);
        
        return 0 != $countDays ? round($energy/$countDays) : round($energy); 
    }

    public function averageDailyNutrientForAPeriod($start, $end)
    {
        $dateStart = new \DateTime($start);
        $dateEnd = new \DateTime($end);
        $dateEnd = $dateEnd->modify('+1 day');

        $results = [
            'protein' => 0,
            'lipid' => 0,
            'carbohydrate' => 0,
            'sodium' => 0
        ];

        $meals = $this->getMealsForAPeriod($dateStart, $dateEnd);
  
        foreach ($meals as $dateDay => $list) {
            foreach($list as $meal) {
                // $nutrients += $this->mealUtil->getNutrients($meal);
                $nutrientsValues = $this->mealUtil->getNutrients($meal);
                $results['protein'] += $nutrientsValues['protein'];
                $results['lipid'] += $nutrientsValues['lipid'];
                $results['carbohydrate'] += $nutrientsValues['carbohydrate'];
                $results['sodium'] += $nutrientsValues['sodium'];
            }
        }

        $countDays = $dateStart->diff($dateEnd)->format("%a");

        array_walk($results, function (&$value, $key, $countDays) {
            return $value = round($value/$countDays);
        }, $countDays);

        return $results; 
    }

    public function averageDailyFgpForAPeriod($start, $end)
    {
        $dateStart = new \DateTime($start);
        $dateEnd = new \DateTime($end);
        $dateEnd = $dateEnd->modify('+1 day');

        $results = [];

        $meals = $this->getMealsForAPeriod($dateStart, $dateEnd);
        $foodGroupParents = $this->foodGroupParentRepository->findByIsPrincipal(1);
		foreach($foodGroupParents as $foodGroupParent) {
			$results[$foodGroupParent->getAlias()] = 0;
		}

        foreach ($meals as $dateDay => $list) {
            foreach($list as $meal) {
                $fgpValues = $this->mealUtil->getFoodGroupParents($meal);
                foreach($foodGroupParents as $foodGroupParent) {
                    $results[$foodGroupParent->getAlias()] += $fgpValues[$foodGroupParent->getAlias()];
                }
            }
        }
        
        $countDays = $dateStart->diff($dateEnd)->format("%a");

        array_walk($results, function (&$value, $key, $countDays) {
            return $value = round($value/$countDays);
        }, $countDays);

        return $results;
    }

    public function getFavoriteDishPerPeriod($start, $end)
	{
        $dateStart = new \DateTime($start);
        $dateEnd = new \DateTime($end);

        $meals = $this->getMealsForAPeriod($dateStart, $dateEnd);

        foreach($meals as $date => $list) {
            foreach($list as $meal) {
                foreach($meal->getDishAndFoods() as $item) {
                    if('Dish' === $item['type']) {
                        if(isset($dishes[$item['id']])) {
                            $dishes[$item['id']] += (int) $item['quantity'];
                        }else{
                            $dishes[$item['id']] = (int) $item['quantity'];
                        }
                    }
                }
            }
        }

        if(isset($dishes)) {
            $maxValue = max($dishes);
            $id = array_keys($dishes, $maxValue)[0];
            $dish = $this->dishRepository->findOneById($id);
        }

		return $dish ?? null;
	}

    public function getFavoriteFoodPerPeriod($start, $end)
	{
        $dateStart = new \DateTime($start);
        $dateEnd = new \DateTime($end);

        $meals = $this->getMealsForAPeriod($dateStart, $dateEnd);

        foreach($meals as $date => $list) {
            foreach($list as $meal) {
                foreach($meal->getDishAndFoods() as $item) {
                    if('Food' === $item['type']) {
                        $quantityGr = $this->foodUtil->convertInGr($item['quantity'], $item['id'], $item['unitMeasureAlias']);
                        if(isset($foods[$item['id']])) {
                            $foods[$item['id']] += (int) $quantityGr;
                        }else{
                            $foods[$item['id']] = (int) $quantityGr;
                        }
                    }
                }
            }
        }

        if(isset($foods)) {
            $maxValue = max($foods);
            $id = array_keys($foods, $maxValue)[0];
            $food = $this->foodRepository->findOneById($id);
        }

		return $food ?? null;
	}

    public function getMostCaloricPerPeriod($start, $end)
	{
        $dateStart = new \DateTime($start);
        $dateEnd = new \DateTime($end);
        // $meals = $this->mealRepository->findBy([
        //     'user' => $this->security->getUser(),
        // ]);
        $meals = $this->getMealsForAPeriod($dateStart, $dateEnd);
        $energyMax = 0;
        $mostCaloricMeal = null;
        foreach($meals as $dateStr => $list) {
            foreach($list as $meal) {
                $energy = $this->mealUtil->getEnergy($meal);
                if($energy > $energyMax) {
                    $energyMax = $energy;
                    $mostCaloricMeal = $meal;
                }
            }
        }

        return $mostCaloricMeal;
    }

    public function getMealsForAPeriod(\DateTime $dateStart, \DateTime $dateEnd, ?string $feature = 'energy'): ?array
    {
        $results = [];
        if($dateStart != $dateEnd) {
            foreach(new \DatePeriod($dateStart, new \DateInterval('P1D'), $dateEnd, \DatePeriod::INCLUDE_END_DATE) as $dt) {
                $dateStr = $dt->format('Y-m-d');
                if(null !== $meals = $this->getMealByDate($dateStr)) {
                    $results[$dateStr] = $meals;
                }
            }
        }else{
            $dateStr = $dateStart->format('Y-m-d');
            if(null !== $meals = $this->getMealByDate($dateStr)) {
                $results[$dateStr] = $meals;
            }
        }

        return $results;
    }

    private function getMealByDate(String $dateStr): ?array
    {
        $meals = $this->mealRepository->findBy([
            'eatedAt' => $dateStr,
            'user' => $this->security->getUser(),
        ]);

        if(!empty($meals)) {
            return $meals;
        }

        return null;
    }
}