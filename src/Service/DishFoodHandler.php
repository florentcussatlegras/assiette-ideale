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

/**
 * DishFoodHandler.php
 *
 * Service pour gérer les aliments dans les plats et les recettes.
 * Il permet d'accéder aux aliments, aux groupes alimentaires, aux unités de mesure
 * et aux étapes de recette, ainsi qu'à la gestion de la base de données via l'EntityManager.
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class DishFoodHandler
{
    /**
     * Injection des dépendances via le constructeur
     */
    public function __construct(
        private RequestStack $requestStack,                   // Pour accéder à la session utilisateur
        private FoodRepository $foodRepository,               // Repository pour les aliments
        private FoodGroupRepository $foodGroupRepository,     // Repository pour les groupes alimentaires
        private FoodGroupParentRepository $foodGroupParentRepository, // Repository pour les groupes alimentaires parents
        private UnitMeasureRepository $unitMeasureRepository, // Repository pour les unités de mesure
        private EntityManagerInterface $manager,              // Gestionnaire d'entités pour la base de données
        private StepRecipeRepository $stepRecipeRepository    // Repository pour les étapes de recette
    ) {}

    /**
     * Crée les éléments DishFood, DishFoodGroup et DishFoodGroupParent pour un plat donné (Dish).
     * 
     * Cette méthode fait le lien entre le plat et :
     *  - Les aliments individuels (DishFood) avec leurs quantités réelles et en grammes.
     *  - Les groupes alimentaires (DishFoodGroup) avec les quantités normalisées par portion.
     *  - Les groupes alimentaires parents (DishFoodGroupParent) pour le suivi des catégories principales.
     * 
     * Si aucun tableau $recipeFoods n'est fourni, les données sont récupérées depuis la session sous 'recipe_foods'.
     * 
     * @param Dish  $dish        L'objet plat auquel les éléments seront associés.
     * @param array $recipeFoods Tableau optionnel contenant les aliments organisés par groupe alimentaire.
     * 
     * @return Dish Le plat enrichi avec ses DishFood, DishFoodGroup et DishFoodGroupParent.
     * 
     * @throws EntityNotFoundException Si un aliment référencé n'existe pas en base.
     */
    public function createDishFoodElement(Dish $dish, array $recipeFoods = []): Dish
    {
        // Récupération des aliments depuis la session si le tableau est vide
        if (empty($recipeFoods)) {
            $session = $this->requestStack->getSession();
            $recipeFoods = $session->get('recipe_foods');
        }

        // Calcul des quantités totales par groupe alimentaire
        foreach ($recipeFoods as $foodGroupAlias => $foodRows) {
            $totalGrByFoodGroup[$foodGroupAlias] = array_sum(array_column($foodRows, 'quantity_g'));

            foreach ($foodRows as $foodRow) {
                // Création d'un DishFood pour chaque aliment
                $dishFood = new DishFood();

                // Vérification que l'aliment existe bien en base
                if (null === $food = $this->foodRepository->findOneBy(['id' => $foodRow['food']->getId()])) {
                    throw new EntityNotFoundException(
                        sprintf('L\'aliment #%d n\'existe pas', $foodRow['food']->getId())
                    );
                }

                // Affectation des propriétés de l'aliment au DishFood
                $dishFood->setFood($food);
                $dishFood->setQuantityG($foodRow["quantity_g"]);
                $dishFood->setQuantityReal($foodRow["quantity"]);
                $dishFood->setUnitMeasure(
                    $this->unitMeasureRepository->findOneBy(['alias' => $foodRow["unit_measure"]])
                );

                // Association au plat
                $dish->addDishFood($dishFood);
            }
        }

        // Détermination du groupe alimentaire principal du plat
        $dish->setPrincipalFoodGroup(
            $this->foodGroupRepository->findOneByAlias(
                array_search(max($totalGrByFoodGroup), $totalGrByFoodGroup)
            )
        );

        // Création des DishFoodGroup pour chaque groupe alimentaire
        foreach ($totalGrByFoodGroup as $foodGroupAlias => $quantity) {
            $foodGroup = $this->foodGroupRepository->findOneByAlias($foodGroupAlias);
            $dishFoodGroup = new DishFoodGroup(
                $foodGroup,
                $quantity / $dish->getLengthPersonForRecipe() // Quantité par portion
            );

            $dish->addDishFoodGroup($dishFoodGroup);

            $foodGroupParents[] = $foodGroup->getParent()->getId();
        }

        // Création des DishFoodGroupParent pour les groupes parents uniques
        foreach (array_unique($foodGroupParents) as $foodGroupParentId) {
            $dishFoodGroupParent = new DishFoodGroupParent(
                $dish,
                $this->foodGroupParentRepository->findOneBy(['id' => $foodGroupParentId])
            );

            $dish->addDishFoodGroupParent($dishFoodGroupParent);
        }

        return $dish;
    }

    /**
     * Supprime tous les éléments liés à un plat (Dish) : DishFood, DishFoodGroup, DishFoodGroupParent
     * ainsi que les étapes de recette (StepRecipe) associées qui ne sont plus liées au plat.
     * 
     * Cette méthode s'assure que toutes les relations entre le plat et ses aliments/groupes
     * sont nettoyées dans l'EntityManager avant une éventuelle suppression ou modification du plat.
     * 
     * @param Dish $dish Le plat dont on souhaite supprimer les éléments liés.
     * 
     * @return bool Retourne true une fois que toutes les suppressions ont été programmées dans l'EntityManager.
     */
    public function removeDishFoodElement(Dish $dish): bool
    {
        // Suppression des étapes de recette associées au plat si elles ne sont plus liées
        foreach ($this->stepRecipeRepository->findByDish($dish) as $stepRecipe) {
            if (!$dish->getStepRecipes()->contains($stepRecipe)) {
                $this->manager->remove($stepRecipe);
            }
        }

        // Vérifie si le plat contient des DishFood
        if (!empty($dish->getDishFoods()->toArray())) {

            // Supprime tous les DishFood liés
            foreach ($dish->getDishFoods() as $dishFood) {
                $this->manager->remove($dishFood);
            }

            // Supprime tous les DishFoodGroup liés
            foreach ($dish->getDishFoodGroups()->toArray() as $dishFoodGroup) {
                $this->manager->remove($dishFoodGroup);
            }

            // Supprime tous les DishFoodGroupParent liés
            foreach ($dish->getDishFoodGroupParents()->toArray() as $dishFoodGroupParent) {
                $this->manager->remove($dishFoodGroupParent);
            }
        }

        return true;
    }
}
