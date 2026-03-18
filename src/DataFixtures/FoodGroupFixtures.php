<?php

namespace App\DataFixtures;

use App\Service\FoodGroupHandler;
use App\Entity\FoodGroup\FoodGroup;
use Doctrine\Persistence\ObjectManager;
use App\Entity\FoodGroup\FoodGroupParent;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * FoodGroupFixtures.php
 *
 * Fixtures pour créer les groupes alimentaires et leurs sous-groupes.
 * Utilise le service FoodGroupHandler pour récupérer les données structurées.
 */
class FoodGroupFixtures extends Fixture implements FixtureGroupInterface
{
    /**
     * @param FoodGroupHandler $foodGroupHandler Service pour récupérer les données des groupes alimentaires
     */
    public function __construct(private FoodGroupHandler $foodGroupHandler)
    {}

    /**
     * Charge les données de groupes alimentaires dans la base
     *
     * @param ObjectManager $manager Gestionnaire d'entités Doctrine
     */
    public function load(ObjectManager $manager): void
    {
        // Parcours de tous les groupes parent et leurs enfants
        foreach ($this->foodGroupHandler->getDatas() as $key => $fgpItem) {

            // Création du groupe parent
            $foodGroupParent = new FoodGroupParent();
            $foodGroupParent->setName($fgpItem['name']);
            $foodGroupParent->setShortName($fgpItem['short_name']);
            $foodGroupParent->setSemiShortName($fgpItem['semi_short_name']);
            $foodGroupParent->setAlias($fgpItem['alias']);
            $foodGroupParent->setColor($fgpItem['color']);
            $foodGroupParent->setDegradedColor($fgpItem['degraded_color']);
            $foodGroupParent->setRanking($fgpItem['ranking']);
            $foodGroupParent->setIsPrincipal($fgpItem['principal']);
            
            $manager->persist($foodGroupParent);

            // Référence pour réutilisation dans d'autres fixtures
            $this->addReference(sprintf('%s_%s', 'food_group_parents', $fgpItem['alias']), $foodGroupParent);

            // Création des sous-groupes enfants
            foreach($fgpItem['childs'] as $fgItem)
            {   
                $foodGroup = new FoodGroup();
                $foodGroup->setName($fgItem['name']);
                $foodGroup->setAlias($fgItem['alias']);
                $foodGroup->setShortName($fgItem['short_name']);
                $foodGroup->setSemiShortName($fgItem['semi_short_name']);
                $foodGroup->setRanking($fgItem['ranking']);
                $foodGroup->setParent($foodGroupParent);
               
                $manager->persist($foodGroup);

                // Référence pour réutilisation dans d'autres fixtures
                $this->addReference(sprintf('%s_%s', 'food_groups', $fgItem['alias']), $foodGroup);
            }
        }

        // Enregistrement final en base
        $manager->flush();
    }

    /**
     * Retourne les groupes de fixtures
     *
     * @return array Liste des groupes
     */
    public static function getGroups(): array
    {
        return ['food_groups', 'foods', 'dev', 'test'];
    }
}