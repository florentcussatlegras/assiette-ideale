<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\DishFoodGroup;
use App\Entity\Food;
use App\Entity\FoodGroup\FoodGroup;
use App\Entity\FoodGroup\FoodGroupParent;
use App\Repository\FoodRepository;
use App\Repository\DishRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Service\FoodUtil;
use App\Service\BalanceSheetFeature;

class DishUtil
{
	public function __construct(
		private EntityManagerInterface $manager, 
		private TokenStorageInterface $tokenStorage, 
		private FoodUtil $foodUtil,
		private DishRepository $dishRepository)
	{}

	public function getFoodGroupParentQuantitiesForNPortion(Dish $dish, $nPortion)
	{
		foreach ($this->manager->getRepository(FoodGroupParent::class)->findAll() as $fgp) {

			$quantities[$fgp->getAlias()] = 0;

			foreach($this->manager->getRepository(DishFoodGroup::class)->findByDishAndFoodGroupParent($dish, $fgp) as $dfg) {

				$quantities[$fgp->getAlias()] += $dfg->getQuantityForOne();
			
			}

			$quantities[$fgp->getAlias()] *= $nPortion;
		}

		return $quantities;
	}

	public function getByQuantityFoodGroupParent($dishes, $fgpAlias, $qtyMin, $qtyMax)
	{
		$nPortion = 1;
		$results = [];

		foreach($dishes as $dish){

			$quantity = $this->getFoodGroupParentQuantitiesForNPortion($dish, $nPortion)[$fgpAlias];

			if($quantity >= $qtyMin &&  $quantity <= $qtyMax)
			{
				$results[] = $dish;
			}

		}

		return $results;
	}

	public function isForbidden($dish)
	{
		$forbidden = false;

		foreach($dish->getDishFoods()->toArray() as $dishFood)
		{
			if(true === $forbidden = $this->foodUtil->isForbidden($dishFood->getFood())) 
				break;

			/*foreach($this->user->getForbiddenFoods() as $forbiddenFood)
			{
				if(
					$dishFood->getFood()->getId() == (int)$forbiddenFood 
						|| 
					(null !== $dishFood->getFood()->getSubFoodGroup() && $dishFood->getFood()->getSubFoodGroup()->getId() == (int)$forbiddenFood)
				)
				{
					$forbidden = true;
				}
			}
			if(!$forbidden)
			{
				foreach($this->user->getDiets()->toArray() as $diet)
				{
					if($diet->getSubDiets()->isEmpty())
					{
						if(
							$diet->getForbiddenFoods()->contains($dishFood->getFood()) 
								|| 
							$diet->getForbiddenFoods()->contains($dishFood->getFood()->getSubFoodGroup())
						)
						{
							$forbidden = true;
						}
						if(!$forbidden)
						{
							if($diet->getForbiddenFoodGroups()->contains($dishFood->getFood()->getFoodGroup()))
							{
								$forbidden = true;
							}
						}
					}
				}
			}
			if(!$forbidden)
			{
				foreach($this->user->getSubDiets()->toArray() as $subDiet)
				{
					if(
						$subDiet->getForbiddenFoods()->contains($dishFood->getFood())
							||
						$subDiet->getForbiddenFoods()->contains($dishFood->getFood()->getSubFoodGroup())
					)
					{
						$forbidden = true;
					}
					if(!$forbidden)
					{
						if($subDiet->getForbiddenFoodGroups()->contains($dishFood->getFood()->getFoodGroup()))
						{
							$forbidden = true;
						}
					}
				}
			}*/
		}

		return $forbidden;
	}

	public function myFindByKeywordAndFGPExcludeFordidden($keyword, $fgp, $offset, $limit)
	{
		$results = [];
	
		foreach($this->manager->getRepository(Dish::class)->myFindByKeywordAndFGP($keyword, $fgp, $offset, $limit) as $dish)
		{
			if (!$this->isForbidden($dish))
				$results[] = $dish;
		}

		return $results;
	}

	public function myFindByKeywordAndFGAndTypeAndLactoseAndGlutenExcludeForbidden($keyword, $fglist, $freeLactose, $freeGluten)
	{
		$results = [];
	
		// foreach($this->manager->getRepository(Dish::class)->myFindByKeywordAndFG($keyword, $fglist, $sortAlpha) as $dish)
		// foreach($this->manager->getRepository(Dish::class)->myFindByKeywordAndFGAndTypeAndLactoseAndGluten($keyword, $fglist) as $dish)
		foreach($this->dishRepository->myFindByKeywordAndFGAndTypeAndLactoseAndGluten($keyword, $fglist, $freeLactose, $freeGluten) as $dish)
		{
			if (!$this->isForbidden($dish))
				$results[] = $dish;
		}

		return $results;
	}

	public function myFindAllExcludeForbidden()
	{
		$results = [];

		foreach ($this->manager->getRepository(Dish::class)->findAll() as $dish) 
		{
			if (!$this->isForbidden($dish))
				$results[] = $dish;
		}

		return $results;
	}

	public function myFindByGroupAndQuantityRangeExcludeForbidden($fgpAlias, $qtyMin, $qtyMax)
	{
		$results = [];

		foreach ($this->manager->getRepository(Dish::class)->myFindByGroupAndQuantityRange($fgpAlias, $qtyMin, $qtyMax) as $dish) 
		{
			if (!$this->isForbidden($dish))
				$results[] = $dish;
		}

		return $results;
	}
	
	public function orderObjectByName($a, $b)
	{
		if ($a->getname() == $b->getName()) {
        return 0;
	    } else if ($a->getName() < $b->getName()) {//retourner -1 en cas d’infériorité
	        return -1;
	    } else {//retourner 1 en cas de supériorité
	        return 1;
	    }
    }
}