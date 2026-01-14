<?php

namespace App\Serializer\Normalizer;

use App\Entity\Dish;
use App\Entity\NutritionalTable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

class DishDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private ObjectNormalizer $normalizer,
        private EntityManagerInterface $manager
    ){

    }

    public function denormalize($data, string $type, string $format = null, array $context = []): Dish
    {
        $data["id"] = !empty($data["lengthPersonForRecipe"]) ? (int)$data["id"] : null;
        $data["lengthPersonForRecipe"] = !empty($data["lengthPersonForRecipe"]) ? (int)$data["lengthPersonForRecipe"] : null;
        $data["preparationTime"] = !empty($data["preparationTime"]) ? (int)$data["preparationTime"] : null;
        $data["preparationTimeUnitTime"] = !empty($data["preparationTimeUnitTime"]) ? (int)$data["preparationTimeUnitTime"] : null;
        $data["cookingTime"] = !empty($data["cookingTime"]) ? (int)$data["cookingTime"] : null;
        $data["cookingTimeUnitTime"] = !empty($data["cookingTimeUnitTime"]) ? (int)$data["cookingTimeUnitTime"] : null;

        if(isset($data["nutritionalTable"])) {
            $nutritionalTable = $data["nutritionalTable"];
            $nutritionalTable["protein"] = !empty($nutritionalTable["protein"]) ? (int)$nutritionalTable["protein"] : null;
            $nutritionalTable["lipid"] = !empty($nutritionalTable["lipid"]) ? (int)$nutritionalTable["lipid"] : null;
            $nutritionalTable["saturatedFattyAcid"] = !empty($nutritionalTable["saturatedFattyAcid"]) ? (int)$nutritionalTable["saturatedFattyAcid"] : null;
            $nutritionalTable["carbohydrate"] = !empty($nutritionalTable["carbohydrate"]) ? (int)$nutritionalTable["carbohydrate"] : null;
            $nutritionalTable["sugar"] = !empty($nutritionalTable["sugar"]) ? (int)$nutritionalTable["sugar"] : null;
            $nutritionalTable["salt"] = !empty($nutritionalTable["salt"]) ? (int)$nutritionalTable["salt"] : null;
            $nutritionalTable["fiber"] = !empty($nutritionalTable["fiber"]) ? (int)$nutritionalTable["fiber"] : null;
            $nutritionalTable["energy"] = !empty($nutritionalTable["energy"]) ? (int)$nutritionalTable["energy"] : null;
            $nutritionalTableObject = new NutritionalTable();
            $nutritionalTableObject = $this->normalizer->denormalize(
                $nutritionalTable,
                NutritionalTable::class,
                'class'
            );
        }else{
            $nutritionalTableObject = new NutritionalTable();
        }

        $data["nutritionalTable"] = $nutritionalTableObject;

        if(!empty($data['dish']['id'])) {
            $dish = $manager->getRepository(Dish::class)->findOneById((int)$data['dish']['id']);
        }else{
            $dish = new Dish();
        }
     
        // $dish->setNutritionalTable($nutritionalTableObject);
        // $stepRecipes = $data["stepRecipes"];
        
        return $this->normalizer->denormalize(
            $data,
            Dish::class,
            'class',
            [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                AbstractNormalizer::OBJECT_TO_POPULATE => $dish,
            ]
        );
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return is_array($data);
    }
}