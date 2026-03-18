<?php

namespace App\DataFixtures;

use App\Entity\Dish;
use App\Entity\Spice;
use App\Service\FoodUtil;
use App\Entity\StepRecipe;
use App\Service\FoodGroupHandler;
use App\Service\UploaderHelper;
use App\Service\DishFoodHandler;
use App\DataFixtures\BaseFixture;
use App\DataFixtures\FoodFixtures;
use App\Repository\FoodRepository;
use Doctrine\Persistence\ObjectManager;
use App\Repository\UnitMeasureRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

/**
 * DishDevFixtures.php
 *
 * Fixture de développement pour générer des plats aléatoires avec :
 * - étapes de recette
 * - image
 * - aliments associés par groupes alimentaires
 */
class DishDevFixtures extends BaseFixture implements FixtureGroupInterface, DependentFixtureInterface
{
    /**
     * @param FoodRepository $foodRepository
     * @param UnitMeasureRepository $unitMeasureRepository
     * @param DishFoodHandler $dishFoodHandler
     * @param FoodGroupHandler $foodGroupHandler
     * @param FoodUtil $foodUtil
     * @param UploaderHelper $uploaderHelper
     */
    public function __construct(
        private FoodRepository $foodRepository,
        private UnitMeasureRepository $unitMeasureRepository,
        private DishFoodHandler $dishFoodHandler,
        private FoodGroupHandler $foodGroupHandler,
        private FoodUtil $foodUtil,
        private UploaderHelper $uploaderHelper
    ) {}

    /**
     * Fichier : DishDevFixtures.php
     *
     * Crée un objet Dish complet avec :
     * - informations de base
     * - étapes de recette aléatoires
     * - image associée
     * - aliments associés par groupe alimentaire
     *
     * @param int $i Index utilisé pour la génération
     * @return Dish
     */
    private function create(int $i): Dish
    {
        $dish = new Dish();
        $dish->setName($this->faker->word());
        $dish->setLengthPersonForRecipe(random_int(1, 6));
        $dish->setPreparationTime(random_int(1, 4));
        $dish->setPreparationTimeUnitTime($this->getReference('unit_times_h'));
        $dish->setCookingTime(random_int(5, 59));
        $dish->setCookingTimeUnitTime($this->getReference('unit_times_min'));

        // Génération aléatoire des étapes de recette
        for ($j = 0; $j < random_int(1, 5); $j++) {
            $stepRecipe = new StepRecipe();
            $stepRecipe->setRankStep($j);
            $stepRecipe->setDescription($this->faker->text());
            $dish->addStepRecipe($stepRecipe);
        }

        // Gestion des images de plats
        switch ((int)($i / 10)) {
            case 0: $fileName = 'plat1.jpeg'; break;
            case 1: $fileName = 'plat2.jpeg'; break;
            case 2: $fileName = 'plat3.jpeg'; break;
            case 3: $fileName = 'plat4.jpeg'; break;
            case 4: $fileName = 'plat5.jpeg'; break;
            case 5: $fileName = 'plat6.jpeg'; break;
            case 6: $fileName = 'plat7.jpeg'; break;
            case 7: $fileName = 'plat8.jpeg'; break;
            case 8: $fileName = 'plat9.jpeg'; break;
            case 9: $fileName = 'plat10.jpeg'; break;
            default: $fileName = 'plat1.jpeg';
        }

        $fs = new Filesystem();
        $targetPath = sys_get_temp_dir() . '/' . $fileName;
        $fs->copy(__DIR__ . '/images/' . $fileName, $targetPath, true);

        $picture = $this->uploaderHelper->upload(new File($targetPath), UploaderHelper::DISH);
        $dish->setPicture($picture);

        // Association des aliments
        return $this->dishFoodHandler->createDishFoodElement($dish, $this->getListFoods());
    }

    /**
     * Fichier : DishDevFixtures.php
     *
     * Charge les données en base :
     * - épices
     * - 100 plats générés
     *
     * @param ObjectManager $manager
     * @return void
     */
    public function loadData(ObjectManager $manager): void
    {
        // Création des épices de base
        $spice = new Spice();
        $spice->setName('Sel');
        $manager->persist($spice);
        $this->addReference(sprintf('%s_%s', 'spices', $spice->getName()), $spice);

        $spice = new Spice();
        $spice->setName('Poivre');
        $manager->persist($spice);
        $this->addReference(sprintf('%s_%s', 'spices', $spice->getName()), $spice);

        $spice = new Spice();
        $spice->setName('Cumin');
        $manager->persist($spice);
        $this->addReference(sprintf('%s_%s', 'spices', $spice->getName()), $spice);

        // Création de 100 plats
        $this->createMany(100, 'dishs', function($i) {
            return $this->create($i);
        });

        $manager->flush();
    }

    /**
     * Retourne les dépendances de fixtures.
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            FoodFixtures::class
        ];
    }

    /**
     * Retourne les groupes de fixtures.
     *
     * @return array
     */
    public static function getGroups(): array
    {
        return ['dishs'];
    }

    /**
     * Fichier : DishDevFixtures.php
     *
     * Génère une liste d'aliments structurée par groupe alimentaire
     * compatible avec DishFoodHandler::createDishFoodElement()
     *
     * @return array
     */
    private function getListFoods(): array
    {
        $foodGroupCodes = $this->foodGroupHandler->getFoodGroupAlias();
        $nombreDeFoodGroupDanslePlat = random_int(1, count($foodGroupCodes));
        shuffle($foodGroupCodes);

        $groupesRetenusPourLePlat = array_slice($foodGroupCodes, 0, $nombreDeFoodGroupDanslePlat);

        foreach ($groupesRetenusPourLePlat as $groupe) {
            $touslesAlimentsDuGroupe = $this->foodRepository->myFindByFgAlias($groupe);
            $tabIdAlimentDejaSelectionnes = [];

            $unitMeasureGrammeId = $this->unitMeasureRepository->findOneByAlias('g');
            $unitMeasureUnitId = $this->unitMeasureRepository->findOneByAlias('u');
            $choiceUnitMeasures = [$unitMeasureGrammeId, $unitMeasureUnitId];

            $nombreDAlimentParGroupe = random_int(1, 2);

            for ($i = 0; $i < $nombreDAlimentParGroupe; $i++) {
                do {
                    $food = $touslesAlimentsDuGroupe[array_rand($touslesAlimentsDuGroupe)];
                } while (in_array($food->getId(), $tabIdAlimentDejaSelectionnes));

                $tabIdAlimentDejaSelectionnes[] = $food->getId();

                $quantity = random_int(10, 30);
                $unitMeasure = $unitMeasureGrammeId;

                $quantitiesInfos = [
                    "quantity" => $quantity,
                    "quantity_g" => $this->foodUtil->convertInGr($food, $quantity, $unitMeasure),
                    "unit_measure" => $unitMeasure,
                    "food" => $food,
                ];

                $results[$groupe][$food->getId()] = $quantitiesInfos;
            }
        }

        return $results;
    }
}