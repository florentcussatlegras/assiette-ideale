<?php

namespace App\Service;

use App\Repository\DishRepository;
use App\Repository\FoodRepository;
use App\Entity\Dish;
use App\Entity\Food;
use App\Service\DishUtil;
use App\Service\FoodUtil;
use App\Entity\Alert\Alert;
use App\Entity\Alert\LevelAlert;
use App\Entity\UnitMeasure;
use App\Service\EnergyHandler;
use App\Service\QuantityTreatment;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\FoodGroup\FoodGroupParent;
use App\Repository\ImcMessageRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class AlertFeature
{
	public function __construct(
		private RequestStack $requestStack, 
		private EntityManagerInterface $manager, 
		private DishUtil $dishUtil, 
		private FoodUtil $foodUtil, 
		private QuantityTreatment $quantityTreatment, 
		private Security $security,
		private EnergyHandler $energyHandler,
		private DishRepository $dishRepository,
		private FoodRepository $foodRepository,
		private ImcMessageRepository $imcMessageRepository,
		private TranslatorInterface $translator,
	){}


	/************* ALERTES DES PLATS/FOODS DE LA LISTE DES REPAS ************/

	public function setAlertOnDishesAndFoodsAlreadySelected()
	{
		$session = $this->requestStack->getSession();

		$fgpQuantitiesConsumed = $this->quantityTreatment->getQuantitiesConsumedNull();
		$energyConsumed = $quantityProteinConsumed = $quantityLipidConsumed = $quantityCarbohydrateConsumed = $quantitySodiumConsumed = 0;

		$alertDishes = [];
		$alertFoods = [];
		$finalListAlerts = [];

		for($n = 0; $n <= $session->get('_meal_day_range'); $n++)
		{
			if($session->has('_meal_day_' . $n) && array_key_exists('dishAndFoods', $session->get('_meal_day_' . $n)))
			{
				foreach ($session->get('_meal_day_' . $n)['dishAndFoods'] as $index => $element) 
				{
					if('Dish' === $element['type'] || 'dish' === $element['type'])
					{
						$dish = $this->manager->getRepository(Dish::class)->findOneById((int)$element['id']);

						// ALERTES SUR LES GROUPES PARENTS D'ALIMENTS

						$fgpQuantitiesForNPortion = $this->dishUtil->getFoodGroupParentQuantitiesForNPortion($dish, $element['quantity']);
						if(null !== $listAlertFgp = $this->getAlerts('food_group_parent', $dish, $fgpQuantitiesConsumed, $element['quantity'], $fgpQuantitiesForNPortion, $element['unitMeasureAlias'], $finalListAlerts)) {
							$alertDishes[$n][$index]["food_group_parent"] = $listAlertFgp;
						}

						// ALERTES SUR LES PROTEINES

						$quantityProtein = $this->extractDataFromDishOrFoodSelected('protein', $dish, $element['quantity']);
						if(null !== $listAlertProtein = $this->getAlerts('protein', $dish, $quantityProteinConsumed, $quantityProtein, null, null, $finalListAlerts)) {
							$alertDishes[$n][$index]["protein"] = $listAlertProtein;
						}
					
						// ALERTES SUR LES LIPIDES

						$quantityLipid = $this->extractDataFromDishOrFoodSelected('lipid', $dish, $element['quantity']);
						if(null !== $listAlertLipid = $this->getAlerts('lipid', $dish, $quantityLipidConsumed, $quantityLipid, null, null, $finalListAlerts)) {
							$alertDishes[$n][$index]["lipid"] = $listAlertLipid;
						}
						
						// ALERTES SUR LES GLUCIDES

						$quantityCarbohydrate = $this->extractDataFromDishOrFoodSelected('carbohydrate', $dish, $element['quantity']);
						if(null !== $listAlertCarbohydrate = $this->getAlerts('carbohydrate', $dish, $quantityCarbohydrateConsumed, $quantityCarbohydrate, null, null, $finalListAlerts)) {
							$alertDishes[$n][$index]["carbohydrate"] = $listAlertCarbohydrate;
						}

						// ALERTES SUR LE SODIUM

						$quantitySodium = $this->extractDataFromDishOrFoodSelected('sodium', $dish, $element['quantity']);
						if(null !== $listAlertSodium = $this->getAlerts('sodium', $dish, $quantitySodiumConsumed, $quantitySodium, null, null, $finalListAlerts)) {
							$alertDishes[$n][$index]["sodium"] = $listAlertSodium;
						}

						// ALERTE SUR L'ENERGIE DEPENSEE

						$energyDish = $this->extractDataFromDishOrFoodSelected('energy', $dish, $element['quantity']);
						if(null !== $listAlertEnergy = $this->getAlerts('energy', $dish, $energyConsumed, $energyDish, null, null, $finalListAlerts)) {
							$alertDishes[$n][$index]["energy"] = $listAlertEnergy;
						}

						if (isset($alertDishes[$n][$index])) {
							$alertDishes[$n][$index] = $this->compileAlertsInformation($alertDishes[$n][$index]);
						}


						// On met à jour les quantités consommées de chaque catégorie

						foreach($fgpQuantitiesForNPortion as $fgpAlias => $quantity)
						{
							array_key_exists($fgpAlias, $fgpQuantitiesConsumed) ? $fgpQuantitiesConsumed[$fgpAlias] += $quantity : $fgpQuantitiesConsumed[$fgpAlias] = $quantity;
						}

						$energyConsumed += $energyDish;

					}

					if('Food' === $element['type'] || 'food' === $element['type'])
					{
						$food = $this->manager->getRepository(Food::class)->findOneById((int)$element['id']);
	
						// ALERTES SUR LES GROUPES PARENTS D'ALIMENTS

						$fgpAlias = $food->getFoodGroup()->getParent()->getAlias();

						if(null !== $listAlertFgp = $this->getAlerts('food_group_parent', $food, $fgpQuantitiesConsumed, (float)$element['quantity'], null, (string)$element['unitMeasureAlias'], $finalListAlerts)) {
							$alertFoods[$n][$index]["food_group_parent"] = $listAlertFgp;
						}

						// ALERTES SUR LES PROTEINES

						$quantityProtein = $this->extractDataFromDishOrFoodSelected('protein', $food, $element['quantity'], $element['unitMeasureAlias']);
						if(null !== $listAlertProtein = $this->getAlerts('protein', $food, $quantityProteinConsumed, $quantityProtein, null, null, $finalListAlerts)) {
							$alertFoods[$n][$index]["protein"] = $listAlertProtein;
						}
					
						// ALERTES SUR LES LIPIDES

						$quantityLipid = $this->extractDataFromDishOrFoodSelected('lipid', $food, $element['quantity'], $element['unitMeasureAlias']);
						if(null !== $listAlertLipid = $this->getAlerts('lipid', $food, $quantityLipidConsumed, $quantityLipid, null, null, $finalListAlerts)) {
							$alertFoods[$n][$index]["lipid"] = $listAlertLipid;
						}
						
						// ALERTES SUR LES GLUCIDES

						$quantityCarbohydrate = $this->extractDataFromDishOrFoodSelected('carbohydrate', $food, $element['quantity'], $element['unitMeasureAlias']);
						if(null !== $listAlertCarbohydrate = $this->getAlerts('carbohydrate', $food, $quantityCarbohydrateConsumed, $quantityCarbohydrate, null, null, $finalListAlerts)) {
							$alertFoods[$n][$index]["carbohydrate"] = $listAlertCarbohydrate;
						}

						// ALERTES SUR LE SODIUM
						
						$quantitySodium = $this->extractDataFromDishOrFoodSelected('sodium', $food, $element['quantity'], $element['unitMeasureAlias']);
						if(null !== $listAlertSodium = $this->getAlerts('sodium', $food, $quantitySodiumConsumed, $quantitySodium, null, null, $finalListAlerts)) {
							$alertFoods[$n][$index]["sodium"] = $listAlertSodium;
						}

						// ALERTE SUR L'ENERGIE DEPENSEE

						$energyFood = $this->extractDataFromDishOrFoodSelected('energy', $food, (float)$element['quantity'], (string)$element['unitMeasureAlias']);

						if(null !== $listAlertEnergy = $this->getAlerts('energy', $food, $energyConsumed, $energyFood, null, null, $finalListAlerts)) {
							$alertFoods[$n][$index]["energy"] = $listAlertEnergy;
						}
						
						if (isset($alertFoods[$n][$index])) {
							$alertFoods[$n][$index] = $this->compileAlertsInformation($alertFoods[$n][$index]);
						}
						
						// On met à jour les quantités consommées de chaque groupe d'aliment
						$quantityInGr = $this->foodUtil->convertInGr((float)$element['quantity'], $food, (string)$element['unitMeasureAlias']);
						array_key_exists($fgpAlias, $fgpQuantitiesConsumed) ? $fgpQuantitiesConsumed[$fgpAlias] += $quantityInGr : $fgpQuantitiesConsumed[$fgpAlias] = $quantityInGr;
						
						$energyConsumed += $energyFood;
					}

					$quantityProteinConsumed += $quantityProtein; 
					$quantityLipidConsumed += $quantityLipid; 
					$quantityCarbohydrateConsumed += $quantityCarbohydrate; 
					$quantitySodiumConsumed += $quantitySodium; 

				}
			}
		}

		// dd($alertDishes, $alertFoods);
		
		$session->set('_meal_day_alerts/_final_list', $finalListAlerts);
		$session->set('_meal_day_alerts/_dishes_selected', $alertDishes);
		$session->set('_meal_day_alerts/_foods_selected', $alertFoods);
	}

	/********** ALERTES DES PLATS/FOODS DE LA LISTE DE LA FENETRE MODALE ***********/

	public function setAlertOnDishesAndFoodsAboutTobeSelected($rankMeal = null, $rankDish = null)
	{
		$rankMeal = (int)$rankMeal;
		$rankDish = (int)$rankDish;

		$session = $this->requestStack->getSession();

		$alertDishes = $alertFoods = [];
		$fgpQuantitiesConsumed = $this->quantityTreatment->getQuantitiesConsumedInSessionDishes($rankMeal, $rankDish);

		// $energyConsumed = ($session->has('_meal_day_energy_evolution/energy') && isset($session->get('_meal_day_energy_evolution/energy')[$rankMeal][$rankDish]))
		// 			? $session->get('_meal_day_energy_evolution/energy')[$rankMeal][$rankDish] : 0;


		// $proteinConsumed = ($session->has('_meal_day_energy_evolution/protein') && isset($session->get('_meal_day_energy_evolution/protein')[$rankMeal][$rankDish])) 
		// 			? $session->get('_meal_day_energy_evolution/protein')[$rankMeal][$rankDish] : 0;

		// $lipidConsumed = ($session->has('_meal_day_energy_evolution/lipid') && isset($session->get('_meal_day_energy_evolution/lipid')[$rankMeal][$rankDish])) 
		// 			? $session->get('_meal_day_energy_evolution/lipid')[$rankMeal][$rankDish] : 0;

		// $carbohydrateConsumed = ($session->has('_meal_day_energy_evolution/carbohydrate') && isset($session->get('_meal_day_energy_evolution/carbohydrate')[$rankMeal][$rankDish])) 
		// 			? $session->get('_meal_day_energy_evolution/carbohydrate')[$rankMeal][$rankDish] : 0;

		// $sodiumConsumed = ($session->has('_meal_day_energy_evolution/sodium') && isset($session->get('_meal_day_energy_evolution/sodium')[$rankMeal][$rankDish])) 
		// 			? $session->get('_meal_day_energy_evolution/sodium')[$rankMeal][$rankDish] : 0;

		$energyConsumed = ($session->has('_meal_day_evolution/energy') && isset($session->get('_meal_day_evolution/energy')[$rankMeal][$rankDish]))
					? $session->get('_meal_day_evolution/energy')[$rankMeal][$rankDish] : 0;

		$proteinConsumed = ($session->has('_meal_day_evolution/protein') && isset($session->get('_meal_day_evolution/protein')[$rankMeal][$rankDish])) 
					? $session->get('_meal_day_evolution/protein')[$rankMeal][$rankDish] : 0;

		$lipidConsumed = ($session->has('_meal_day_evolution/lipid') && isset($session->get('_meal_day_evolution/lipid')[$rankMeal][$rankDish])) 
					? $session->get('_meal_day_evolution/lipid')[$rankMeal][$rankDish] : 0;

		$carbohydrateConsumed = ($session->has('_meal_day_evolution/carbohydrate') && isset($session->get('_meal_day_evolution/carbohydrate')[$rankMeal][$rankDish])) 
					? $session->get('_meal_day_evolution/carbohydrate')[$rankMeal][$rankDish] : 0;

		$sodiumConsumed = ($session->has('_meal_day_evolution/sodium') && isset($session->get('_meal_day_evolution/sodium')[$rankMeal][$rankDish])) 
					? $session->get('_meal_day_evolution/sodium')[$rankMeal][$rankDish] : 0;


		// ALERTES SUR LES PLATS
		
		foreach ($this->manager->getRepository(Dish::class)->findAll() as $dish) 
		{
			// ALERTES SUR FOODGROUP
			$fgpQuantitiesForNPortion = $this->dishUtil->getFoodGroupParentQuantitiesForNPortion($dish, 1);

			if(null !== $listAlertFgp = $this->getAlerts('food_group_parent', $dish, $fgpQuantitiesConsumed, 1, $fgpQuantitiesForNPortion)){
				$alertDishes[$dish->getId()]["food_group_parent"] = $listAlertFgp;
			}

			// ALERTES SUR LES PROTEINES
			$proteinDish = $this->extractDataFromDishOrFoodSelected('protein', $dish, 1);
			if(null !== $listAlertProtein = $this->getAlerts('protein', $dish, $proteinConsumed, $proteinDish)) {
				$alertDishes[$dish->getId()]["protein"] = $listAlertProtein;
			}

			// ALERTES SUR LES LIPIDES
			$lipidDish = $this->extractDataFromDishOrFoodSelected('lipid', $dish, 1);
			if(null !== $listAlertlipid = $this->getAlerts('protein', $dish, $lipidConsumed, $lipidDish)) {
				$alertDishes[$dish->getId()]["lipid"] = $listAlertlipid;
			}

			// ALERTES SUR LES GLUCIDES
			$carbohydrateDish = $this->extractDataFromDishOrFoodSelected('carbohydrate', $dish, 1);
			if(null !== $listAlertCarbohydrate = $this->getAlerts('carbohydrate', $dish, $carbohydrateConsumed, $carbohydrateDish)) {
				$alertDishes[$dish->getId()]["carbohydrate"] = $listAlertCarbohydrate;
			}

			// ALERTES SUR LE SODIUM
			$sodiumDish = $this->extractDataFromDishOrFoodSelected('sodium', $dish, 1);
			if(null !== $listAlertSodium = $this->getAlerts('sodium', $dish, $sodiumConsumed, $sodiumDish)) {
				$alertDishes[$dish->getId()]["sodium"] = $listAlertSodium;
			}

			// ALERTES SUR ENERGY
			$energyDish = $this->extractDataFromDishOrFoodSelected('energy', $dish, 1);
			
			if(null !== $listAlertEnergy = $this->getAlerts('energy', $dish, $energyConsumed, $energyDish)) {
				$alertDishes[$dish->getId()]["energy"] = $listAlertEnergy;
			}

			if (array_key_exists($dish->getId(), $alertDishes)) {
				$alertDishes[$dish->getId()] = $this->compileAlertsInformation($alertDishes[$dish->getId()]);
			}
		}
		// exit;

		$session->set('_meal_day_alerts/_dishes_not_selected', $alertDishes);



		// ALERTES SUR LES ALIMENTS

		// $unitMeasureGr = $this->manager->getRepository(UnitMeasure::class)->findOneByAlias('g');
		foreach ($this->manager->getRepository(Food::class)->findAll() as $food) 
		{										
			// dd($this->getAlerts('food_group_parent', $food, $fgpQuantitiesConsumed, 1, null, 'mg'));

			// ALERTES SUR FOODGROUP
			if(null !== $listAlertFgp = $this->getAlerts('food_group_parent', $food, $fgpQuantitiesConsumed, 1, null, 'mg')) {
				$alertFoods[$food->getId()]["food_group_parent"] = $listAlertFgp;
			}

			// ALERTES SUR L'ENERGIE
			$energyFood = $this->extractDataFromDishOrFoodSelected('energy', $food, 1, 'mg');
			if(null !== $listAlertEnergy = $this->getAlerts('energy', $food, $energyConsumed, $energyFood)) {
				$alertFoods[$food->getId()]["energy"] = $listAlertEnergy;
			}

			// ALERTS SUR PROTEINES
			$proteinFood = $this->extractDataFromDishOrFoodSelected('protein', $food, 1, 'mg');
			if(null !== $listAlertProtein = $this->getAlerts('protein', $food, $proteinConsumed, $proteinFood)) {
				$alertFoods[$food->getId()]["protein"] = $listAlertProtein;
			}

			// ALERTS SUR LIPIDES
			$lipidFood = $this->extractDataFromDishOrFoodSelected('lipid', $food, 1, 'mg');
			if(null !== $listAlertLipid = $this->getAlerts('lipid', $food, $lipidConsumed, $lipidFood)) {
				$alertFoods[$food->getId()]["lipid"] = $listAlertLipid;
			}

			// ALERTS SUR GLUCIDES
			$carbohydrateFood = $this->extractDataFromDishOrFoodSelected('carbohydrate', $food, 1, 'mg');
			if(null !== $listAlertCarbohydrate = $this->getAlerts('carbohydrate', $food, $carbohydrateConsumed, $carbohydrateFood)) {
				$alertFoods[$food->getId()]["carbohydrate"] = $listAlertCarbohydrate;
			}

			// ALERTS SUR SODIUM
			$sodiumFood = $this->extractDataFromDishOrFoodSelected('sodium', $food, 1, 'mg');
			if(null !== $listAlertSodium = $this->getAlerts('sodium', $food, $sodiumConsumed, $sodiumFood)) {
				$alertFoods[$food->getId()]["sodium"] = $listAlertSodium;
			}

			if (array_key_exists($food->getId(), $alertFoods)) {
				$alertFoods[$food->getId()] = $this->compileAlertsInformation($alertFoods[$food->getId()]);
			}
		}

		$session->set('_meal_day_alerts/_foods_not_selected', $alertFoods);
	}

	public function setAlertOnDishOrFoodQuantityUpdated(Dish|Food|null $object = null, float $quantityOrPortion, null|int $unitMeasureId = null, $rankMeal = null, $rankDish = null)
	{
		$session = $this->requestStack->getSession();

		$fgpQuantitiesConsumed = $this->quantityTreatment->getQuantitiesConsumedInSessionDishes($rankMeal, $rankDish);
	
		$energyConsumed = ($session->has('_meal_day_evolution/energy') && isset($session->get('_meal_day_evolution/energy')[$rankMeal][$rankDish]))
					? $session->get('_meal_day_evolution/energy')[$rankMeal][$rankDish] : 0;

		$proteinConsumed = ($session->has('_meal_day_evolution/protein') && isset($session->get('_meal_day_evolution/protein')[$rankMeal][$rankDish])) 
					? $session->get('_meal_day_evolution/protein')[$rankMeal][$rankDish] : 0;

		$lipidConsumed = ($session->has('_meal_day_evolution/lipid') && isset($session->get('_meal_day_evolution/lipid')[$rankMeal][$rankDish])) 
					? $session->get('_meal_day_evolution/lipid')[$rankMeal][$rankDish] : 0;

		$carbohydrateConsumed = ($session->has('_meal_day_evolution/carbohydrate') && isset($session->get('_meal_day_evolution/carbohydrate')[$rankMeal][$rankDish])) 
					? $session->get('_meal_day_evolution/carbohydrate')[$rankMeal][$rankDish] : 0;

		$sodiumConsumed = ($session->has('_meal_day_evolution/sodium') && isset($session->get('_meal_day_evolution/sodium')[$rankMeal][$rankDish])) 
					? $session->get('_meal_day_evolution/sodium')[$rankMeal][$rankDish] : 0;

		if($object instanceof Dish) {

			$dish = $object;
			$alertDishes = $session->get('_meal_day_alerts/_dishes_not_selected');

			if(array_key_exists($dish->getId(), $alertDishes) && !empty($alertDishes[$dish->getId()])) {
				unset($alertDishes[$dish->getId()]);
			}

			// ALERTES SUR LES GROUPES PARENTS D'ALIMENTS
			$fgpQuantitiesForNPortion = $this->dishUtil->getFoodGroupParentQuantitiesForNPortion($dish, $quantityOrPortion);
			if(null !== $listAlertFgp = $this->getAlerts('food_group_parent', $dish, $fgpQuantitiesConsumed, $quantityOrPortion, $fgpQuantitiesForNPortion)) {
				$alertDishes[$dish->getId()]["food_group_parent"] = $listAlertFgp;
			}
			
			// ALERTES SUR L'ENERGIE
			$energyDish = $this->extractDataFromDishOrFoodSelected('energy', $dish, $quantityOrPortion);			
			if(null !== $listAlertEnergy = $this->getAlerts('energy', $dish, $energyConsumed, $energyDish)) {
				$alertDishes[$dish->getId()]["energy"] = $listAlertEnergy;
			}

			// ALERTES SUR LES PROTEINES
			$proteinDish = $this->extractDataFromDishOrFoodSelected('protein', $dish, $quantityOrPortion);
			if(null !== $listAlertProtein = $this->getAlerts('protein', $dish, $proteinConsumed, $proteinDish)) {
				$alertDishes[$dish->getId()]["protein"] = $listAlertProtein;
			}

			// ALERTES SUR LES LIPIDES
			$lipidDish = $this->extractDataFromDishOrFoodSelected('lipid', $dish, $quantityOrPortion);
			if(null !== $listAlertlipid = $this->getAlerts('protein', $dish, $lipidConsumed, $lipidDish)) {
				$alertDishes[$dish->getId()]["lipid"] = $listAlertlipid;
			}

			// ALERTES SUR LES GLUCIDES
			$carbohydrateDish = $this->extractDataFromDishOrFoodSelected('carbohydrate', $dish, $quantityOrPortion);
			if(null !== $listAlertCarbohydrate = $this->getAlerts('carbohydrate', $dish, $carbohydrateConsumed, $carbohydrateDish)) {
				$alertDishes[$dish->getId()]["carbohydrate"] = $listAlertCarbohydrate;
			}

			// ALERTES SUR LE SODIUM
			$sodiumDish = $this->extractDataFromDishOrFoodSelected('sodium', $dish, $quantityOrPortion);
			if(null !== $listAlertSodium = $this->getAlerts('sodium', $dish, $sodiumConsumed, $sodiumDish)) {
				$alertDishes[$dish->getId()]["sodium"] = $listAlertSodium;
			}

			// On compile le tableau d'alertes pour conserver les messages et le niveau d'alerte le plus elevé.
			if (isset($alertDishes[$dish->getId()])) {
				$alertDishes[$dish->getId()] = $this->compileAlertsInformation($alertDishes[$dish->getId()]);
			}

			$session->set('_meal_day_alerts/_dishes_not_selected', $alertDishes);
		}

		if($object instanceof Food) {

			$food = $object;
			$alertFoods = $session->get('_meal_day_alerts/_foods_not_selected');

			if(array_key_exists($food->getId(), $alertFoods) && !empty($alertFoods[$food->getId()])) {
				unset($alertFoods[$food->getId()]);
			}

			$unitMeasure = $this->manager->getRepository(UnitMeasure::class)->findOneById($unitMeasureId);

			// ALERTES SUR LES GROUPES PARENTS D'ALIMENTS
			if(null !== $listAlertFgp = $this->getAlerts('food_group_parent', $food, $fgpQuantitiesConsumed, $quantityOrPortion, null, $unitMeasure->getAlias())) {
				$alertFoods[$food->getId()]["food_group_parent"] = $listAlertFgp;
			}

			// ALERTE SUR L'ENERGIE
			$energyFood = $this->extractDataFromDishOrFoodSelected('energy', $food, $quantityOrPortion, $unitMeasure->getAlias());
			if(null !== $listAlertEnergy = $this->getAlerts('energy', $food, $energyConsumed, $energyFood)) {
				$alertFoods[$food->getId()]["energy"] = $listAlertEnergy;
			}

			// ALERTS SUR PROTEINES
			$proteinFood = $this->extractDataFromDishOrFoodSelected('protein', $food, $quantityOrPortion, $unitMeasure->getAlias());
			if(null !== $listAlertProtein = $this->getAlerts('protein', $food, $proteinConsumed, $proteinFood)) {
				$alertFoods[$food->getId()]["protein"] = $listAlertProtein;
			}

			// ALERTS SUR LIPIDES
			$lipidFood = $this->extractDataFromDishOrFoodSelected('lipid', $food, $quantityOrPortion, $unitMeasure->getAlias());
			if(null !== $listAlertLipid = $this->getAlerts('lipid', $food, $lipidConsumed, $lipidFood)) {
				$alertFoods[$food->getId()]["lipid"] = $listAlertLipid;
			}

			// ALERTS SUR GLUCIDES
			$carbohydrateFood = $this->extractDataFromDishOrFoodSelected('carbohydrate', $food, $quantityOrPortion, $unitMeasure->getAlias());
			if(null !== $listAlertCarbohydrate = $this->getAlerts('carbohydrate', $food, $carbohydrateConsumed, $carbohydrateFood)) {
				$alertFoods[$food->getId()]["carbohydrate"] = $listAlertCarbohydrate;
			}

			// ALERTS SUR SODIUM
			$sodiumFood = $this->extractDataFromDishOrFoodSelected('sodium', $food, $quantityOrPortion, $unitMeasure->getAlias());
			if(null !== $listAlertSodium = $this->getAlerts('sodium', $food, $sodiumConsumed, $sodiumFood)) {
				$alertFoods[$food->getId()]["sodium"] = $listAlertSodium;
			}

			if (isset($alertFoods[$food->getId()])) {
				$alertFoods[$food->getId()] = $this->compileAlertsInformation($alertFoods[$food->getId()]);
			}

			$session->set('_meal_day_alerts/_foods_not_selected', $alertFoods);
		}
	}

	/********************************** FUNCTIONS **********************************/
	public function getAlerts(
			string $subject, 
			Dish|Food|null $object = null, 
			array|int|float $quantityOrFgpQuantitiesOrEnergyConsumed = null, 
			array|float $quantityOrPortionOrEnergyAdded, 
			?array $fgpQuantitiesForNPortionAdded = null, 
			?string $unitMeasureAlias = null, 
			?array &$finalListAlerts = []
	): ?array
	{
		$listAlert = [];

		if($object instanceof Food && null !== $unitMeasureAlias && 'g' !== $unitMeasureAlias) {
			$quantityOrPortionOrEnergyAdded = $this->foodUtil->convertInGr($quantityOrPortionOrEnergyAdded, $object, $unitMeasureAlias);
		}

		// ALERTES SUR LES QUANTITES DE GROUPES D'ALIMENT

		if('food_group_parent' === $subject) {
			$fgpQuantitiesRecommended = $this->security->getUser()->getRecommendedQuantities();

			foreach($fgpQuantitiesRecommended as $fgpAlias => $value)
			{
				if(
					($object instanceof Food && $fgpAlias === $object->getFoodGroup()->getParent()->getAlias())
						||
					($object instanceof Dish && $fgpQuantitiesForNPortionAdded[$fgpAlias] > 0)
				) {
					if($object instanceof Dish) {
						$alert = $alert = $this->createAlert($subject, $quantityOrFgpQuantitiesOrEnergyConsumed[$fgpAlias], $fgpQuantitiesForNPortionAdded[$fgpAlias], $fgpQuantitiesRecommended[$fgpAlias], $fgpAlias);
					}

					if($object instanceof Food) {
						$alert = $this->createAlert($subject, $quantityOrFgpQuantitiesOrEnergyConsumed[$fgpAlias], $quantityOrPortionOrEnergyAdded, $fgpQuantitiesRecommended[$fgpAlias], $fgpAlias);
					}

					if(null !== $alert) {
						$fgp = $this->manager->getRepository(FoodGroupParent::class)->findOneByAlias($fgpAlias);
						$listAlert['messages'][] = sprintf(LevelAlert::MESSAGE_FGP_NOT_RECOMMENDED, $alert->getPlaceholderText(), strtolower($fgp->getName()));
						$listAlert['levels'][] = $alert->getPriority();
						$finalListAlerts[$subject][$fgpAlias] = sprintf(LevelAlert::MESSAGE_FGP_NOT_RECOMMENDED, $alert->getPlaceholderText(), strtolower($fgp->getName()));
					}
				}
			}

		} else{

			$propertyAccessor = PropertyAccess::createPropertyAccessor();
			$quantityOrEnergyDailyRecommended = (int)$propertyAccessor->getValue($this->security->getUser(), $subject);

			if(null !== $alert = $this->createAlert($subject, $quantityOrFgpQuantitiesOrEnergyConsumed, $quantityOrPortionOrEnergyAdded, $quantityOrEnergyDailyRecommended)) {
				if('energy' === $subject) {
					$message = sprintf(LevelAlert::MESSAGE_ENERGY_NOT_RECOMMENDED, $alert->getPlaceholderText());
				}else{
					$message = sprintf(LevelAlert::MESSAGE_NUTRIENT_NOT_RECOMMENDED, $alert->getPlaceholderText(), $this->translator->trans($subject, [], 'nutrient'));
				}
				$listAlert['messages'][] = $message;
				$listAlert['levels'][] = $alert->getPriority();
				$finalListAlerts[$subject] = $message;;
			}

		}

		if(!empty($listAlert)){
			ksort($listAlert);
			return $listAlert;
		}

		return null;
	}

	private function createAlert(string $subject, float|array $quantityOrEnergyConsumed, float $quantityOrEnergyAdded, float|array $quantityOrEnergyRecommended, string $fgpAlias = null): null|LevelAlert
	{
		// if($subject === 'energy') {
		// 	dump($quantityOrEnergyConsumed);
		// 	dd('energy');
		// }
		$listAlert = [];
		$codeStartAlertLevel = $this->getLevelAlert($quantityOrEnergyConsumed, $quantityOrEnergyRecommended);
		$codeFinishAlertLevel = $this->getLevelAlert($quantityOrEnergyConsumed + $quantityOrEnergyAdded, $quantityOrEnergyRecommended);
		
		if(LevelAlert::RECOMMENDED != $codeFinishAlertLevel)
		{
			$levelAlert = $this->manager->getRepository(LevelAlert::class)->findOneByCode($codeFinishAlertLevel);

			return $levelAlert;

		}

		return null;
	}

	private function getLevelAlert(float $quantityOrEnergyConsumed, float $quantityOrEnergyRecommended): string
	{
		if ($quantityOrEnergyConsumed > ($quantityOrEnergyRecommended  * 2))
		{
			$level = LevelAlert::STRONGLY_NOT_RECOMMENDED;
		}elseif($quantityOrEnergyConsumed > ($quantityOrEnergyRecommended + ($quantityOrEnergyRecommended / 2))){
			$level = LevelAlert::HIGHLY_NOT_RECOMMENDED;
		}elseif($quantityOrEnergyConsumed > ($quantityOrEnergyRecommended + (LevelAlert::RECOMMENDED_WELL_RANGE * $quantityOrEnergyRecommended))){
			$level = LevelAlert::NOT_RECOMMENDED;
		// }elseif($quantityConsumedWithThisDish < ($this->quantitiesRecommended[$fgpAlias] / 2)){
		// 	$level = Level::HIGHLY_RECOMMENDED; 
		}else{
			$level = LevelAlert::RECOMMENDED;
		}
		
		return $level;
	}

	private function compileAlertsInformation(array $alerts)
	{
		$messages = [];
		foreach($alerts as $alert) {
			$mins[] = min($alert["levels"]);
			$messages = array_merge($messages, $alert["messages"]);
		}

		return [
			"higher_level" => $this->manager->getRepository(LevelAlert::class)->findOneByPriority((int)min($mins)),
			"messages" => $messages
		];
	}

	public function extractDataFromDishOrFoodSelected(string $type, Food|Dish $element, float $quantity, ?string $unitMeasureAlias = null): ?int
    {
        // $item example = [▼
        //     "type" => "Food"
        //     "id" => "680"
        //     "quantity" => "20"
        //     "measureUnit" => "93"
        //     "measureUnitAlias" => "ml"
        // ]
		$result = 0;

		$propertyAccessor = PropertyAccess::createPropertyAccessor();

		if($element instanceof Food) {
			if(null !== $unitMeasureAlias && 'g' !== $unitMeasureAlias) {
				$quantity = $this->foodUtil->convertInGr($quantity, $element, $unitMeasureAlias);
			}
			$result = ($quantity * (int)$propertyAccessor->getValue($element, $type)) / 100;

			return $result;
		}

		if($element instanceof Dish) {
			$result = 0;
			foreach($element->getDishFoods()->toArray() as $dishFood)
			{
				$quantiteGr = ($dishFood->getQuantityG() * $quantity) / $element->getLengthPersonForRecipe();
				$result += ($quantiteGr * $propertyAccessor->getValue($dishFood->getFood(), $type)) / 100;
			}

			return $result;
		}

        return $result;
	}

	public function setEnergyAndNutrientsDataSession(): bool
	{
		$session = $this->requestStack->getSession();

		if($session->has('_meal_day_range')) {
			//$init = false;

			// if($session->get('_meal_day_range') > 0 )
			// {
			$energyDay = $proteinDay = $lipidDay = $carbohydrateDay = $sodiumDay = 0;
			// On stocke les énergies augmentées à chaque plat en partant de zéro
			$energies[0][0] = $proteins[0][0] = $lipids[0][0] = $carbohydrates[0][0] = $sodiums[0][0] = 0;
			
			for($i = 0; $i <= $session->get('_meal_day_range'); $i++)
			{
				$energyMeal = $proteinMeal = $lipidMeal = $carbohydrateMeal = $sodiumMeal = 0;
				$meal = $session->get('_meal_day_' . $i);
		
				if($i > 0) {
					$energies[$i][0] = end($energies[$i-1]);
					$proteins[$i][0] = end($proteins[$i-1]);
					$lipids[$i][0] = end($lipids[$i-1]);
					$carbohydrates[$i][0] = end($carbohydrates[$i-1]);
					$sodiums[$i][0] = end($sodiums[$i-1]);
				}

				if(isset($meal['dishAndFoods']))
				{
					$dishAndFoods = $meal['dishAndFoods'];
					// $listFgp = array_merge($mealUtil->getListfgp($dishAndFoods), $listFgp);

					foreach($dishAndFoods as $j => $dishOrFood) {
						// $energyDishOrFoodOld = $energyHandler->getEnergyForDishOrFoodSelected($dishOrFood['id'], $dishOrFood['type'], $dishOrFood['quantity'], $dishOrFood['unitMeasureAlias']);
						$repo = ('Dish' === $dishOrFood['type'] || 'dish' === $dishOrFood['type']) ? $this->dishRepository : $this->foodRepository;
						$item = $repo->findOneById((int)$dishOrFood['id']);

						// EVOLUTION DE L'ENERGIE DANS LA JOURNEE
						$energyDishOrFood = $this->extractDataFromDishOrFoodSelected(
												'energy', 
												$item, 
												(float)$dishOrFood['quantity'], 
												(string)$dishOrFood['unitMeasureAlias']
											);
						
						$energyMeal += $energyDishOrFood;
						$energyDay += $energyDishOrFood;
						$energies[$i][$j+1] = $energyDay;

						// EVOLUTION DES PROTEINES DANS LA JOURNEE
						$proteinDishOrFood = $this->extractDataFromDishOrFoodSelected(
												'protein', 
												$item, 
												(float)$dishOrFood['quantity'], 
												(string)$dishOrFood['unitMeasureAlias']
											);
	
						$proteinMeal += $proteinDishOrFood;
						$proteinDay += $proteinDishOrFood;
						$proteins[$i][$j+1] = $proteinDay;

						// EVOLUTION DES LIPIDES DANS LA JOURNEE
						$lipidDishOrFood = $this->extractDataFromDishOrFoodSelected(
												'lipid', 
												$item, 
												(float)$dishOrFood['quantity'], 
												(string)$dishOrFood['unitMeasureAlias']
											);

						$lipidMeal += $lipidDishOrFood;
						$lipidDay += $lipidDishOrFood;
						$lipids[$i][$j+1] = $lipidDay;

						// EVOLUTION DES GLUCIDES DANS LA JOURNEE
						$carbohydrateDishOrFood = $this->extractDataFromDishOrFoodSelected(
							'lipid', 
							$item, 
							(float)$dishOrFood['quantity'], 
							(string)$dishOrFood['unitMeasureAlias']
						);

						$carbohydrateMeal += $carbohydrateDishOrFood;
						$carbohydrateDay += $carbohydrateDishOrFood;
						$carbohydrates[$i][$j+1] = $carbohydrateDay;

						// EVOLUTION DU SODIUM DANS LA JOURNEE
						$sodiumDishOrFood = $this->extractDataFromDishOrFoodSelected(
							'sodium', 
							$item, 
							(float)$dishOrFood['quantity'], 
							(string)$dishOrFood['unitMeasureAlias']
						);

						$sodiumMeal += $sodiumDishOrFood;
						$sodiumDay += $sodiumDishOrFood;
						$sodiums[$i][$j+1] = $sodiumDay;
					}	
				}

				$meal['energy'] = $energyMeal;
				$session->set('_meal_day_' . $i, $meal);

			}

			$session->set('_meal_day_evolution/energy', $energies);
			$session->set('_meal_day_evolution/protein', $proteins);
			$session->set('_meal_day_evolution/lipid', $lipids);
			$session->set('_meal_day_evolution/carbohydrate', $carbohydrates);

			$session->set('_meal_day_energy', $energyDay);

		}

		return true;
	}

	public function getBalanceSheetAlerts($averageDailyEnergy, $averageDailyNutrient, $averageDailyFgp): array|Response
	{
		$results = [];
		$user = $this->security->getUser();

		$propertyAccessor = PropertyAccess::createPropertyAccessor();

		// ENERGIE
		$energyDailyRecommended = (int)$propertyAccessor->getValue($this->security->getUser(), 'energy');
		$results['energy'] = $this->isWellBalanced($averageDailyEnergy, $energyDailyRecommended);
	
		// NUTRIMENTS
		foreach($averageDailyNutrient as $nutrient => $averageQuantity)
		{
			$nutrientDailyRecommended = (int)$propertyAccessor->getValue($this->security->getUser(), $nutrient);
			$results[$nutrient] = $this->isWellBalanced($averageQuantity, $nutrientDailyRecommended);
		}

		// FOOD GROUP PARENT
		$fgpQuantitiesRecommended = $this->security->getUser()->getRecommendedQuantities();
		
		if(!$fgpQuantitiesRecommended) {
			return new Response('Vous n\'avez aucune recommendations de quantités de groupes d\'aliments');
		}
		// dd($averageDailyFgp);
		// dd($fgpQuantitiesRecommended);
		foreach($averageDailyFgp as $fgpAlias => $averageQuantity)
		{
			if(isset($fgpQuantitiesRecommended[$fgpAlias])) {
				$results[$fgpAlias] = $this->isWellBalanced($averageQuantity, $fgpQuantitiesRecommended[$fgpAlias]);
			}
		}

		return $results;
	}

	public function getWeightEnergyAndImcBalanceAlerts(): array
	{
		return [
			'imc' => $this->getWeightAlert(),
			'weight' => $this->getImcAlert(),
			'energy' => $this->getEnergyAlert(),
		];
	}

	public function getWeightAlert(): LevelAlert
	{
		$user = $this->security->getUser();

		$weight = $user->getWeight();
		$idealWeight = $user->getIdealWeight();
		
		return $this->isWellBalanced($weight, $idealWeight);
	}

	// public function getImcAlert(): LevelAlert
	// {
	// 	$user = $this->security->getUser();

	// 	$imc = $user->getImc();
	// 	$idealImc = $user->getIdealImc();
		
	// 	return $this->isWellBalanced($imc, $idealImc);
	// }

	public function getEnergyAlert(): null|LevelAlert
	{
		$user = $this->security->getUser();

		if ($user->isAutomaticCalculateEnergy()) {
			return $this->manager
				->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_WELL);
		}

		return $this->isWellBalanced($user->getEnergy(), $user->getEnergyCalculate());
	}

	public function isWellBalanced($quantity, $quantityRecommended): LevelAlert
	{
		$marginAccepted = $quantityRecommended * LevelAlert::BALANCE_WELL_RANGE;
		$marginAcceptedMin = $quantityRecommended - $marginAccepted;
		$marginAcceptedMax = $quantityRecommended + $marginAccepted;

		if($quantity < ($marginAcceptedMin / 3)) {
			return $this->manager->getRepository(LevelAlert::class)->findOneByCode(LevelAlert::BALANCE_CRITICAL_LACK);
		}

		if($quantity < ($marginAcceptedMin / 2)) {
			return $this->manager->getRepository(LevelAlert::class)->findOneByCode(LevelAlert::BALANCE_VERY_LACK);
		}

		if($quantity < $marginAcceptedMin) {
			return $this->manager->getRepository(LevelAlert::class)->findOneByCode(LevelAlert::BALANCE_LACK);
		}

		if($quantity > ($marginAcceptedMax * 3)) {
			return $this->manager->getRepository(LevelAlert::class)->findOneByCode(LevelAlert::BALANCE_CRITICAL_EXCESS);
		}

		if($quantity > ($marginAcceptedMax * 2)) {
			return $this->manager->getRepository(LevelAlert::class)->findOneByCode(LevelAlert::BALANCE_VERY_EXCESS);
		}

		if($quantity > $marginAcceptedMax) {
			return $this->manager->getRepository(LevelAlert::class)->findOneByCode(LevelAlert::BALANCE_EXCESS);
		}

		return $this->manager->getRepository(LevelAlert::class)->findOneByCode(LevelAlert::BALANCE_WELL);
	}

	public function getImcAlert(float $imc): LevelAlert
	{
		if ($imc < 16) {
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_CRITICAL_LACK);
		}

		if ($imc < 17) {
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_VERY_LACK);
		}

		if ($imc < 18.5) {
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_LACK);
		}

		if ($imc <= 25) {
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_WELL);
		}

		if ($imc <= 30) {
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_EXCESS);
		}

		if ($imc <= 35) {
			return $this->manager->getRepository(LevelAlert::class)
				->findOneByCode(LevelAlert::BALANCE_VERY_EXCESS);
		}

		return $this->manager->getRepository(LevelAlert::class)
			->findOneByCode(LevelAlert::BALANCE_CRITICAL_EXCESS);
	}


	public function getCalorieAdjustmentPercent(LevelAlert $imcAlert): int
	{
		return match ($imcAlert->getCode()) {
			LevelAlert::BALANCE_CRITICAL_EXCESS => -15,
			LevelAlert::BALANCE_VERY_EXCESS     => -10,
			LevelAlert::BALANCE_EXCESS          => -5,
			LevelAlert::BALANCE_WELL            => 0,
			LevelAlert::BALANCE_LACK            => 5,
			LevelAlert::BALANCE_VERY_LACK       => 10,
			LevelAlert::BALANCE_CRITICAL_LACK   => 15,
			default => 0,
		};
	}
}