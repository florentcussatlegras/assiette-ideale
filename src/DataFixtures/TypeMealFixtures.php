<?php

namespace App\DataFixtures;

use App\Entity\TypeMeal;
use App\Service\MealUtil;
use App\DataFixtures\BaseFixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * TypeMealFixtures.php
 *
 * Fixtures pour créer les différents types de repas dans l'application.
 * Utilise MealUtil pour les identifiants techniques (backname).
 *
 * Chaque TypeMeal contient :
 * - backname : identifiant technique ou constante de MealUtil
 * - frontname : nom affiché à l'utilisateur
 * - shortCut : abréviation pour affichage compact
 * - ranking : ordre d'affichage dans la journée
 * - isSnack : indique s'il s'agit d'une collation (1) ou d'un repas principal (0)
 */
class TypeMealFixtures extends BaseFixture implements FixtureGroupInterface
{
    /**
     * Charge les types de repas dans la base
     *
     * @param ObjectManager $manager
     */
    protected function loadData(ObjectManager $manager)
    {
        // ======================
        // Petit-déjeuner
        // ======================
        $breakfast = new TypeMeal();
        $breakfast->setBackname(MealUtil::TYPE_BREAKFAST); // identifiant technique
        $breakfast->setFrontname('Petit déjeuner');        // affichage pour l'utilisateur
        $breakfast->setShortCut('P. déj');                 // abréviation
        $breakfast->setRanking(0);                         // premier repas
        $breakfast->setIsSnack(0);                         // repas principal
        $manager->persist($breakfast);

        // ======================
        // Collation du matin
        // ======================
        $morningSnack = new TypeMeal();
        $morningSnack->setBackname(MealUtil::TYPE_SNACK_MORNING);
        $morningSnack->setFrontname('Collation du matin');
        $morningSnack->setShortCut('Coll mat');
        $morningSnack->setRanking(1);
        $morningSnack->setIsSnack(1);                      // collation
        $manager->persist($morningSnack);

        // ======================
        // Déjeuner
        // ======================
        $lunch = new TypeMeal();
        $lunch->setBackname(MealUtil::TYPE_LUNCH);
        $lunch->setFrontname('Déjeuner');
        $lunch->setShortCut('Déj');
        $lunch->setRanking(2);
        $lunch->setIsSnack(0);                             // repas principal
        $manager->persist($lunch);

        // ======================
        // Collation de l'après-midi
        // ======================
        $afternoonSnack = new TypeMeal();
        $afternoonSnack->setBackname(MealUtil::TYPE_SNACK_AFTERNOON);
        $afternoonSnack->setFrontname('Collation de l\'après-midi');
        $afternoonSnack->setShortCut('Coll aprèm');
        $afternoonSnack->setRanking(3);
        $afternoonSnack->setIsSnack(1);                    // collation
        $manager->persist($afternoonSnack);

        // ======================
        // Dîner
        // ======================
        $dinner = new TypeMeal();
        $dinner->setBackname(MealUtil::TYPE_DINNER);
        $dinner->setFrontname('Dîner');
        $dinner->setShortCut('Din');
        $dinner->setRanking(4);
        $dinner->setIsSnack(0);                            // repas principal
        $manager->persist($dinner);

        // Enregistrement en base
        $manager->flush();
    }

    /**
     * Groupes de fixtures
     *
     * Permet de regrouper les fixtures pour un chargement spécifique
     *
     * @return array
     */
    public static function getGroups(): array
    {
        return ['type_meals', 'dev', 'test'];
    }
}