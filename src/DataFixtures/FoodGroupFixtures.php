<?php

namespace App\DataFixtures;

use App\Service\FoodGroupUtils;
use App\Entity\FoodGroup\FoodGroup;
use Doctrine\Persistence\ObjectManager;
use App\Entity\FoodGroup\FoodGroupParent;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class FoodGroupFixtures extends Fixture implements FixtureGroupInterface
{
    private $foodGroupUtils;

    public function __construct(FoodGroupUtils $foodGroupUtils)
    {
        $this->foodGroupUtils = $foodGroupUtils;
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->foodGroupUtils->getDatas() as $key => $fgpItem) {

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

            $this->addReference(sprintf('%s_%s', 'food_group_parents', $fgpItem['alias']), $foodGroupParent);

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

                $this->addReference(sprintf('%s_%s', 'food_groups', $fgItem['alias']), $foodGroup);
            }
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['food_groups', 'foods', 'dev', 'test'];
    }
}