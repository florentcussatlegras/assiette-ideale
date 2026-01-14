<?php

namespace App\EntityListener;

use App\Entity\Dish;
use App\Repository\FoodRepository;
use App\Util\TransformerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\String\Slugger\SluggerInterface;

class DishEntityListener implements TransformerInterface 
{
    private $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function editGlutenLactose($dish) {

        $dish->setHaveLactose(false);
        $dish->setHaveGluten(false);
        
        foreach($dish->getDishFoods() as $dishFood) {
            if($dishFood->getFood()->isHaveLactose()) {
                $dish->sethaveLactose(true);
            }
            if($dishFood->getFood()->isHaveGluten()) {
                $dish->sethaveGluten(true);
            }
        }

        return $dish;

    }

    public function prePersist(Dish $dish, LifecycleEventArgs $event)
    {
        $dish = $this->editGlutenLactose($dish);

        $dish->computeSlug($this->slugger);
    }

    public function preUpdate(Dish $dish, LifecycleEventArgs $event)
    {
        $dish = $this->editGlutenLactose($dish);

        $dish->computeSlug($this->slugger);
    }

    public function transform(): void
    {
     
    }
}