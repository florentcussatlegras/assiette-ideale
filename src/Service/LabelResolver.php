<?php

namespace App\Service;

use App\Repository\NutrientRepository;
use App\Repository\FoodGroupParentRepository;

class LabelResolver
{
    public function __construct(
        private NutrientRepository $nutrientRepository,
        private FoodGroupParentRepository $foodGroupParentRepository
    ) {}

    public function resolve(string $code): string
    {
        // On cherche d’abord dans Nutrient
        $nutrient = $this->nutrientRepository->findOneBy(['code' => $code]);
        if ($nutrient) {
            return $nutrient->getName();
        }

        // Sinon dans FoodGroup
        $foodGroup = $this->foodGroupParentRepository->findOneBy(['alias' => $code]);
        if ($foodGroup) {
            return $foodGroup->getName();
        }

        // Fallback si jamais rien trouvé
        return ucfirst(str_replace('_', ' ', $code));
    }
}
