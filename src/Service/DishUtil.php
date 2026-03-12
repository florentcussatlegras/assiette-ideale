<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\DishFoodGroup;
use App\Entity\FoodGroup\FoodGroupParent;
use App\Repository\DishRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\FoodUtil;

/**
 * DishUtil.php
 * 
 * Service utilitaire pour les entités Dish.
 *
 * Objectif :
 *  - Fournir des méthodes pour manipuler, filtrer et analyser les plats.
 *  - Calculer les quantités par groupe alimentaire pour un nombre de portions donné.
 *  - Filtrer les plats en excluant les aliments interdits (forbidden).
 *  - Permettre des recherches complexes par mots-clés, groupes et contraintes nutritionnelles.
 *
 * Points clés :
 *  - Les méthodes `myFind*ExcludeForbidden` combinent recherche et filtre d’aliments interdits.
 *  - Les méthodes de calcul (`getFoodGroupParentQuantitiesForNPortion`) sont multiplications-aware pour n portions.
 *  - La méthode `orderObjectByName` fournit un comparateur pour tri alphabétique.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class DishUtil
{
    public function __construct(
        private EntityManagerInterface $manager, 
        private FoodUtil $foodUtil,
        private DishRepository $dishRepository
    ) {}

    /**
     * Calcule les quantités par groupe alimentaire parent pour un nombre de portions donné.
     */
    public function getFoodGroupParentQuantitiesForNPortion(Dish $dish, int $nPortion): array
    {
        $quantities = [];

        foreach ($this->manager->getRepository(FoodGroupParent::class)->findAll() as $fgp) {
            $quantities[$fgp->getAlias()] = 0;

            foreach ($this->manager->getRepository(DishFoodGroup::class)->findByDishAndFoodGroupParent($dish, $fgp) as $dfg) {
                $quantities[$fgp->getAlias()] += $dfg->getQuantityForOne();
            }

            $quantities[$fgp->getAlias()] *= $nPortion;
        }

        return $quantities;
    }

    /**
     * Filtre les plats selon la quantité d’un groupe alimentaire parent.
     */
    public function getByQuantityFoodGroupParent(array $dishes, string $fgpAlias, float $qtyMin, float $qtyMax): array
    {
        $results = [];

        foreach ($dishes as $dish) {
            $quantity = $this->getFoodGroupParentQuantitiesForNPortion($dish, 1)[$fgpAlias];

            if ($quantity >= $qtyMin && $quantity <= $qtyMax) {
                $results[] = $dish;
            }
        }

        return $results;
    }

    /**
     * Vérifie si un plat contient des aliments interdits.
     */
    public function isForbidden(Dish $dish): bool
    {
        foreach ($dish->getDishFoods()->toArray() as $dishFood) {
            if ($this->foodUtil->isForbidden($dishFood->getFood())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recherche des plats par mot-clé et groupe alimentaire parent en excluant les aliments interdits.
     */
    public function myFindByKeywordAndFGPExcludeFordidden(string $keyword, string $fgp, int $offset, int $limit): array
    {
        $results = [];
    
        foreach ($this->manager->getRepository(Dish::class)->myFindByKeywordAndFGP($keyword, $fgp, $offset, $limit) as $dish) {
            if (!$this->isForbidden($dish)) {
                $results[] = $dish;
            }
        }

        return $results;
    }

    /**
     * Recherche des plats par mot-clé, liste de groupes, type et contraintes lactose/gluten,
     * en excluant les aliments interdits.
     */
    public function myFindByKeywordAndFGAndTypeAndLactoseAndGlutenExcludeForbidden(
        string $keyword,
        array $fglist,
        bool $freeLactose,
        bool $freeGluten
    ): array
    {
        $results = [];

        foreach ($this->dishRepository->myFindByKeywordAndFGAndTypeAndLactoseAndGluten($keyword, $fglist, $freeLactose, $freeGluten) as $dish) {
            if (!$this->isForbidden($dish)) {
                $results[] = $dish;
            }
        }

        return $results;
    }

    /**
     * Retourne tous les plats en excluant les aliments interdits.
     */
    public function myFindAllExcludeForbidden(): array
    {
        $results = [];

        foreach ($this->manager->getRepository(Dish::class)->findAll() as $dish) {
            if (!$this->isForbidden($dish)) {
                $results[] = $dish;
            }
        }

        return $results;
    }

    /**
     * Recherche des plats par groupe alimentaire et plage de quantités, excluant les aliments interdits.
     */
    public function myFindByGroupAndQuantityRangeExcludeForbidden(string $fgpAlias, float $qtyMin, float $qtyMax): array
    {
        $results = [];

        foreach ($this->manager->getRepository(Dish::class)->myFindByGroupAndQuantityRange($fgpAlias, $qtyMin, $qtyMax) as $dish) {
            if (!$this->isForbidden($dish)) {
                $results[] = $dish;
            }
        }

        return $results;
    }

    /**
     * Comparateur pour trier des objets Dish par nom alphabétique.
     */
    public function orderObjectByName($a, $b): int
    {
        return $a->getName() <=> $b->getName();
    }
}