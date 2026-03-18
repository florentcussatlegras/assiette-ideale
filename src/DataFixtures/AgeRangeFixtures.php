<?php

namespace App\DataFixtures;

use App\Entity\AgeRange;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

/**
 * AgeRangeFixtures.php
 *
 * Fixtures pour les tranches d'âge.
 * Chaque tranche d'âge contient :
 * - un âge minimum et maximum
 * - une description lisible
 * - un coefficient énergétique (utilisé pour ajuster les besoins caloriques)
 */
class AgeRangeFixtures extends Fixture
{
    /**
     * Charge les tranches d'âge dans la base
     *
     * @param ObjectManager $manager
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        // Tableau des tranches d'âge avec leurs propriétés
        $tabsrange = [
            ['min' => 0,   'max' => 18,   'description' => 'Vous avez moins de 18 ans',  'coeff_energy' => 0.86],
            ['min' => 19,  'max' => 33,   'description' => 'Vous avez entre 18 et 33 ans', 'coeff_energy' => 1],
            ['min' => 34,  'max' => 43,   'description' => 'Vous avez entre 34 et 43 ans', 'coeff_energy' => 0.98],
            ['min' => 44,  'max' => 53,   'description' => 'Vous avez entre 44 et 53 ans', 'coeff_energy' => 0.96],
            ['min' => 54,  'max' => 63,   'description' => 'Vous avez entre 54 et 63 ans', 'coeff_energy' => 0.94],
            ['min' => 64,  'max' => 73,   'description' => 'Vous avez entre 64 et 73 ans', 'coeff_energy' => 0.92],
            ['min' => 74,  'max' => 83,   'description' => 'Vous avez entre 74 et 83 ans', 'coeff_energy' => 0.9],
            ['min' => 84,  'max' => 93,   'description' => 'Vous avez entre 84 et 93 ans', 'coeff_energy' => 0.88],
            ['min' => 94,  'max' => 10000,'description' => 'Vous avez plus de 94 ans',      'coeff_energy' => 0.86],
        ];

        // Boucle sur chaque tranche d'âge pour créer et persister les entités
        foreach($tabsrange as $tabAgerange)
        {
            $agerange = new AgeRange();

            // Définition des limites de la tranche
            $agerange->setAgeMin($tabAgerange['min']);
            $agerange->setAgeMax($tabAgerange['max']);

            // Code unique basé sur la tranche (ex: 0_18, 19_33)
            $agerange->setCode(strtoupper(sprintf('%s_%s', $tabAgerange['min'], $tabAgerange['max'])));

            // Description lisible pour l'interface
            $agerange->setDescription($tabAgerange['description']);

            // Coefficient énergétique pour ajuster les besoins caloriques selon l'âge
            $agerange->setCoeffEnergy($tabAgerange['coeff_energy']);
            
            $manager->persist($agerange);
        }

        // Écriture finale en base
        $manager->flush();
    }
}