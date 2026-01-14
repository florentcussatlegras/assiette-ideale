<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\Food;
use App\Entity\FoodGroup\FoodGroupParent;
use App\Entity\Meal;
use App\Entity\MealModel;
use App\Entity\TypeMeal;
use App\Service\DishUtil;
use App\Service\FoodUtil;
use App\Service\QuantityTreatment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MenuFeature
{
	protected $manager;
	protected $user;

	public function __construct(EntityManagerInterface $manager, TokenStorageInterface $tokenStorage, DishUtil $dishUtil, FoodUtil $foodUtil, QuantityTreatment $quantityTreatment)
	{
		$this->manager = $manager;
		$this->user = $tokenStorage->getToken()->getUser();
		$this->dishUtil = $dishUtil;
		$this->foodUtil = $foodUtil;
		$this->quantityTreatment = $quantityTreatment;
		$this->fgpPrincipalCodes = $this->manager->getRepository(FoodGroupParent::class)->getAliasPrincipal();
	}

	// on créer un petit dejeuner à partir d'un modèle de petit déjeuner de l'utilisateur pris au hasard
	// c'est le point de départ de l'élaboration des repas proposés
	public function getRandomBreakfast($date)
	{
		$typeMealBreakfast = $this->manager->getRepository(TypeMeal::class)->findOneBy(['backName' => 'breakfast']);
		$breakfasts = $this->manager->getRepository(MealModel::class)->findBy(['type' => $typeMealBreakfast, 'user' => $this->user]);
		if(!empty($breakfasts)) {
			$breakfast = $breakfasts[array_rand($breakfasts)];
			$breakfast = new Meal('breakfast', 0, $date, $breakfast->getDishAndFoods(), $typeMealBreakfast, $this->user, null, null);
		}else{
			// TO DO
			// TROUVER UN MOYEN DE FAIRE UN PETIT DEJEUNER QUAND AUCUN MODELE N EXISTE 
			return null;
		}

		// $breakfast = $this->manager->getRepository(MealModel::class)->findOneById(5);

		return $breakfast;
	}

	public function getSnack($typeBackName, $quantitiesForMeal, $date, $foodsSelected, $dishesSelected)
	{
		$choices = ['Dish', 'Food'];
		shuffle($choices);
		$choice = $choices[0];
		$dishAndFoods = [];

		if('Dish' === $choice)
		{

			foreach($this->dishUtil->myFindByGroupAndQuantityRangeExcludeForbidden('FG_FRUIT_RAW', $quantitiesForMeal['FGP_FRUIT'] - 10, $quantitiesForMeal['FGP_FRUIT'] + 10, $this->user->getForbiddenFoods()) as $snack)
			{
				$quantities = $this->dishUtil->getFoodGroupParentQuantitiesForNPortion($snack, 1);
				$match = true;
				foreach($this->fgpPrincipalCodes as $fgpCode)
				{
					if(!(0 <= $quantities[$fgpCode] && $quantities[$fgpCode] <= $quantitiesForMeal[$fgpCode]))
					{
						$match = false;
						break;
					}
				}
				if($match && !in_array($snack->getId(), $dishesSelected))
					$snacks[] = [
						"type" => "Dish",
						  "id" => $snack->getId(),
				    "nPortion" => "1"
					];
			}

			if(!empty($snacks))
			{
				$dishAndFoods[] = $snack = $snacks[array_rand($snacks)];
				$dishesSelected[] = (int)$snack['id'];
			}

		}else{

			foreach($this->manager->getRepository(Food::class)->myFindByFgCodeExcludeForbidden('FG_FRUIT_RAW', $this->user->getForbiddenFoods()) as $fruit)
			{
				if(!in_array($fruit->getId(), $foodsSelected))
					$fruits[] = [
							"type" => "Food",
						      "id" => $fruit->getId(),
						"quantity" => $quantitiesForMeal['FGP_FRUIT'],
					 "measureUnit" => "58"
					];
			}

			if(!empty($fruits))
			{
				$dishAndFoods[] = $fruit = $fruits[array_rand($fruits)];
				$foodsSelected[] = (int)$fruit['id'];
			}

			foreach($this->manager->getRepository(Food::class)->myFindByFgCodeExcludeForbidden('FG_STARCHY', $this->user->getForbiddenFoods()) as $starchy)
			{
				if(!in_array($starchy->getId(), $foodsSelected))
					$starchies[] = [
							"type" => "Food",
						      "id" => $starchy->getId(),
						"quantity" => $quantitiesForMeal['FGP_STARCHY'],
					 "measureUnit" => "58"
					];
			}

			if(!empty($starchies))
			{
				$dishAndFoods[] = $starchy = $starchies[array_rand($starchies)];
				$foodsSelected[] = (int)$starchy['id'];
			}

		}

		return [
			 'meal' => new Meal($typeBackName, 1, $date, $dishAndFoods, $this->manager->getRepository(TypeMeal::class)->findOneByBackName($typeBackName), $this->user, null, null),
	'foodsSelected' => $foodsSelected,
   'dishesSelected' => $dishesSelected
		];

	}

	public function getMeal($typeBackName, $quantitiesForMeal, $date, $foodsSelected, $dishesSelected)
	{
		//ENTREE
		$qtyEntranceMax['FGP_VPO'] = $quantitiesForMeal['FGP_VPO']/3;
		$qtyEntranceMax['FGP_STARCHY'] = $quantitiesForMeal['FGP_STARCHY']/3;			
		$qtyEntranceMax['FGP_FRUIT'] = 0;
		$qtyEntranceMax['FGP_DAIRY'] = 10;
		$qtyEntranceMax['FGP_VEG'] = $quantitiesForMeal['FGP_VEG']/2;

		$entrances = [];

		foreach($this->dishUtil->myFindByGroupAndQuantityRangeExcludeForbidden('FG_VEG_RAW', 30, $qtyEntranceMax['FGP_VEG'], $this->user->getForbiddenFoods()) as $entrance)
		{
			$quantities = $this->dishUtil->getFoodGroupParentQuantitiesForNPortion($entrance, 1);
			$match = true;
			foreach($this->fgpPrincipalCodes as $fgpCode)
			{
				if(!(0 <= $quantities[$fgpCode] && $quantities[$fgpCode] <= $qtyEntranceMax[$fgpCode]))
				{
					$match = false;
					break;
				}
			}
			if($match && !in_array($entrance->getId(), $dishesSelected))
				$entrances[] = [
					"type" => "Dish",
					  "id" => $entrance->getId(),
			    "nPortion" => "1"
				];
		}

		foreach($this->manager->getRepository(Food::class)->myFindByFgCodeExcludeForbidden('FG_VEG_RAW', $this->user->getForbiddenFoods()) as $entrance)
		{
			if(!in_array($entrance->getId(), $foodsSelected))
				$entrances[] = [
						"type" => "Food",
					      "id" => $entrance->getId(),
					"quantity" => $quantitiesForMeal['FGP_VEG']/2,
				 "measureUnit" => "58"
				];
		}

		if(!empty($entrances))
		{
			$entrance = $entrances[array_rand($entrances)];
			
			if('Food' === $entrance['type'])
			{
				$foodsSelected[] = (int)$entrance['id'];
			}else{
				$dishesSelected[] = (int)$entrance['id'];
			}

			$quantitiesConsumed = $this->quantityTreatment->getQuantitiesConsumed($entrance, $this->quantityTreatment->getQuantitiesConsumedNull());
			foreach ($quantitiesConsumed as $fgpCode => $quantityConsumed) {
				$quantitiesForMeal[$fgpCode] -= $quantityConsumed;
			}

			$entrances = [$entrance];
		}

		
		//PLAT PRINCIPAL

		$mains = [];

		if('lunch' === $typeBackName)
		{
			//Pour le déjeuner, on a des VPO
			//On cherche d'abord un palt avec du VPO puis on complète en aliment Fec et legume cuit si besoin
			//Si pas de plat VPO avec les quantités cherchées on propose que des aliments séparés

			//PLATS

			$mainDishes = [];
			
			foreach($this->fgpPrincipalCodes as $fgpCode)
			{
				$qtyMainMin[$fgpCode] = $quantitiesForMeal[$fgpCode] - 10;
				$qtyMainMax[$fgpCode] = $quantitiesForMeal[$fgpCode] + 10;
			}

			$qtyMainMin['FGP_FRUIT'] = $qtyMainMax['FGP_FRUIT'] = 0;
			$qtyMainMin['FGP_DAIRY'] = 0;
			$qtyMainMax['FGP_DAIRY'] = 10;

			foreach($this->dishUtil->myFindAllExcludeForbidden($this->user->getForbiddenFoods()) as $main)
			{	
				$quantities = $this->dishUtil->getFoodGroupParentQuantitiesForNPortion($main, 1);

				$match = true;

				foreach(['FGP_FRUIT', 'FGP_DAIRY', 'FGP_VPO'] as $fgpCode)
				{
					if(!($qtyMainMin[$fgpCode] <= $quantities[$fgpCode] && $quantities[$fgpCode] <= $qtyMainMax[$fgpCode]))
					{
						$match = false;
						break;
					}
				}

				if($match && !in_array($main->getId(), $dishesSelected) && $quantities['FGP_STARCHY'] <= $qtyMainMax['FGP_STARCHY'] && $quantities['FGP_VEG'] <= $qtyMainMax['FGP_STARCHY'])
				{
					$mainDishes[] = $main;
				}
			}

			if(!empty($mainDishes)){

				$main = $mainDishes[array_rand($mainDishes)];

				$mains[] = [
					"type" => "Dish",
					  "id" => $main->getId(),
			    "nPortion" => "1"
				];

				$quantities = $this->dishUtil->getFoodGroupParentQuantitiesForNPortion($main, 1);

				if($quantities['FGP_VEG'] < $qtyMainMin['FGP_VEG'])
				{
					$cookedVegs = $this->foodUtil->myFindByFgCodeExcludeForbidden('FG_VEG_COOKED', $this->user->getForbiddenFoods());
					if(!empty($cookedVegs))
					{
						$cookedVeg = $cookedVegs[array_rand($cookedVegs)];
						$mains[] = [
							 "type" => "Food",
							   "id" => $cookedVeg->getId(),
						 "quantity" => $quantitiesForMeal['FGP_VEG'] - $quantities['FGP_VEG'],
					  "measureUnit" => "58"
						];
						$foodsSelected[] = $cookedVeg->getId();
					}
				}

				if($quantities['FGP_STARCHY'] < $qtyMainMin['FGP_STARCHY'])
				{
					$starchies = $this->foodUtil->myFindByFgCodeExcludeForbidden('FG_STARCHY', $this->user->getForbiddenFoods());
					if(!empty($starchies))
					{
						$starchy = $starchies[array_rand($starchies)];
						$mains[] = [
							 "type" => "Food",
							   "id" => $starchy->getId(),
						 "quantity" => $quantitiesForMeal['FGP_STARCHY'] - $quantities['FGP_STARCHY'],
					  "measureUnit" => "58"
						];
						$dishesSelected[] = $starchy->getId();
					}
				}	

			}else{

				//ALIMENTS

				foreach($this->fgpPrincipalCodes as $fgpCode)
				{
					if('FGP_DAIRY' !== $fgpCode && 'FGP_FRUIT' !== $fgpCode && $quantitiesForMeal[$fgpCode] != 0)
					{
						$mainFoods = [];

						$foods = $this->foodUtil->myFindByFgpCodeExcludeForbidden($fgpCode, $this->user->getForbiddenFoods());
						dump($fgpCode);
						dump($foods);

						foreach ($foods as $food) {

							if('FG_VEG_RAW' !== $food->getFoodGroup()->getCode() && !in_array($food->getId(), $foodsSelected))
							{
								$mainFoods[] = [
										"type" => "Food",
									      "id" => $food->getId(),
									"quantity" => $quantitiesForMeal[$fgpCode],
								 "measureUnit" => "58"
								];
							}

						}

						if(!(empty($mainFoods))){
							$main = $mainFoods[array_rand($mainFoods)];
							$foodsSelected[] = $main['id'];
							$mains[] = $main;
						}

					}
				}

			}

		}else{

			//ALIMENTS

			foreach($this->fgpPrincipalCodes as $fgpCode)
			{
				if('FGP_DAIRY' !== $fgpCode && 'FGP_FRUIT' !== $fgpCode && $quantitiesForMeal[$fgpCode] != 0)
				{
					$mainFoods = [];

					$foods = $this->manager->getRepository(Food::class)->myFindByFgpCodeExcludeForbidden($fgpCode, $this->user->getForbiddenFoods());

					foreach ($foods as $food) {

						if('FG_VEG_RAW' !== $food->getFoodGroup()->getCode() && !in_array($food->getId(), $foodsSelected))
						{
							$mainFoods[] = [
									"type" => "Food",
								      "id" => $food->getId(),
								"quantity" => $quantitiesForMeal[$fgpCode],
							 "measureUnit" => "58"
							];
						}

					}

					if(!(empty($mainFoods))){
						$main = $mainFoods[array_rand($mainFoods)];
						$foodsSelected[] = $main['id'];
						$mains[] = $main;
					}

				}
			}

		}

		$quantitiesConsumed = $this->quantityTreatment->getQuantitiesConsumedNull();
		foreach($mains as $main)
		{
			$quantitiesConsumed = $this->quantityTreatment->getQuantitiesConsumed($main, $quantitiesConsumed);
		}
		
		foreach ($quantitiesConsumed as $fgpCode => $quantityConsumed) {
			$quantitiesForMeal[$fgpCode] -= $quantityConsumed;
		}


		//FROMAGE OU DESSERT

		//1 FROMAGE

		$cheese = $milk = $fruit = $cheeseAndDesserts = null;
		$cheeses = $this->manager->getRepository(Food::class)->myFindByFgCodeExcludeForbidden('FG_CHEESE', $this->user->getForbiddenFoods());
		foreach ($cheeses as $cheeseItem) {
			if(!in_array($cheeseItem->getId(), $foodsSelected))
				$list[] = [
						"type" => "Food",
					      "id" => $cheeseItem->getId(),
					"quantity" => $quantitiesForMeal['FGP_DAIRY'],
				 "measureUnit" => "58"
				];
		}
		if(!empty($list))
		{
			$cheese = $list[array_rand($list)];
			$list = [];
		}

		//1 FRUIT
		$fruits = $this->manager->getRepository(Food::class)->myFindByFgpCodeExcludeForbidden('FGP_FRUIT', $this->user->getForbiddenFoods());
		foreach ($fruits as $fruitItem) {
			if(!in_array($fruitItem->getId(), $foodsSelected))
				$list[] = [
						"type" => "Food",
					      "id" => $fruitItem->getId(),
					"quantity" => $quantitiesForMeal['FGP_FRUIT'],
				 "measureUnit" => "58"
				];
		}
		if(!empty($list))
		{
			$fruit = $list[array_rand($list)];
			$list = [];
		}

		//1 LAITAGE
		$milks = $this->manager->getRepository(Food::class)->myFindByFgCodeExcludeForbidden('FG_MILK', $this->user->getForbiddenFoods());
		foreach ($milks as $milkItem) {
			if(!in_array($milkItem->getId(), $foodsSelected))
				$list[] = [
						"type" => "Food",
					      "id" => $milkItem->getId(),
					"quantity" => $quantitiesForMeal['FGP_DAIRY'],
				 "measureUnit" => "58"
				];
		}
		if(!empty($list))
		{
			$milk = $list[array_rand($list)];
			$list = [];
		}

		//1 PLAT AVEC FRUIT ET LAITAGE

		$milkAndFruit = [];
		foreach($this->dishUtil->myFindByGroupAndQuantityRangeExcludeForbidden('FG_MILK', $quantitiesForMeal['FGP_DAIRY'] - 10, $quantitiesForMeal['FGP_DAIRY'] + 10, $this->user->getForbiddenFoods()) as $dessert)
		{
			$quantities = $this->dishUtil->getFoodGroupParentQuantitiesForNPortion($dessert, 1);
			if(($quantitiesForMeal['FGP_DAIRY'] - 10) <= $quantities['FGP_FRUIT'] && $quantities['FGP_FRUIT'] <= ($quantitiesForMeal['FGP_DAIRY'] - 10))
			{
				if(!in_array($dessert->getId(), $dishesSelected))
					$list[] = [
						"type" => "Dish",
						  "id" => $dessert->getId(),
				    "nPortion" => "1"
					];
			}
		}

		if(!empty($list))
		{
			$milkAndFruit[] = $list[array_rand($list)];
			$list = [];
		}	

		if(null !== $cheese)
		{
			if($quantitiesForMeal['FGP_DAIRY'] > 0)
			{	
				$cheeseAndDesserts[] = $cheese;
				$foodsSelected[] = $cheese['id'];
			}

			if($quantitiesForMeal['FGP_FRUIT'] > 0)
			{	
				$cheeseAndDesserts[] = $fruit;
				$foodsSelected[] = $fruit['id'];
			}

		}else{

			if($quantitiesForMeal['FGP_DAIRY'] > 0 && $quantitiesForMeal['FGP_FRUIT'] > 0){

				$choices = ['dish', 'food'];
				shuffle($choices);
				$choice = $choices[0];

				if('dish' == $choice && !empty($milkAndFruit))
				{
					$cheeseAndDesserts[] = $milkAndFruit;
					$dishesSelected[] = $milkAndFruit['id'];
				}else{
					if(!empty($milk))
					{
						$cheeseAndDesserts[] = $milk;
						$foodsSelected[] = $milk['id'];
					}
					if(!empty($fruit))
					{
						$cheeseAndDesserts[] = $fruit;
						$foodsSelected[] = $fruit['id'];
					}
				}

			}elseif($quantitiesForMeal['FGP_DAIRY'] <= 0 && $quantitiesForMeal['FGP_FRUIT'] > 0 && !empty($fruit)){

				$cheeseAndDesserts[] = $fruit;
				$foodsSelected[] = $fruit['id'];

			}elseif($quantitiesForMeal['FGP_DAIRY'] > 0 && $quantitiesForMeal['FGP_FRUIT'] <= 0 && !empty($milk)){

				$cheeseAndDesserts[] = $milk;
				$foodsSelected[] = $milk['id'];

			}

		}

		if(!empty($cheeseAndDessert))
		{		
			$quantitiesConsumed = $this->quantityTreatment->getQuantitiesConsumedNull();
			foreach($cheeseAndDesserts as $cheeseAndDessert)
			{
				$quantitiesConsumed = $this->quantityTreatment->getQuantitiesConsumed($cheeseAndDessert, $quantitiesConsumed);
			}
			foreach ($quantitiesConsumed as $fgpCode => $quantityConsumed) {
				$quantitiesForMeal[$fgpCode] -= $quantityConsumed;
			}
		}
		// dd($quantitiesForLunch);

		$dishAndFoods = array_merge($entrances, $mains, $cheeseAndDesserts);
		// dd($dishAndFoods);

		return [
			 'meal' => new Meal($typeBackName, 1, $date, $dishAndFoods, $this->manager->getRepository(TypeMeal::class)->findOneByBackName($typeBackName), $this->user, null, null),
	'foodsSelected' => $foodsSelected,
   'dishesSelected' => $dishesSelected
		];
	}
}