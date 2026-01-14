<?php


/**  
 *	ALERTES DES PLATS/FOODS DE LA LISTE DE LA LISTE DES REPAS: 290 
 *  ALERTES DES PLATS/FOODS DE LA LISTE DE LA FENETRE MODALE: 373
 */

namespace App\Service;

use App\Repository\DishRepository;
use App\Repository\FoodRepository;
use App\Entity\Dish;
use App\Entity\Food;
use App\Service\DishUtil;
use App\Service\FoodUtil;
use App\Entity\Alert\Alert;
use App\Entity\Alert\Level;
use App\Entity\UnitMeasure;
use App\Service\AlertFeature;
use App\Service\EnergyHandler;
use App\Service\QuantityTreatment;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\FoodGroup\FoodGroupParent;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AlertFeature2
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
	){}


	/************* ALERTES DES PLATS/FOODS DE LA LISTE DES REPAS ************/

	public function setAlertOnDishesAndFoodsAlreadySelected()
	{
		$session = $this->requestStack->getSession();

		$fgpQuantitiesConsumed = $this->quantityTreatment->getQuantitiesConsumedNull();
		$energyConsumed = $quantityProteinConsumed = $quantityLipidConsumed = $quantityCarbohydrateConsumed = $quantitySodiumConsumed = 0;

		$alertDishes = [];
		$alertFoods = [];

		for($n = 0; $n <= $session->get('_meal_day_range'); $n++)
		{
			if($session->has('_meal_day_' . $n) && array_key_exists('dishAndFoods', $session->get('_meal_day_' . $n)))
			{
				foreach ($session->get('_meal_day_' . $n)['dishAndFoods'] as $index => $element) 
				{
					if('Dish' === $element['type'])
					{
						$dish = $this->manager->getRepository(Dish::class)->findOneById((int)$element['id']);

						// ALERTES SUR LES GROUPES PARENTS D'ALIMENTS

						$fgpQuantitiesForNPortion = $this->dishUtil->getFoodGroupParentQuantitiesForNPortion($dish, $element['quantity']);
						if(null !== $listAlertFgp = $this->getAlerts('food_group_parent', $dish, $fgpQuantitiesConsumed, $element['quantity'], $fgpQuantitiesForNPortion)) {
							$alertDishes[$n][$index]["food_group_parent"] = $listAlertFgp;
						}

						// ALERTES SUR LES PROTEINES

						$quantityProtein = $this->extractDataFromDishOrFoodSelected('protein', $dish, $element['quantity']);
						if(null !== $listAlertProtein = $this->getAlerts('protein', $dish, $quantityProteinConsumed, $quantityProtein)) {
							$alertDishes[$n][$index]["protein"] = $listAlertProtein;
						}
					
						// ALERTES SUR LES LIPIDES

						$quantityLipid = $this->extractDataFromDishOrFoodSelected('lipid', $dish, $element['quantity']);
						if(null !== $listAlertLipid = $this->getAlerts('lipid', $dish, $quantityLipidConsumed, $quantityLipid)) {
							$alertDishes[$n][$index]["lipid"] = $listAlertLipid;
						}
						
						// ALERTES SUR LES GLUCIDES

						$quantityCarbohydrate = $this->extractDataFromDishOrFoodSelected('carbohydrate', $dish, $element['quantity']);
						if(null !== $listAlertCarbohydrate = $this->getAlerts('carbohydrate', $dish, $quantityCarbohydrateConsumed, $quantityCarbohydrate)) {
							$alertDishes[$n][$index]["carbohydrate"] = $listAlertCarbohydrate;
						}

						// ALERTES SUR LE SODIUM

						$quantitySodium = $this->extractDataFromDishOrFoodSelected('sodium', $dish, $element['quantity']);
						if(null !== $listAlertSodium = $this->getAlerts('sodium', $dish, $quantitySodiumConsumed, $quantitySodium)) {
							$alertDishes[$n][$index]["sodium"] = $listAlertSodium;
						}

						// ALERTE SUR L'ENERGIE DEPENSEE

						$energyDish = $this->extractDataFromDishOrFoodSelected('energy', $dish, $element['quantity']);
						if(null !== $listAlertEnergy = $this->getAlerts('energy', $dish, $energyConsumed, $energyDish)) {
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

					}

					if('Food' === $element['type'])
					{
						$food = $this->manager->getRepository(Food::class)->findOneById((int)$element['id']);
	
						// ALERTES SUR LES GROUPES PARENTS D'ALIMENTS

						$fgpAlias = $food->getFoodGroup()->getParent()->getAlias();

						if(null !== $listAlertFgp = $this->getAlerts('food_group_parent', $food, $fgpQuantitiesConsumed, (float)$element['quantity'], null, (string)$element['unitMeasureAlias'])) {
							$alertFoods[$n][$index]["food_group_parent"] = $listAlertFgp;
						}

						// ALERTES SUR LES PROTEINES

						$quantityProtein = $this->extractDataFromDishOrFoodSelected('protein', $dish, $element['quantity']);
						if(null !== $listAlertProtein = $this->getAlerts('protein', $dish, $quantityProteinConsumed, $quantityProtein)) {
							$alertFoods[$n][$index]["protein"] = $listAlertProtein;
						}
					
						// ALERTES SUR LES LIPIDES

						$quantityLipid = $this->extractDataFromDishOrFoodSelected('lipid', $dish, $element['quantity']);
						if(null !== $listAlertLipid = $this->getAlerts('lipid', $dish, $quantityLipidConsumed, $quantityLipid)) {
							$alertFoods[$n][$index]["lipid"] = $listAlertLipid;
						}
						
						// ALERTES SUR LES GLUCIDES

						$quantityCarbohydrate = $this->extractDataFromDishOrFoodSelected('carbohydrate', $dish, $element['quantity']);
						if(null !== $listAlertCarbohydrate = $this->getAlerts('carbohydrate', $dish, $quantityCarbohydrateConsumed, $quantityCarbohydrate)) {
							$alertFoods[$n][$index]["carbohydrate"] = $listAlertCarbohydrate;
						}

						// ALERTES SUR LE SODIUM

						$quantitySodium = $this->extractDataFromDishOrFoodSelected('sodium', $dish, $element['quantity']);
						if(null !== $listAlertSodium = $this->getAlerts('sodium', $dish, $quantitySodiumConsumed, $quantitySodium)) {
							$alertFoods[$n][$index]["sodium"] = $listAlertSodium;
						}

						// ALERTE SUR L'ENERGIE DEPENSEE

						$energyFood = $this->extractDataFromDishOrFoodSelected('energy', $food, (float)$element['quantity'], (string)$element['unitMeasureAlias']);

						if(null !== $listAlertEnergy = $this->getAlerts('energy', $food, $energyConsumed, $energyFood)) {
							$alertFoods[$n][$index]["energy"] = $listAlertEnergy;
						}
						
						if (isset($alertFoods[$n][$index])) {
							$alertFoods[$n][$index] = $this->compileAlertsInformation($alertFoods[$n][$index]);
						}
						
						// On met à jour les quantités consommées de chaque groupe d'aliment
						$quantityInGr = $this->foodUtil->convertInGr((float)$element['quantity'], $food, (string)$element['unitMeasureAlias']);
						array_key_exists($fgpAlias, $fgpQuantitiesConsumed) ? $fgpQuantitiesConsumed[$fgpAlias] += $quantityInGr : $fgpQuantitiesConsumed[$fgpAlias] = $quantityInGr;
						
					}

					$energyConsumed += $energyDish;
					$quantityProteinConsumed += $quantityProtein; 
					$quantityLipidConsumed += $quantityLipid; 
					$quantityCarbohydrateConsumed += $quantityCarbohydrate; 
					$quantitySodiumConsumed += $quantitySodium; 

				}
			}
		}

		// dd($alertDishes, $alertFoods);
		
		$session->set('_meal_day_alerts/_dishes_selected', $alertDishes);
		$session->set('_meal_day_alerts/_foods_selected', $alertFoods);
	}

	/********** ALERTES DES PLATS/FOODS DE LA LISTE DE LA FENETRE MODALE ***********/

	public function setAlertOnDishesAndFoodsAboutTobeSelected($rankMeal = null, $rankDish = null)
	{
		$session = $this->requestStack->getSession();

		$alertDishes = $alertFoods = [];
		$fgpQuantitiesConsumed = $this->quantityTreatment->getQuantitiesConsumedInSessionDishes($rankMeal, $rankDish);
		// $energyConsumed = $session->has('_meal_day_energy_consumed') ? $session->get('_meal_day_energy_consumed') : 0;
		// $energyConsumed = $session->has('_meal_day_energy') ? $session->get('_meal_day_energy') : 0;
		// dd($session->get('_meal_day_energy_evolution'));
		// dd($rankMeal, $rankDish);
		// dd($session->get('_meal_day_energy_evolution'));
		
		if($session->has('_meal_day_energy_evolution') && isset($session->get('_meal_day_energy_evolution')[$rankMeal][$rankDish])) {
			$energyConsumed = $session->get('_meal_day_energy_evolution')[$rankMeal][$rankDish];
		} else{
			$energyConsumed = 0;
		}

		// dd($session->get('_meal_day_energy_evolution'));

		// ALERTES SUR LES PLATS
		
		foreach ($this->manager->getRepository(Dish::class)->findAll() as $dish) 
		{
			// ALERTES SUR FOODGROUP
			$fgpQuantitiesForNPortion = $this->dishUtil->getFoodGroupParentQuantitiesForNPortion($dish, 1);

			if(null !== $listAlertFgp = $this->getAlerts('food_group_parent', $dish, $fgpQuantitiesConsumed, 1, $fgpQuantitiesForNPortion)){
				$alertDishes[$dish->getId()]["food_group_parent"] = $listAlertFgp;
			}

			// $energyDishOld = $this->energyHandler->getEnergyForDishOrFoodSelected($dish, 'Dish', 1);
			$energyDish = $this->extractDataFromDishOrFoodSelected('energy', $dish, 1);
			// dump($energyDishOld, $energyDish);

			if(null !== $listAlertEnergy = $this->getAlerts('energy', $dish, $energyConsumed, $energyDish)) {
				$alertDishes[$dish->getId()]["energy"] = $listAlertEnergy;
			}
			// dump($dish->getName());
			// dump($alertDishes);

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
			if(null !== $listAlertFgp = $this->getAlerts('food_group_parent', $food, $fgpQuantitiesConsumed, 0)) {
				$alertFoods[$food->getId()]["food_group_parent"] = $listAlertFgp;
			}

			// $energyFoodOld = $this->energyHandler->getEnergyForDishOrFoodSelected($food, 'Food', 0, $unitMeasureGr);
			$energyFood = $this->extractDataFromDishOrFoodSelected('energy', $food, 0, 'g');
			// dump($energyFoodOld, $energyFood);

			if(null !== $listAlertEnergy = $this->getAlerts('energy', $food, $energyConsumed, $energyFood)) {
				$alertFoods[$food->getId()]["energy"] = $listAlertEnergy;
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

		$energyConsumed = $session->has('_meal_day_energy_evolution') ? $session->get('_meal_day_energy_evolution')[$rankMeal][$rankDish] : 0;

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
			
			// ALERTES SUR L'ENERGIE DEPENSEE

			$energyDishOld = $this->energyHandler->getEnergyForDishOrFoodSelected($dish, 'Dish', $quantityOrPortion);
			$energyDish = $this->extractDataFromDishOrFoodSelected('energy', $dish, $quantityOrPortion);			

			// $energyConsumed = $session->has('_meal_day_energy_consumed') ? $session->get('_meal_day_energy_consumed') : 0;
			// $energyConsumed = $session->has('_meal_day_energy') ? $session->get('_meal_day_energy') : 0;

			if(null !== $listAlertEnergy = $this->getAlerts('energy', $dish, $energyConsumed, $energyDish)) {
				$alertDishes[$dish->getId()]["energy"] = $listAlertEnergy;
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

			// ALERTES SUR LES GROUPES PARENTS D'ALIMENTS
			$unitMeasure = $this->manager->getRepository(UnitMeasure::class)->findOneById($unitMeasureId);
			// $quantityInGr = $this->foodUtil->convertInGr($quantityOrPortion, $food, $unitMeasure);
			// $unitMeasureGr = $this->manager->getRepository(UnitMeasure::class)->findOneByAlias('g');

			if(null !== $listAlertFgp = $this->getAlerts('food_group_parent', $food, $fgpQuantitiesConsumed, $quantityOrPortion, null, $unitMeasure->getAlias())) {
				$alertFoods[$food->getId()]["food_group_parent"] = $listAlertFgp;
			}

			// ALERTE SUR L'ENERGIE DEPENSEE

			$unitMeasureGr = $this->manager->getRepository(UnitMeasure::class)->findOneByAlias('g');
			$energyFoodOld = $this->energyHandler->getEnergyForDishOrFoodSelected($food, 'Food', $quantityOrPortion, $unitMeasureGr);
			$energyFood = $this->extractDataFromDishOrFoodSelected('energy', $food, $quantityOrPortion, $unitMeasure->getAlias());

			// $energyConsumed = $session->has('_meal_day_energy_consumed') ? $session->get('_meal_day_energy_consumed') : 0;
			// $energyConsumed = $session->has('_meal_day_energy') ? $session->get('_meal_day_energy') : 0;
			if(null !== $listAlertEnergy = $this->getAlerts('energy', $food, $energyConsumed, $energyFood)) {
				$alertFoods[$food->getId()]["energy"] = $listAlertEnergy;
			}

			if (isset($alertFoods[$food->getId()])) {
				$alertFoods[$food->getId()] = $this->compileAlertsInformation($alertFoods[$food->getId()]);
			}

			$session->set('_meal_day_alerts/_foods_not_selected', $alertFoods);
		}
	}

	/********************************** FUNCTIONS **********************************/
	public function getAlerts(string $subject, Dish|Food|null $object = null, array|int|float $quantityOrFgpQuantitiesOrEnergyConsumed = null, array|float $quantityOrPortionOrEnergyAdded, ?array $fgpQuantitiesForNPortionAdded = null, ?string $unitMeasureAlias = null): ?array
	{
		$listAlert = [];

		if($object instanceof Food && null !== $unitMeasureAlias && 'g' !== $unitMeasureAlias) {
			$quantityOrPortionOrEnergyAdded = $this->foodUtil->convertInGr($quantityOrPortionOrEnergyAdded, $object, $unitMeasureAlias);
		}

		// ALERTES SUR LES QUANTITES DE GROUPES D'ALIMENT

		if('food_group_parent' === $subject) {
			
			$fgpQuantitiesRecommended = $this->security->getUser()->getRecommendedQuantities();
			// dump($fgpQuantitiesRecommended);
			// dump($fgpQuantitiesForNPortionAdded);

			foreach($fgpQuantitiesRecommended as $fgpAlias => $value)
			{
				if($object instanceof Food && $fgpAlias === $object->getFoodGroup()->getParent()->getAlias()) {
					$alert = $this->createAlert($subject, $quantityOrFgpQuantitiesOrEnergyConsumed[$fgpAlias], $quantityOrPortionOrEnergyAdded, $fgpQuantitiesRecommended[$fgpAlias], $fgpAlias);
					if(!empty($alert)) {
						$listAlert['messages'][] = $alert['message'];
						$listAlert['levels'][] = $alert['level'];
					}
				}
				if($object instanceof Dish && $fgpQuantitiesForNPortionAdded[$fgpAlias] > 0) {
					// dump($quantityOrFgpQuantitiesOrEnergyConsumed[$fgpAlias], $fgpQuantitiesForNPortionAdded[$fgpAlias], $fgpQuantitiesRecommended[$fgpAlias], $fgpAlias);
					$alert = $this->createAlert($subject, $quantityOrFgpQuantitiesOrEnergyConsumed[$fgpAlias], $fgpQuantitiesForNPortionAdded[$fgpAlias], $fgpQuantitiesRecommended[$fgpAlias], $fgpAlias);
					if(!empty($alert)) {
						$listAlert['messages'][] = $alert['message'];
						$listAlert['levels'][] = $alert['level'];
					}
				}
			}

		} else{

			$propertyAccessor = PropertyAccess::createPropertyAccessor();

			$quantityOrEnergyDailyRecommended = (int)$propertyAccessor->getValue($this->security->getUser(), $subject);

			// $energyDailyRecommended = $this->security->getUser()->getEnergy();
			$alert = $this->createAlert($subject, $quantityOrFgpQuantitiesOrEnergyConsumed, $quantityOrPortionOrEnergyAdded, $quantityOrEnergyDailyRecommended);
			if(!empty($alert)) {
				$listAlert['messages'][] = $alert['message'];
				$listAlert['levels'][] = $alert['level'];
			}

		}
		

		// if('energy' === $subject) {
		// 	$energyDailyRecommended = $this->security->getUser()->getEnergy();
		// 	$alert = $this->createAlert($subject, $quantityOrFgpQuantitiesOrEnergyConsumed, $quantityOrPortionOrEnergyAdded, $energyDailyRecommended);
		// 	if(!empty($alert)) {
		// 		$listAlert['messages'][] = $alert['message'];
		// 		$listAlert['levels'][] = $alert['level'];
		// 	}
		// }


		if(!empty($listAlert)){
			ksort($listAlert);
			return $listAlert;
		}

		return null;
	}

	private function createAlert(string $subject, float|array $quantityOrEnergyConsumed, float $quantityOrEnergyAdded, float|array $quantityOrEnergyRecommended, string $fgpAlias = null): array
	{
		$listAlert = [];

		$codeStartAlertLevel = $this->getLevelAlert($quantityOrEnergyConsumed, $quantityOrEnergyRecommended);
		$codeFinishAlertLevel = $this->getLevelAlert($quantityOrEnergyConsumed + $quantityOrEnergyAdded, $quantityOrEnergyRecommended);
		
		if(Level::RECOMMENDED != $codeFinishAlertLevel)
		{
			// $alreadyNotRecommended = $codeStartAlertLevelForFGP == $codeFinishAlertLevelForFGP ? true : false;

			$levelAlert = $this->manager->getRepository(Level::class)->findOneByCode($codeFinishAlertLevel);
			
			switch ($subject) {
				case 'food_group_parent':
					$fgp = $this->manager->getRepository(FoodGroupParent::class)->findOneByAlias($fgpAlias);
					$message = sprintf($levelAlert->getDetailedText(), strtolower($fgp->getName()));
					break;
				case 'energy':
					$message = Level::MESSAGE_ENERGY_NOT_RECOMMENDED;
					break;
				case 'protein':
					$message = sprintf($levelAlert->getDetailedText(), 'protéines');
					break;
				case 'sodium':
					$message = sprintf($levelAlert->getDetailedText(), 'sodium');
					break;
				case 'lipid':
					$message = sprintf($levelAlert->getDetailedText(), 'lipides');
					break;
				case 'carbohydrate':
					$message = sprintf($levelAlert->getDetailedText(), 'glucides');
					break;
				default:
					# code...
					break;
			}

			// // $listAlert[$levelAlert->getPriority()][] = new Alert(null, null, $fgp, null, null, $levelAlert, $alreadyNotRecommended);
			// // $listAlert["messages"][] =  sprintf($levelAlert->getDetailedText(), strtolower($fgp->getName()));

			$listAlert["message"] =  $message;
			$listAlert["level"] = $levelAlert->getPriority();

			return $listAlert;

		}

		return $listAlert;
	}

	private function getLevelAlert(float $quantityOrEnergyConsumed, float $quantityOrEnergyRecommended): string
	{
		if ($quantityOrEnergyConsumed > ($quantityOrEnergyRecommended  * 2))
		{
			$level = Level::STRONGLY_NOT_RECOMMENDED;
		}elseif($quantityOrEnergyConsumed > ($quantityOrEnergyRecommended + ($quantityOrEnergyRecommended / 2))){
			$level = Level::HIGHLY_NOT_RECOMMENDED;
		}elseif($quantityOrEnergyConsumed > $quantityOrEnergyRecommended){
			$level = Level::NOT_RECOMMENDED;
		// }elseif($quantityConsumedWithThisDish < ($this->quantitiesRecommended[$fgpAlias] / 2)){
		// 	$level = Level::HIGHLY_RECOMMENDED; 
		}else{
			$level = Level::RECOMMENDED;
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
			"higher_level" => $this->manager->getRepository(Level::class)->findOneByPriority((int)min($mins)),
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

	public function setEnergyDataSession(): bool
	{
		$session = $this->requestStack->getSession();

		if($session->has('_meal_day_range')) {
			//$init = false;

			// if($session->get('_meal_day_range') > 0 )
			// {
				$energyDay = 0;
				// On stocke les énergies augmentées à chaque plat en partant de zéro
				$energies[0][0] = 0;
				
				for($i = 0; $i <= $session->get('_meal_day_range'); $i++)
				{
					$energyMeal = 0;
					$meal = $session->get('_meal_day_' . $i);
			

					if($i > 0) {
						$energies[$i][0] = end($energies[$i-1]);
					}

					if(isset($meal['dishAndFoods']))
					{
						$dishAndFoods = $meal['dishAndFoods'];
						// $listFgp = array_merge($mealUtil->getListfgp($dishAndFoods), $listFgp);

						foreach($dishAndFoods as $j => $dishOrFood) {
							// $energyDishOrFoodOld = $energyHandler->getEnergyForDishOrFoodSelected($dishOrFood['id'], $dishOrFood['type'], $dishOrFood['quantity'], $dishOrFood['unitMeasureAlias']);
							$repo = 'Dish' === $dishOrFood['type'] ? $this->dishRepository : $this->foodRepository;
							$energyDishOrFood = $this->extractDataFromDishOrFoodSelected(
													'energy', 
													$repo->findOneById((int)$dishOrFood['id']), 
													(float)$dishOrFood['quantity'], 
													(string)$dishOrFood['unitMeasureAlias']
												);
							
							$energyMeal += $energyDishOrFood;
							$energyDay += $energyDishOrFood;
							$energies[$i][$j+1] = $energyDay;
						}	
					}

					$meal['energy'] = $energyMeal;
					$session->set('_meal_day_' . $i, $meal);
					// dump($energies);

				}
			
			// dd($energies);
			$session->set('_meal_day_energy_evolution', $energies);
			$session->set('_meal_day_energy', $energyDay);

		}

		return true;
	}

}