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

// class DishDevFixtures extends BaseFixture implements FixtureGroupInterface, DependentFixtureInterface
class DishDevFixtures
{
    private $dishFoodHandler;
    private $foodGroupUtils;
    private $foodUtils;
    private $foodRepository;
    private $quantityConverter;
    private $uploaderHelper;
    private $unitMeasureRepository;

    public function __construct(FoodRepository $foodRepository, UnitMeasureRepository $unitMeasureRepository,
        DishFoodHandler $dishFoodHandler, FoodGroupUtils $foodGroupUtils, FoodUtil $foodUtil, UploaderHelper $uploaderHelper)
        //QuantityConverter $quantityConverter)
    {
        $this->dishFoodHandler = $dishFoodHandler;
        $this->foodGroupUtils = $foodGroupUtils;
        $this->foodUtil = $foodUtil;
        $this->foodRepository = $foodRepository;
        $this->unitMeasureRepository = $unitMeasureRepository;
        //$this->quantityConverter = $quantityConverter;
        $this->uploaderHelper = $uploaderHelper;
    }

    private function create(int $i): Dish
    {
        $dish = new Dish();
        $dish->setName($this->faker->word());
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

        // 10 images plat1.jpeg, plat2.jpeg...plat10.jpeg dans public/images/
        // sont utilisés pour les 100 plats
        switch((int)($i/10)) {
            case 0:
                $fileName = 'plat1.jpeg';
                break;
            case 1:
                $fileName = 'plat2.jpeg';
                break;
            case 2:
                $fileName = 'plat3.jpeg';
                break;
            case 3:
                $fileName = 'plat4.jpeg';
                break;
            case 4:
                $fileName = 'plat5.jpeg';
                break;
            case 5:
                $fileName = 'plat6.jpeg';
                break;
            case 6:
                $fileName = 'plat7.jpeg';
                break;
            case 7:
                $fileName = 'plat8.jpeg';
                break;
            case 8:
                $fileName = 'plat9.jpeg';
                break;
            case 9:
                $fileName = 'plat10.jpeg';
                break;
            default:
                $fileName = 'plat1.jpeg';
        }
        $fs = new Filesystem();
        $targetPath = sys_get_temp_dir().'/'.$fileName;
        $fs->copy(__DIR__.'/images/'.$fileName, $targetPath, true);

        $picture =  $this->uploaderHelper->upload(new File($targetPath), UploaderHelper::DISH);
        $dish->setPicture($picture);

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
        $this->createMany(100, 'dishs', function($i) {
            return $this->create($i);
        });

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
        $foodGroupCodes = $this->foodGroupUtils->getFoodGroupAlias();
        $nombreDeFoodGroupDanslePlat = random_int(1, count($foodGroupCodes));
        shuffle($foodGroupCodes);
        // On choiti aléatoirement un nombre alétoire de groupe d'aliments (code)
        $groupesRetenusPourLePlat = array_slice($foodGroupCodes, 0, $nombreDeFoodGroupDanslePlat);

        foreach($groupesRetenusPourLePlat as $groupe) {

            $touslesAlimentsDuGroupe = $this->foodRepository->myFindByFgAlias($groupe);
            $tabIdAlimentDejaSelectionnes = [];
            // $toutesLesUnitesDeMesures = $this->unitMeasureRepository->findAll();
            $unitMeasureGrammeId = $this->unitMeasureRepository->findOneByAlias('g');
            $unitMeasureUnitId = $this->unitMeasureRepository->findOneByAlias('u');
            $choiceUnitMeasures = [$unitMeasureGrammeId, $unitMeasureUnitId];

            //On ajoute entre 1 ou 2 aliments
            $nombreDAlimentParGroupe = random_int(1, 2);

            for($i = 0; $i < $nombreDAlimentParGroupe; $i++) {

                //id aléatoire de l'aliment après vérification qu'il n'a pas déja été choisi
                do {
                    $food = $touslesAlimentsDuGroupe[array_rand($touslesAlimentsDuGroupe)];
                }while(in_array($food->getId(), $tabIdAlimentDejaSelectionnes));

                $tabIdAlimentDejaSelectionnes[] = $food->getId();

                $quantity = random_int(10, 30);
                // $unitMeasure = $choiceUnitMeasures[array_rand($choiceUnitMeasures)];
                $unitMeasure = $unitMeasureGrammeId;

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