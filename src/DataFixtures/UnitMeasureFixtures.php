<?php

namespace App\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use App\Entity\UnitMeasure;
use App\Entity\UnitTime;

/**
 * UnitMeasureFixtures.php
 *
 * Fixtures pour les unités de mesure et unités de temps utilisées dans les recettes.
 *
 * - UnitTime : heures, minutes pour les temps de préparation/cuisson
 * - UnitMeasure : kg, g, mg, litres, ml, unités, portions, tranches, etc.
 */
class UnitMeasureFixtures extends Fixture implements FixtureGroupInterface
{
    /**
     * Charge les unités dans la base
     *
     * @param ObjectManager $manager
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        // ======================
        // UNITÉS DE TEMPS POUR LES RECETTES
        // ======================

        // Heure
        $unitTime = new UnitTime();
        $unitTime->setText('Heure');
        $unitTime->setAlias('h');
        $manager->persist($unitTime);
        $this->addReference(sprintf('%s_%s', 'unit_times', $unitTime->getAlias()), $unitTime);

        // Minute
        $unitTime = new UnitTime();
        $unitTime->setText('Minute');
        $unitTime->setAlias('min');
        $manager->persist($unitTime);
        $this->addReference(sprintf('%s_%s', 'unit_times', $unitTime->getAlias()), $unitTime);

        // ======================
        // UNITÉS DE MESURE POUR LES ALIMENTS
        // ======================

        // Kilogramme
        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Kilogramme'); // Nom complet
        $unitmeasure->setAlias('kg');       // Alias utilisé dans le code
        $unitmeasure->setGramRatio(1000);   // 1 kg = 1000 g
        $unitmeasure->setIsUnit(0);         // Non une unité (poids mesurable)
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        // Centigramme
        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Centigramme');
        $unitmeasure->setAlias('cg');
        $unitmeasure->setGramRatio(100);
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        // Décigramme
        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Décigramme');
        $unitmeasure->setAlias('dg');
        $unitmeasure->setGramRatio(10);
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        // Gramme
        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Gramme');
        $unitmeasure->setAlias('g');
        $unitmeasure->setGramRatio(1); // base pour conversion
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        // Milligramme
        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Milligramme');
        $unitmeasure->setAlias('mg');
        $unitmeasure->setGramRatio(0.001);
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        // Litre
        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Litre');
        $unitmeasure->setAlias('l');
        $unitmeasure->setGramRatio(1000); // 1 litre = 1000 g pour conversion (approximative pour l'eau)
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        // Décilitre
        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Décilitre');
        $unitmeasure->setAlias('dl');
        $unitmeasure->setGramRatio(100); // 0.1 litre = 100 g
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        // Centilitre
        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Centilitre');
        $unitmeasure->setAlias('cl');
        $unitmeasure->setGramRatio(10); // 0.01 litre = 10 g
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        // Millilitre
        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Millilitre');
        $unitmeasure->setAlias('ml');
        $unitmeasure->setGramRatio(1); // 1 ml = 1 g pour approximation
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        // Unité (ex: 1 pomme, 1 oeuf)
        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Unité');
        $unitmeasure->setAlias('u');
        $unitmeasure->setGramRatio(null); // pas applicable
        $unitmeasure->setIsUnit(1);       // une unité comptable
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        // Portion
        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Portion');
        $unitmeasure->setAlias('p');
        $unitmeasure->setGramRatio(null);
        $unitmeasure->setIsUnit(1);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        // Tranche
        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Tranche');
        $unitmeasure->setAlias('tr');
        $unitmeasure->setGramRatio(null);
        $unitmeasure->setIsUnit(1);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        // Écriture finale en base
        $manager->flush();
    }

    /**
     * Définition des groupes de fixtures
     *
     * @return array
     */
    public static function getGroups(): array
    {
        return ['unit_times', 'unit_measures', 'foods', 'dev', 'test'];
    }
}