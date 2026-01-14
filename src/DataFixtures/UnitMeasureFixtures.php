<?php

namespace App\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use App\Entity\UnitMeasure;
use App\Entity\UnitTime;

class UnitMeasureFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        // TYPE OF TIME FOR RECIPE

        $unitTime = new UnitTime();
        $unitTime->setText('Heure');
        $unitTime->setAlias('h');
        $manager->persist($unitTime);
        $this->addReference(sprintf('%s_%s', 'unit_times', $unitTime->getAlias()), $unitTime);

        $unitTime = new UnitTime();
        $unitTime->setText('Minute');
        $unitTime->setAlias('min');
        $manager->persist($unitTime);
        $this->addReference(sprintf('%s_%s', 'unit_times', $unitTime->getAlias()), $unitTime);

        // //UNIT MEASURE

        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Kilogramme');
        $unitmeasure->setAlias('kg');
        $unitmeasure->setGramRatio(1000);
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Centigramme');
        $unitmeasure->setAlias('cg');
        $unitmeasure->setGramRatio(100);
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Décigramme');
        $unitmeasure->setAlias('dg');
        $unitmeasure->setGramRatio(10);
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Gramme');
        $unitmeasure->setAlias('g');
        $unitmeasure->setGramRatio(1);
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Milligramme');
        $unitmeasure->setAlias('mg');
        $unitmeasure->setGramRatio(0.001);
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Litre');
        $unitmeasure->setAlias('l');
        $unitmeasure->setGramRatio(1000);
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Décilitre');
        $unitmeasure->setAlias('dl');
        $unitmeasure->setGramRatio(100);
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);
        
        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Centilitre');
        $unitmeasure->setAlias('cl');
        $unitmeasure->setGramRatio(10);
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Millilitre');
        $unitmeasure->setAlias('ml');
        $unitmeasure->setGramRatio(1);
        $unitmeasure->setIsUnit(0);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Unité');
        $unitmeasure->setAlias('u');
        $unitmeasure->setGramRatio(null);
        $unitmeasure->setIsUnit(1);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Portion');
        $unitmeasure->setAlias('p');
        $unitmeasure->setGramRatio(null);
        $unitmeasure->setIsUnit(1);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        $unitmeasure = new UnitMeasure();
        $unitmeasure->setName('Tranche');
        $unitmeasure->setAlias('tr');
        $unitmeasure->setGramRatio(null);
        $unitmeasure->setIsUnit(1);
        $manager->persist($unitmeasure);
        $this->addReference(sprintf('%s_%s', 'unit_measures', $unitmeasure->getAlias()), $unitmeasure);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['unit_times', 'unit_measures', 'foods', 'dev', 'test'];
    }
}