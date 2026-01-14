<?php

namespace App\DataFixtures;


use App\Entity\Alert\Alert;
use App\Entity\Alert\Level;
use App\DataFixtures\BaseFixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class AlertFixtures extends BaseFixture implements FixtureGroupInterface
{
    protected function loadData(ObjectManager $manager)
    {
        $level1 = new Level();
        $level1->setText('Très fortement déconseillé');
        $level1->setDetailedText('très fortement les quantités conseillées de %s');
        $level1->setColor('#000');
        $level1->setCode('strongly_not_recommended');
        $level1->setPriority(1);
       
        $manager->persist($level1);

        $level2 = new Level();
        $level2->setText('Fortement déconseillé');
        $level2->setDetailedText('fortement les quantités conseillées de %s');
        $level2->setColor('#c72200');
        $level2->setCode('highly_not_recommended');
        $level2->setPriority(2);
       
        $manager->persist($level2);

        $level3 = new Level();
        $level3->setText('Déconseillé');
        $level3->setDetailedText('modérément les quantités conseillées de %s');
        $level3->setColor('#ecd00f');
        $level3->setCode('not_recommended');
        $level3->setPriority(3);
       
        $manager->persist($level3);

        $level4 = new Level();
        $level4->setText('Conseillé');
        $level4->setDetailedText('Conseillé');
        $level4->setColor('#4bb272');
        $level4->setCode('recommended');
        $level4->setPriority(4);
       
        $manager->persist($level4);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['alert', 'dev', 'test'];
    }
}