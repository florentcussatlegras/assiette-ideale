<?php

namespace App\DataFixtures;

use App\Entity\Food;
use App\Service\FoodGroupHandler;
use App\Service\UploaderHelper;
use App\DataFixtures\BaseFixture;
use App\DataFixtures\FoodGroupFixtures;
use App\DataFixtures\UnitMeasureFixtures;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Filesystem;

/**
 * FoodFixtures.php
 *
 * Fixtures pour créer des aliments et les associer aux groupes alimentaires.
 */
class FoodFixtures extends BaseFixture implements FixtureGroupInterface, DependentFixtureInterface
{
    /**
     * @var FoodGroupHandler
     */
    public function __construct(
        private FoodGroupHandler $foodGroupHandler,
        private UploaderHelper $uploaderHelper,
        private string $env = 'dev'
    ) {}

    /**
     * Crée un aliment
     *
     * @param string $foodGroupCode Alias du groupe alimentaire
     * @param bool $isSubFoodGroup Indique si c'est un sous-groupe
     *
     * @return Food
     */
    private function create(string $foodGroupCode, bool $isSubFoodGroup = false): Food
    {
        $food = new Food();
        $food->setIsSubFoodGroup($isSubFoodGroup);
        $food->setName($this->faker->word());

        // Gestion des images selon l'environnement
        if ($this->env === 'dev') {
            $fileName = strtolower($foodGroupCode) . '.jpg';
            $fs = new Filesystem();
            $targetPath = sys_get_temp_dir() . '/' . $fileName;
            $fs->copy(__DIR__ . '/images/' . $fileName, $targetPath, true);
            $pictureFileName = $this->uploaderHelper->upload(new File($targetPath), UploaderHelper::FOOD);
            $food->setPicture($pictureFileName);
        } elseif ($this->env === 'test') {
            $food->setPicture(strtolower($foodGroupCode) . '.jpg');
        }

        // Association au groupe alimentaire
        $foodGroup = $this->getReference(sprintf('%s_%s', 'food_groups', $foodGroupCode));
        $food->setFoodGroup($foodGroup);

        // Valeurs aléatoires pour les propriétés nutritionnelles et unités
        $food->setMedianWeight(random_int(0, 500));
        $food->setShowMedianWeight($this->faker->boolean());
        $food->addUnitMeasure($this->getReference('unit_measures_g'));
        $food->addUnitMeasure($this->getReference('unit_measures_kg'));
        $food->setCanBeAPart($this->faker->boolean());
        $food->setHaveGluten($this->faker->boolean());
        $food->setNotConsumableRaw($this->faker->boolean());

        return $food;
    }

    /**
     * Charge les données dans la base
     *
     * @param ObjectManager $manager
     */
    protected function loadData(ObjectManager $manager): void
    {
        // Parcours de tous les groupes alimentaires pour créer des aliments
        foreach ($this->foodGroupHandler->getFoodGroupAlias() as $foodGroupAlias) {

            // Création de 3 aliments pour les sous-groupes
            $this->createMany(3, 'foods_is_sub_food_group_' . $foodGroupAlias, function($i) use ($foodGroupAlias) {
                return $this->create($foodGroupAlias, true);
            });

            // Création de 10 aliments pour les groupes principaux
            $this->createMany(10, 'foods_is_not_sub_food_group_' . $foodGroupAlias, function($i) use ($foodGroupAlias) {
                return $this->create($foodGroupAlias);
            });
        }

        $manager->flush();
    }

    /**
     * Dépendances de fixtures nécessaires
     *
     * @return array
     */
    public function getDependencies(): array
    {
        return [
            FoodGroupFixtures::class,
            UnitMeasureFixtures::class,
        ];
    }

    /**
     * Groupes de fixtures
     *
     * @return array
     */
    public static function getGroups(): array
    {
        return ['foods', 'food_groups', 'unit_measures', 'dev', 'test'];
    }
}