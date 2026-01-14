<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\DishFood;
use App\Entity\DishFoodGroup;
use App\Repository\FoodRepository;
use App\Entity\DishFoodGroupParent;
use App\Repository\FoodGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UnitMeasureRepository;
use App\Repository\FoodGroupParentRepository;
use App\Repository\StepRecipeRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityNotFoundException;

class DishFoodHandler
{
    private $requestStack;
    private $foodRepository;
    private $foodGroupRepository;
    private $foodGroupParentRepository;
    private $unitMeasureRepository;
    private $stepRecipeRepository;
    private $manager;

    public function __construct(RequestStack $requestStack, FoodRepository $foodRepository,
        FoodGroupRepository $foodGroupRepository, FoodGroupParentRepository $foodGroupParentRepository, 
                    UnitMeasureRepository $unitMeasureRepository, EntityManagerInterface $manager,
                    StepRecipeRepository $stepRecipeRepository)
    {
        $this->requestStack = $requestStack;
        $this->foodRepository = $foodRepository;
        $this->foodGroupRepository = $foodGroupRepository;
        $this->foodGroupParentRepository = $foodGroupParentRepository;
        $this->unitMeasureRepository = $unitMeasureRepository;
        $this->stepRecipeRepository = $stepRecipeRepository;
        $this->manager = $manager;
    }

    // Créer les élements DishFood, DishFoodGroup, DishFoodGroupParent liés à un plat objet Dish

    public function createDishFoodElement(Dish $dish, array $recipeFoods = []): Dish
    {
        if(empty($recipeFoods)) {
            $session = $this->requestStack->getSession();
            $recipeFoods = $session->get('recipe_foods');
        }

        // dd($recipeFoods);
        foreach ($recipeFoods as $foodGroupAlias => $foodRows) {
            
            $totalGrByFoodGroup[$foodGroupAlias] = array_sum(array_column($foodRows, 'quantity_g'));
            
            
            foreach ($foodRows as $foodRow) {
                
                $dishFood = new DishFood();
                if(null === $food = $this->foodRepository->findOneBy(['id' => $foodRow['food']->getId()])) {
                    throw new EntityNotFoundException(sprintf(
                        'L\'aliment #%d n\'existe pas', $foodRow['food']->getId())
                    );
                }

                $dishFood->setFood($food);
                $dishFood->setQuantityG($foodRow["quantity_g"]);
                $dishFood->setQuantityReal($foodRow["quantity"]);
                $dishFood->setUnitMeasure($this->unitMeasureRepository->findOneBy(['alias' => $foodRow["unit_measure"]]));
                $dish->addDishFood($dishFood);
            }
            
        }
        
        $dish->setPrincipalFoodGroup($this->foodGroupRepository->findOneByAlias(
                    array_search(max($totalGrByFoodGroup), $totalGrByFoodGroup))
                );
                
                foreach ($totalGrByFoodGroup as $foodGroupAlias => $quantity) {
                    
                    $foodGroup = $this->foodGroupRepository->findOneByAlias($foodGroupAlias);
                    $dishFoodGroup = new DishFoodGroup($foodGroup, $quantity/$dish->getLengthPersonForRecipe());
                    
                    $dish->addDishFoodGroup($dishFoodGroup);

            $foodGroupParents[] = $foodGroup->getParent()->getId();
            
        }
        
        foreach(array_unique($foodGroupParents) as $foodGroupParentId) {
            
            $dishFoodGroupParent = new DishFoodGroupParent(
                $dish, 
                $this->foodGroupParentRepository->findOneBy(['id' => $foodGroupParentId])
            );
            
            $dish->addDishFoodGroupParent($dishFoodGroupParent);
        }
        
        return $dish;
    }
    
    public function removeDishFoodElement(Dish $dish): bool
    {
        foreach ($this->stepRecipeRepository->findByDish($dish) as $stepRecipe) {
            if (!$dish->getStepRecipes()->contains($stepRecipe)) {
                $this->manager->remove($stepRecipe);
            }
        }

        if(!empty($dish->getDishFoods()->toArray()))
        {
            foreach ($dish->getDishFoods() as $dishFood) {
                $this->manager->remove($dishFood);
            }
            foreach ($dish->getDishFoodGroups()->toArray() as $dishFoodGroup) {
                $this->manager->remove($dishFoodGroup);
            }
            foreach ($dish->getDishFoodGroupParents()->toArray() as $dishFoodGroupParent) {
                $this->manager->remove($dishFoodGroupParent);
            }

        }
        
        return true;
    }
}