<?php

namespace App\DataFixtures;

use App\Entity\Diet\Diet;
use App\Entity\Diet\SubDiet;
use App\DataFixtures\BaseFixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * DietFixtures.php
 *
 * Fixture pour créer les régimes alimentaires de base :
 * - Véganisme
 * - Pesco-végétarisme
 * - Ovo-végétarisme
 * - Sans gluten
 * - Sans lactose
 */
class DietFixtures extends BaseFixture implements FixtureGroupInterface
{
    /**
     * Charge les régimes alimentaires en base.
     *
     * @param ObjectManager $manager
     * @return void
     */
    protected function loadData(ObjectManager $manager): void
    {
        // Régime Végan
        $vegan = new Diet();
        $vegan->setName('Véganisme');
        $vegan->setShortName('Végan');
        $vegan->setDescription('Ne consomme aucun produit issus de l\'exploitation animale');
        $manager->persist($vegan);

        // Régime Pesco-végétarien
        $pescoVeg = new Diet();
        $pescoVeg->setName('Pesco-vegetarisme');
        $pescoVeg->setShortName('Pesco-végan');
        $pescoVeg->setDescription('Ne mange pas de viande sauf du poisson');
        $manager->persist($pescoVeg);

        // Régime Ovo-végétarien
        $ovoVeg = new Diet();
        $ovoVeg->setName('Ovo-végétarisme');
        $ovoVeg->setShortName('Ovo-végan');
        $ovoVeg->setDescription('Ne mange pas de viande sauf des oeufs');
        $manager->persist($ovoVeg);

        // Régime Sans gluten
        $noGluten = new Diet();
        $noGluten->setName('Sans gluten');
        $noGluten->setShortName('Sans gluten');
        $noGluten->setDescription('Ne mange pas d\'aliments contenant du gluten');
        $manager->persist($noGluten);

        // Régime Sans lactose
        $noLactose = new Diet();
        $noLactose->setName('Sans lactose');
        $noLactose->setShortName('Sans lactose'); // Correction de la typo
        $noLactose->setDescription('Ne mange pas d\'aliment à base de lactose');
        $manager->persist($noLactose);

        $manager->flush();
    }

    /**
     * Retourne les groupes de fixtures pour ce fichier.
     *
     * @return array
     */
    public static function getGroups(): array
    {
        return ['diets', 'dev', 'test'];
    }
}