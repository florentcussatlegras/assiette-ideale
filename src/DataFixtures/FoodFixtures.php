<?php

namespace App\DataFixtures;

use App\Entity\Food;
use App\Service\FoodGroupUtils;
use App\Service\UploaderHelper;
use App\DataFixtures\BaseFixture;
use App\DataFixtures\FoodGroupFixtures;
use Doctrine\Persistence\ObjectManager;
use App\DataFixtures\UnitMeasureFixtures;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Filesystem;

// class FoodFixtures extends BaseFixture implements FixtureGroupInterface, DependentFixtureInterface
class FoodFixtures
{
    private $foodGroupUtils;
    private $uploaderHelper;
    private $env;
    
    public function __construct(FoodGroupUtils $foodGroupUtils, UploaderHelper $uploaderHelper, $env = 'dev')
    {
        $this->foodGroupUtils = $foodGroupUtils;
        $this->uploaderHelper = $uploaderHelper;
        $this->env = $env;
    }

    private function create($foodGroupCode, $isSubFoodGroup = false): Food
    {
        $food = new Food();
        $food->setIsSubFoodGroup($isSubFoodGroup);
        $food->setName($this->faker->word());

        if('dev' === $this->env) {
            $fileName = strtolower($foodGroupCode).'.jpg';
            $fs = new Filesystem();
            $targetPath = sys_get_temp_dir().'/'.$fileName;
            $fs->copy(__DIR__.'/images/'.$fileName, $targetPath, true);
            $pictureFileName =  $this->uploaderHelper->upload(new File($targetPath), UploaderHelper::FOOD);
            $food->setPicture($pictureFileName);
        }elseif('test' === $this->env) {
            $food->setPicture(strtolower($foodGroupCode).'.jpg');
        }
        
        $foodGroup = $this->getReference(sprintf('%s_%s', 'food_groups', $foodGroupCode));
        $food->setFoodGroup($foodGroup);
        $food->setMedianWeight(random_int(0, 500));
        $food->setShowMedianWeight($this->faker->boolean());
        $food->addUnitMeasure($this->getReference('unit_measures_g'));
        $food->addUnitMeasure($this->getReference('unit_measures_kg'));
        $food->setCanBeAPart($this->faker->boolean());
        $food->setHaveGluten($this->faker->boolean());
        $food->setNotConsumableRaw($this->faker->boolean());

        return $food;
    }

    protected function loadData(ObjectManager $manager)
    {
        foreach($this->foodGroupUtils->getFoodGroupAlias() as $foodGroupAlias) {

            $this->createMany(3, 'foods_is_sub_food_group_'.$foodGroupAlias, function($i) use($foodGroupAlias) {
                return $this->create($foodGroupAlias, true);
            });

            $this->createMany(10, 'foods_is_not_sub_food_group_'.$foodGroupAlias, function($i) use($foodGroupAlias) {
                return $this->create($foodGroupAlias);
            });

        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            FoodGroupFixtures::class,
            UnitMeasureFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['foods', 'food_groups', 'unit_measures', 'dev', 'test'];
    }
}