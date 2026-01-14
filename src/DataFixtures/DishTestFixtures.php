<?php

namespace App\DataFixtures;

use App\Entity\Dish;
use App\Entity\Spice;
use App\Service\FoodUtil;
use App\Entity\StepRecipe;
use App\Service\FoodGroupUtils;
use App\Service\UploaderHelper;
use App\Service\DishFoodHandler;
use App\DataFixtures\BaseFixture;
use App\DataFixtures\FoodFixtures;
//use Florent\QuantityConverterBundle\QuantityConverter;
use App\Repository\FoodRepository;
use Doctrine\Persistence\ObjectManager;
use App\Repository\UnitMeasureRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class DishTestFixtures
{
    private $dishFoodHandler;
    private $foodGroupUtils;
    private $foodUtil;
    private $foodRepository;
    //private $quantityConverter;
    private $uploaderHelper;
    private $unitMeasureRepository;

    public function __construct(FoodRepository $foodRepository, UnitMeasureRepository $unitMeasureRepository,
        DishFoodHandler $dishFoodHandler, FoodGroupUtils $foodGroupUtils, FoodUtil $foodUtil, UploaderHelper $uploaderHelper)
        //QuantityConverter $quantityConverter)
    {
        $this->dishFoodHandler = $dishFoodHandler;
        $this->foodGroupUtils = $foodGroupUtils;
        $this->foodRepository = $foodRepository;
        $this->unitMeasureRepository = $unitMeasureRepository;
        $this->foodUtil = $foodUtil;
        //$this->quantityConverter = $quantityConverter;
        $this->uploaderHelper = $uploaderHelper;
    }

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
        // $dish->addSpice($this->getReference('spices_Sel'));
        // $dish->addSpice($this->getReference('spices_Poivre'));

        //Nombre d'étape de préparation aléatoire entre 1 et 5
        for($j = 0; $j < random_int(1, 5); $j++) {
            $stepRecipe = new StepRecipe();
            $stepRecipe->setRankStep($j);
            $stepRecipe->setDescription($this->faker->text());

            $dish->addStepRecipe($stepRecipe);
        }

        $fileName = 'plat1.jpeg';
             
        $fs = new Filesystem();
        $targetPath = sys_get_temp_dir().'/'.$fileName;
        $fs->copy(__DIR__.'/images/'.$fileName, $targetPath, true);

        $picture =  $this->uploaderHelper->uploadDishPictures(new File($targetPath));
        $dish->addPicture($picture);

        return $this->dishFoodHandler->createDishFoodElement($dish, $this->getListFoods());
    }

    public function loadData(ObjectManager $manager): void
    {
        // SPICE

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

        //createMany(int $count, string $groupName, callable $factory)
        // créer une référence 'groupName_index'
        $dish = $this->create(0);

        $manager->persist($dish);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            FoodFixtures::class
        ];
    }

    public static function getGroups(): array
    {
        return ['dishs'];
    }

    /**
     * Renvoi un tableau de structure identique à celui necessaire à la fonction
     * $this->dishFoodHandler->createDishFoodElement() permettant de créer les objet
     * DishFood et DishFoodGroup
     *
     * @return array
     */
    private function getListFoods(): array
    {
        $foodGroupAlias = $this->foodGroupUtils->getFoodGroupAlias();
        $nombreDeFoodGroupDanslePlat = random_int(1, count($foodGroupAlias));
        shuffle($foodGroupAlias);
        // On choiti aléatoirement un nombre alétoire de groupe d'aliments (code)
        $groupesRetenusPourLePlat = array_slice($foodGroupAlias, 0, $nombreDeFoodGroupDanslePlat);

        foreach($groupesRetenusPourLePlat as $groupe) {

            $touslesAlimentsDuGroupe = $this->foodRepository->myFindByFgAlias($groupe);
            $tabIdAlimentDejaSelectionnes = [];
            $toutesLesUnitesDeMesures = $this->unitMeasureRepository->findAll();

            //On ajoute entre 1 ou 2 aliments
            $nombreDAlimentParGroupe = random_int(1, 2);

            for($i = 0; $i < $nombreDAlimentParGroupe; $i++) {

                //id aléatoire de l'aliment après vérification qu'il n'a pas déja été choisi
                do {
                    $food = $touslesAlimentsDuGroupe[array_rand($touslesAlimentsDuGroupe)];
                }while(in_array($food->getId(), $tabIdAlimentDejaSelectionnes));

                $tabIdAlimentDejaSelectionnes[] = $food->getId();

                $quantity = random_int(10, 500);
                $unitMeasure = $toutesLesUnitesDeMesures[array_rand($toutesLesUnitesDeMesures)];

                $quantitiesInfos = [
                    "quantity" => $quantity,
                    "quantity_g" => $this->foodUtil->convertInGr($food, $quantity, $unitMeasure),
                    "unit_measure" => $unitMeasure,
                    "food" => $food,
                ];

                //$quantitiesInfos['quantity_g'] = $this->quantityConverter->getEquivalentGramme($quantitiesInfos);
                $results[$groupe][$food->getId()] = $quantitiesInfos;

            }

        }

        return $results;
    }
}