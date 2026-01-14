<?php

namespace App\DataFixtures;


use App\Entity\Diet\Diet;
use App\Entity\Diet\SubDiet;
use App\DataFixtures\BaseFixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class DietFixtures extends BaseFixture implements FixtureGroupInterface
{
    protected function loadData(ObjectManager $manager)
    {
        // $noMeat = new Diet();
        // $noMeat->setName('Sans produit animaux');
        // $noMeat->setDescription('Ne mange pas de viande');
        // $manager->persist($noMeat);
        
        $vegan = new Diet();
        $vegan->setName('Véganisme');
        $vegan->setDescription('Ne consomme aucun produit issus de l\'exploitation animale');
        $manager->persist($vegan);
        
        $pescoVeg = new Diet();
        $pescoVeg->setName('Pesco-vegetarisme');
        $pescoVeg->setDescription('Ne mange pas de viande sauf du poisson');
        $manager->persist($pescoVeg);

        $ovoVeg = new Diet();
        $ovoVeg->setName('Ovo-végétarisme');
        $ovoVeg->setDescription('Ne mange pas de viande sauf des oeufs');
        $manager->persist($ovoVeg);

        $noGluten = new Diet();
        $noGluten->setName('Sans gluten');
        $noGluten->setDescription('Ne mange pas d\'aliments contenant du gluten');
        $manager->persist($noGluten);

        $noLactose = new Diet();
        $noLactose->setName('Sans lactose');
        $noLactose->setDescription('Ne mange pas d\'aliment à base de lactose');
        $manager->persist($noLactose);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['diets', 'dev', 'test'];
    }
}