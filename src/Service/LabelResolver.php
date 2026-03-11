<?php

namespace App\Service;

use App\Repository\NutrientRepository;
use App\Repository\FoodGroupParentRepository;

/**
 * LabelResolver.php
 *
 * Service utilitaire pour résoudre un code (nutriment ou groupe d'aliment) en libellé lisible.
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class LabelResolver
{
    public function __construct(
        private NutrientRepository $nutrientRepository,
        private FoodGroupParentRepository $foodGroupParentRepository
    ) {}

    /**
     * Résout un code (nutriment ou groupe d'aliment) en libellé lisible.
     *
     * @param string $code Code à résoudre (ex: "protein", "FGP_VEG")
     * @return string Libellé correspondant ou une version humanisée si non trouvé
     */
    public function resolve(string $code): string
    {
        // 1️⃣ Recherche du code dans les nutriments
        $nutrient = $this->nutrientRepository->findOneBy(['code' => $code]);
        if ($nutrient) {
            return $nutrient->getName(); // Renvoie le nom du nutriment trouvé
        }

        // 2️⃣ Recherche dans les groupes d'aliments parents
        $foodGroup = $this->foodGroupParentRepository->findOneBy(['alias' => $code]);
        if ($foodGroup) {
            return $foodGroup->getName(); // Renvoie le nom du groupe d'aliments
        }

        // 3️⃣ Fallback : si rien n'a été trouvé, transforme le code en libellé lisible
        return ucfirst(str_replace('_', ' ', $code));
    }
}