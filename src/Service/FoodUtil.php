<?php

namespace App\Service;

use App\Util\Util;
use App\Entity\Food;
use App\Entity\Dish;
use App\Entity\UnitMeasure;
use App\Repository\FoodRepository;
use App\Repository\DishRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UnitMeasureRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FoodUtil
{
	public function __construct(
		private EntityManagerInterface $manager, 
		private Security $security,
		private FoodRepository $foodRepository,
		private DishRepository $dishRepository,
		private UnitMeasureRepository $unitMeasureRepository
	){}

	public function convertInGr(float $quantity, int|Food $food, int|string|UnitMeasure $unitMeasureObjectOrIdOrAlias)
	{
		if(!$food instanceof Food) {
			if (null === $food = $this->foodRepository->findOneById($food)) {
				throw new NotFoundHttpException(sprintf("Aucun aliment ne possède l' identifiant %s", $food));
			} 
		}
		
		if(!$unitMeasureObjectOrIdOrAlias instanceof UnitMeasure) {
			if (null === $unitMeasure = $this->unitMeasureRepository->findOneByAlias($unitMeasureObjectOrIdOrAlias)) {
				if (null === $unitMeasure = $this->unitMeasureRepository->findOneById($unitMeasureObjectOrIdOrAlias))
				{
					throw new NotFoundHttpException(sprintf("Aucune unité de mesure ne possède d'alias ou d'identifiant %s", $unitMeasureObjectOrIdOrAlias));
				}
			}
		}else{
			$unitMeasure = $unitMeasureObjectOrIdOrAlias;
		}

		if($unitMeasure->isIsUnit()) {
			return $food->getMedianWeight() * $quantity;
		}
		
		return $quantity * $unitMeasure->getGramRatio();
	}

	public function isForbidden($food)
	{
		/*foreach($this->user->getDiets()->toArray() as $diet)
		{
			dump(1);
			if($diet->getForbiddenFoods()->contains($food))
			{
				$forbidden = true;
			}
			if(!$forbidden)
			{
				if($diet->getForbiddenFoodGroups()->contains($food->getFoodGroup()))
				{
					$forbidden = true;
				}
			}
		}
		if(!$forbidden)
		{
			dump(2);

			foreach($this->user->getSubDiets()->toArray() as $subDiet)
			{
				if($subDiet->getForbiddenFoods()->contains($food))
				{
					$forbidden = true;
				}
				if(!$forbidden)
				{
					if($subDiet->getForbiddenFoodGroups()->contains($food->getFoodGroup()))
					{
						$forbidden = true;
					}
				}
			}
		}*/

		$user = $this->security->getUser();

		$forbidden = false;

		foreach($user->getForbiddenFoods() as $forbiddenFood)
		{
			if(
				$food->getId() == (int)$forbiddenFood->getId()
					|| 
				(null !== $food->getSubFoodGroup() && $food->getSubFoodGroup()->getId() == (int)$forbiddenFood->getId())
			)
			{
				$forbidden = true;

				break;
			}
		}
		
		if(!$forbidden) {
			foreach($user->getDiets()->toArray() as $diet) {
				if($food->getForbiddenDiets()->contains($diet) || $food->getFoodGroup()->getForbiddenDiets()->contains($diet)) {
					$forbidden = true;
	
					break;
				}
			}
		}

		// if(!$forbidden)
		// {
		// 	foreach($user->getDiets()->toArray() as $diet)
		// 	{
		// 		if(
		// 			$diet->getForbiddenFoods()->contains($food) 
		// 				|| 
		// 			$diet->getForbiddenFoods()->contains($food->getSubFoodGroup())
		// 		)
		// 		{
		// 			$forbidden = true;
		// 		}
		// 		if(!$forbidden)
		// 		{
		// 			if($diet->getForbiddenFoodGroups()->contains($food->getFoodGroup()))
		// 			{
		// 				$forbidden = true;
		// 			}
		// 		}
		// 	}
		// }
		// if(!$forbidden)
		// {
		// 	foreach($this->user->getSubDiets()->toArray() as $subDiet)
		// 	{
		// 		if(
		// 			$subDiet->getForbiddenFoods()->contains($food)
		// 				||
		// 			$subDiet->getForbiddenFoods()->contains($food->getSubFoodGroup())
		// 		)
		// 		{
		// 			$forbidden = true;
		// 		}
		// 		if(!$forbidden)
		// 		{
		// 			if($subDiet->getForbiddenFoodGroups()->contains($food->getFoodGroup()))
		// 			{
		// 				$forbidden = true;
		// 			}
		// 		}
		// 	}
		// }

		return $forbidden;
	}

	public function myFindByFgCodeExcludeForbidden($fgCode, $forbiddenFoods = [])
	{

		$results = [];

		foreach($this->manager->getRepository(Food::class)->myFindByFgCodeExcludeForbidden($fgCode, $forbiddenFoods) as $food)
		{
			if (!$this->isForbidden($food))
				$results[] = $food;
		}

		return $results;
	}

	public function myFindByFgpCodeExcludeForbidden($fgpCode, $forbiddenFoods = [])
	{

		$results = [];

		foreach($this->manager->getRepository(Food::class)->myFindByFgpCodeExcludeForbidden($fgpCode, $forbiddenFoods) as $food)
		{
			if (!$this->isForbidden($food))
				$results[] = $food;
		}

		return $results;
	}

	public function myFindByKeywordAndFGPExcludeForbidden($keyword = null, $fgplist = [], $typeSelectFgp = 'or', $sortAlpha = 'ASC', $forbiddenFoods = [], $offset = 0, $limit = 8)
	{
		$results = [];

		foreach($this->manager->getRepository(Food::class)->myFindByKeywordAndFGPExcludeForbidden($keyword, $fgplist, $typeSelectFgp , $sortAlpha, $forbiddenFoods, $offset, $limit) as $food)
		{
			if (!$this->isForbidden($food))
				$results[] = $food;
		}

		return $results;
	}

	public function myFindByKeywordAndFGAndLactoseAndGlutenExcludeForbidden($keyword, $fglist, $freeLactose, $freeGluten)
	{
		$results = [];

		foreach($this->foodRepository->myFindByKeywordAndFGAndLactoseAndGluten($keyword, $fglist, $freeLactose, $freeGluten) as $food)
		{
			if (!$this->isForbidden($food))
				$results[] = $food;
		}

		return $results;
	}

	// public function getQuantityNutrient(Food $food, int $quantityG)
	// {
	// 	/*
	// 		100g => $food->getProtein()
	// 		$quantityG => $qtyProtein?

	// 		$qtyProtein = ($qtyFoodG * $qtyProtein100g) / 100
	// 		etc
	// 	*/
	// 	$result['protein'] = ($quantityG * $food->getProtein()) / 100;
	// 	$result['lipid'] = ($quantityG * $food->getLipid()) / 100;
	// 	$result['carbohydrate'] = ($quantityG * $food->getCarbohydrate()) / 100;

	// 	return $result;
	// }

	public function getNutrientsForDishOrFoodSelected(int|Food|Dish $dishOrFood, $type, float $quantity, int|string|UnitMeasure|null $unitMeasureObjectOrIdOrAlias = null)
    {
        // $item example = [▼
        //     "type" => "Food"
        //     "id" => "680"
        //     "quantity" => "20"
        //     "measureUnit" => "93"
        //     "measureUnitAlias" => "ml"
        // ]

        /*
            Food:

            [▼
                "type" => "Food"
                "id" => "680"
                "quantity" => "20"
                "measureUnit" => "93"
                "measureUnitAlias" => "ml"
            ]

            Pour 100g                                  =>            $food->getEnergy()
            $item['quantity'] convertit en gramme      =>            energy à calculer

            energy = ($food->getEnergy() * (convertInGr($item['quantity'])) / 100

            Dish:

            [▼
                "type" => "Dish"
                "id" => "1"
                "quantity" => "1"
            ]

            Pour chaque aliment du plat ($dishOrFood->getDishFoods()->toArray() => $dishFood):

                $quantiteG = ($dishFood->getQuantityG() * quantity) / dish->getLengthPersonForRecipe()

                100g            =>      $food->getEnergy
                $quantiteG      =>      energy à calculer pour chaque aliment présent dans le plat

                energy = ($quantiteG * $food->getEnergy()) / 100

        */

        switch ($type)
        {
            case 'Food':

                if(!$dishOrFood instanceof Food) {
                    if (null === $dishOrFood = $this->foodRepository->findOneById($dishOrFood)) {
                        throw new NotFoundHttpException('Cet aliment n\'existe pas');
                    }
                }
                $quantityInGr = $this->convertInGr($quantity, $dishOrFood, $unitMeasureObjectOrIdOrAlias);
				$results['protein'] = ($quantityInGr * $dishOrFood->getNutritionalTable()->getProtein()) / 100;
				$results['lipid'] = ($quantityInGr * $dishOrFood->getNutritionalTable()->getLipid()) / 100;
				$results['carbohydrate'] = ($quantityInGr * $dishOrFood->getNutritionalTable()->getCarbohydrate()) / 100;
				$results['sodium'] = ($quantityInGr * $dishOrFood->getNutritionalTable()->getSalt()) / 100;

                return $results;

                break;

            case 'Dish':

                if(!$dishOrFood instanceof Dish) {
                    if (null === $dishOrFood = $this->dishRepository->findOneById($dishOrFood)) {
                        throw new NotFoundHttpException('Ce plat n\'existe pas');
                    }
                }

                $results['protein'] = 0;
				$results['lipid'] = 0;
				$results['carbohydrate'] = 0;
				$results['sodium'] = 0;

                foreach($dishOrFood->getDishFoods()->toArray() as $dishFood)
                {
                    $quantiteG = ($dishFood->getQuantityG() * $quantity) / $dishOrFood->getLengthPersonForRecipe();
                    $results['protein'] += ($quantiteG * $dishFood->getFood()->getNutritionalTable()->getProtein()) / 100;
                    $results['lipid'] += ($quantiteG * $dishFood->getFood()->getNutritionalTable()->getLipid()) / 100;
                    $results['carbohydrate'] += ($quantiteG * $dishFood->getFood()->getNutritionalTable()->getCarbohydrate()) / 100;
					$results['sodium'] += ($quantiteG * $dishFood->getFood()->getNutritionalTable()->getSalt()) / 100;
                }
 
                return $results;

                break;

            default:
			
                return null;
        }

    }
}