<?php

namespace App\DataFixtures;


use App\Entity\TypeMeal;
use App\DataFixtures\BaseFixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class TypeMealFixtures extends BaseFixture implements FixtureGroupInterface
{
    protected function loadData(ObjectManager $manager)
    {
        $breakfast = new TypeMeal();
        $breakfast->setBackname('type_meal.breakfast');
        $breakfast->setFrontname('Petit déjeuner');
        $breakfast->setShortCut('P. déj');
        $breakfast->setRanking(0);
        $breakfast->setIsSnack(0);

        $manager->persist($breakfast);

        $morningSnack = new TypeMeal();
        $morningSnack->setBackname('type_meal.snack_morning');
        $morningSnack->setFrontname('Collation du matin');
        $morningSnack->setShortCut('Coll mat');
        $morningSnack->setRanking(1);
        $morningSnack->setIsSnack(1);

        $manager->persist($morningSnack);

        $lunch = new TypeMeal();
        $lunch->setBackname('type_meal.lunch');
        $lunch->setFrontname('Déjeuner');
        $lunch->setShortCut('Déj');
        $lunch->setRanking(2);
        $lunch->setIsSnack(0);

        $manager->persist($lunch);

        $afternoonSnack = new TypeMeal();
        $afternoonSnack->setBackname('type_meal.snack_afternoon');
        $afternoonSnack->setFrontname('Collation de l\'après-midi');
        $afternoonSnack->setShortCut('Coll aprèm');
        $afternoonSnack->setRanking(3);
        $afternoonSnack->setIsSnack(1);

        $manager->persist($afternoonSnack);

        $dinner = new TypeMeal();
        $dinner->setBackname('type_meal.dinner');
        $dinner->setFrontname('Dîner');
        $dinner->setShortCut('Din');
        $dinner->setRanking(4);
        $dinner->setIsSnack(0);

        $manager->persist($dinner);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['type_meals', 'dev', 'test'];
    }
}