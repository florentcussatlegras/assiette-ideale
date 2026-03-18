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
 * DishTestFixtures.php
 *
 * Fixture de test permettant de générer un plat avec :
 * - des étapes de recette
 * - une image
 * - des aliments associés aléatoirement par groupe alimentaire
 *
 * Utilisé principalement pour les tests unitaires / fonctionnels.
 */
class DishTestFixtures
{
    /**
     * @param FoodRepository $foodRepository Repository des aliments
     * @param UnitMeasureRepository $unitMeasureRepository Repository des unités de mesure
     * @param DishFoodHandler $dishFoodHandler Service de gestion des DishFood
     * @param FoodGroupHandler $foodGroupHandler Service de gestion des groupes alimentaires
     * @param FoodUtil $foodUtil Service utilitaire pour les aliments
     * @param UploaderHelper $uploaderHelper Service d'upload des images
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
     * Fichier : DishTestFixtures.php
     *
     * Crée un objet Dish complet avec :
     * - informations de base
     * - étapes de recette aléatoires
     * - image associée
     * - aliments générés aléatoirement
     *
     * @param int $i Index utilisé pour la génération
     * @return Dish
     */
    private function create(int $i): Dish
    {
        $dish = new Dish();
        $dish->setName($this->faker->name());
        $dish->setDescription($this->faker->text());
        $dish->setLengthPersonForRecipe(random_int(1, 6));
        $dish->setPreparationTime(random_int(1, 4));
        $dish->setPreparationTimeUnitTime($this->getReference('unit_times_h'));
        $dish->setCookingTime(random_int(5, 59));
        $dish->setCookingTimeUnitTime($this->getReference('unit_times_min'));

        // Génération d'étapes de recette
        for ($j = 0; $j < random_int(1, 5); $j++) {
            $stepRecipe = new StepRecipe();
            $stepRecipe->setRankStep($j);
            $stepRecipe->setDescription($this->faker->text());

            $dish->addStepRecipe($stepRecipe);
        }

        // Gestion de l'image
        $fileName = 'plat1.jpeg';
        $fs = new Filesystem();
        $targetPath = sys_get_temp_dir() . '/' . $fileName;
        $fs->copy(__DIR__ . '/images/' . $fileName, $targetPath, true);

        $picture = $this->uploaderHelper->uploadDishPictures(new File($targetPath));
        $dish->addPicture($picture);

        // Association des aliments
        return $this->dishFoodHandler->createDishFoodElement($dish, $this->getListFoods());
    }

    /**
     * Fichier : DishTestFixtures.php
     *
     * Charge les données en base :
     * - épices de base
     * - un plat de test
     *
     * @param ObjectManager $manager
     * @return void
     */
    public function loadData(ObjectManager $manager): void
    {
        // Création des épices
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

        // Création d'un plat
        $dish = $this->create(0);

        $manager->persist($dish);
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
     * Fichier : DishTestFixtures.php
     *
     * Génère une liste d'aliments structurée par groupe alimentaire
     * compatible avec :
     * DishFoodHandler::createDishFoodElement()
     *
     * Structure retournée :
     * [groupe][foodId] => [
     *   quantity,
     *   quantity_g,
     *   unit_measure,
     *   food
     * ]
     *
     * @return array
     */
    private function getListFoods(): array
    {
        $foodGroupAlias = $this->foodGroupHandler->getFoodGroupAlias();
        $nombreDeFoodGroupDanslePlat = random_int(1, count($foodGroupAlias));
        shuffle($foodGroupAlias);

        $groupesRetenusPourLePlat = array_slice($foodGroupAlias, 0, $nombreDeFoodGroupDanslePlat);

        foreach ($groupesRetenusPourLePlat as $groupe) {

            $touslesAlimentsDuGroupe = $this->foodRepository->myFindByFgAlias($groupe);
            $tabIdAlimentDejaSelectionnes = [];
            $toutesLesUnitesDeMesures = $this->unitMeasureRepository->findAll();

            // Nombre d'aliments par groupe
            $nombreDAlimentParGroupe = random_int(1, 2);

            for ($i = 0; $i < $nombreDAlimentParGroupe; $i++) {

                // Sélection aléatoire sans doublon
                do {
                    $food = $touslesAlimentsDuGroupe[array_rand($touslesAlimentsDuGroupe)];
                } while (in_array($food->getId(), $tabIdAlimentDejaSelectionnes));

                $tabIdAlimentDejaSelectionnes[] = $food->getId();

                $quantity = random_int(10, 500);
                $unitMeasure = $toutesLesUnitesDeMesures[array_rand($toutesLesUnitesDeMesures)];

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