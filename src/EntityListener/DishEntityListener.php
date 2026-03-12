<?php

namespace App\EntityListener;

use App\Entity\Dish;
use App\Repository\FoodRepository;
use App\Util\TransformerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Écouteur d'entité pour Dish.
 *
 * Objectif :
 *  - Mettre à jour automatiquement les propriétés `haveGluten` et `haveLactose` d'un plat
 *    en fonction des ingrédients associés (`DishFoods`).
 *  - Générer et mettre à jour le slug du plat lors de la création ou de la modification.
 *
 * Points importants :
 *  - S'exécute avant la persistance (`prePersist`) et avant la mise à jour (`preUpdate`).
 *  - Implémente l'interface `TransformerInterface` pour une cohérence avec d'autres entités transformables.
 */
class DishEntityListener implements TransformerInterface 
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    /**
     * Met à jour les flags gluten et lactose du plat
     * en fonction de ses ingrédients.
     */
    public function editGlutenLactose(Dish $dish): Dish
    {
        $dish->setHaveLactose(false);
        $dish->setHaveGluten(false);
        
        foreach ($dish->getDishFoods() as $dishFood) {
            if ($dishFood->getFood()->getHaveLactose()) {
                $dish->setHaveLactose(true);
            }
            if ($dishFood->getFood()->getHaveGluten()) {
                $dish->setHaveGluten(true);
            }
        }

        return $dish;
    }

    /**
     * Doctrine lifecycle callback avant la création en base
     */
    public function prePersist(Dish $dish, LifecycleEventArgs $event): void
    {
        $dish = $this->editGlutenLactose($dish);
        $dish->computeSlug($this->slugger);
    }

    /**
     * Doctrine lifecycle callback avant la mise à jour en base
     */
    public function preUpdate(Dish $dish, LifecycleEventArgs $event): void
    {
        $dish = $this->editGlutenLactose($dish);
        $dish->computeSlug($this->slugger);
    }

    /**
     * Méthode requise par TransformerInterface
     * actuellement vide pour cette entité
     */
    public function transform(): void
    {
        // Implémentation future si nécessaire
    }
}