<?php

namespace App\DataFixtures;

use App\Entity\AgeRange;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AgeRangeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $tabsAgerange = array_map(
        //     function($ageMin, $agemax) {
        //         return [$ageMin, $agemax];
        //     }, 
        //     range(34, 84, 10), 
        //     range(43, 93, 10)
        // );

        // array_unshift($tabsAgerange, [0, 18], [19, 33], [94, 10000]);

        $tabsrange = [
            [
                'min' => 0,
                'max' => 18,
                'description' => 'Vous avez moins de 18 ans',
                'coeff_energy' => 0.86
            ],
            [
                'min' => 19,
                'max' => 33,
                'description' => 'Vous avez entre 18 et 33 ans',
                'coeff_energy' => 1
            ],
            [
                'min' => 34,
                'max' => 43,
                'description' => 'Vous avez entre 34 et 43 ans',
                'coeff_energy' => 0.98
            ],
            [
                'min' => 44,
                'max' => 53,
                'description' => 'Vous avez entre 44 et 53 ans',
                'coeff_energy' => 0.96
            ],
            [
                'min' => 54,
                'max' => 63,
                'description' => 'Vous avez entre 54 et 63 ans',
                'coeff_energy' => 0.94
            ],
            [
                'min' => 64,
                'max' => 73,
                'description' => 'Vous avez entre 64 et 73 ans',
                'coeff_energy' => 0.92
            ],
            [
                'min' => 74,
                'max' => 83,
                'description' => 'Vous avez entre 74 et 83 ans',
                'coeff_energy' => 0.9
            ],
            [
                'min' => 84,
                'max' => 93,
                'description' => 'Vous avez entre 84 et 93 ans',
                'coeff_energy' => 0.88
            ],
            [
                'min' => 94,
                'max' => 10000,
                'description' => 'Vous avez plus de 94 ans',
                'coeff_energy' => 0.86
            ],
        ];

        foreach($tabsrange as $tabAgerange)
        {
            $agerange = new AgeRange();
            $agemin = $tabAgerange['min'];
            $agemax = $tabAgerange['max'];
            $agerange->setAgeMin($agemin);
            $agerange->setAgeMax($agemax);
            $agerange->setCode(strtoupper(sprintf('%s_%s', $agemin, $agemax)));
            $agerange->setDescription($tabAgerange['description']);
            $agerange->setCoeffEnergy($tabAgerange['coeff_energy']);
            
            $manager->persist($agerange);

        }

        $manager->flush();
    }
}
