<?php

namespace App\DataFixtures;

use App\Entity\Alert\LevelAlert;
use App\DataFixtures\BaseFixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * AlertFixtures.php
 *
 * Fixtures pour les niveaux d'alerte des aliments.
 * Chaque niveau contient :
 * - un texte descriptif
 * - un texte de placeholder pour les messages dynamiques
 * - une couleur associée
 * - un code unique
 * - une priorité (1 = très important, 4 = recommandé)
 */
class AlertFixtures extends BaseFixture implements FixtureGroupInterface
{
    /**
     * Charge les données d'alertes dans la base
     *
     * @param ObjectManager $manager
     * @return void
     */
    protected function loadData(ObjectManager $manager)
    {
        // ======================
        // Niveau 1 : Très fortement déconseillé
        // ======================
        $level1 = new LevelAlert();
        $level1->setText('Très fortement déconseillé');
        $level1->setPlaceholderText('très fortement les quantités conseillées de %s');
        $level1->setColor('#000'); // noir
        $level1->setCode('strongly_not_recommended');
        $level1->setPriority(1); // priorité maximale
        $manager->persist($level1);

        // ======================
        // Niveau 2 : Fortement déconseillé
        // ======================
        $level2 = new LevelAlert();
        $level2->setText('Fortement déconseillé');
        $level2->setPlaceholderText('fortement les quantités conseillées de %s');
        $level2->setColor('#c72200'); // rouge vif
        $level2->setCode('highly_not_recommended');
        $level2->setPriority(2);
        $manager->persist($level2);

        // ======================
        // Niveau 3 : Déconseillé
        // ======================
        $level3 = new LevelAlert();
        $level3->setText('Déconseillé');
        $level3->setPlaceholderText('modérément les quantités conseillées de %s');
        $level3->setColor('#ecd00f'); // jaune
        $level3->setCode('not_recommended');
        $level3->setPriority(3);
        $manager->persist($level3);

        // ======================
        // Niveau 4 : Conseillé
        // ======================
        $level4 = new LevelAlert();
        $level4->setText('Conseillé');
        $level4->setPlaceholderText('Conseillé'); // message générique
        $level4->setColor('#4bb272'); // vert
        $level4->setCode('recommended');
        $level4->setPriority(4); // priorité minimale
        $manager->persist($level4);

        // Persist toutes les entités en base
        $manager->flush();
    }

    /**
     * Groupes de fixtures pour cette classe
     *
     * @return array
     */
    public static function getGroups(): array
    {
        return ['alert', 'dev', 'test'];
    }
}