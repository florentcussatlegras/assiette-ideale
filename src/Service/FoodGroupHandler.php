<?php

namespace App\Service;

use Symfony\Component\Security\Core\Security;
use App\Service\NutrientHandler;

/**
 * FoodGroupHandler.php
 *
 * Service chargé de calculer les recommandations journalières
 * par groupes alimentaires pour l'utilisateur connecté.
 *
 * Les recommandations sont déterminées à partir :
 * - du besoin énergétique de l'utilisateur
 * - des recommandations en macronutriments (protéines, lipides, glucides)
 *
 * Ce service convertit ensuite ces apports nutritionnels en quantités
 * recommandées pour différents groupes alimentaires :
 * - VPO (Viandes, Poissons, Œufs)
 * - Féculents
 * - Légumes
 * - Fruits
 * - Produits laitiers
 * - Matières grasses
 * - Sucre
 * - Condiments
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class FoodGroupHandler
{
    /**
     * Injection des dépendances via constructeur
     */
    public function __construct(
        private Security $security,             // Permet de récupérer l'utilisateur connecté
        private NutrientHandler $nutrientHandler // Service fournissant les recommandations nutritionnelles
    )
    {}

    /**
     * Retourne les recommandations journalières
     * par groupe alimentaire pour l'utilisateur.
     *
     * @return array
     */
    public function getRecommendations()
    {
        return $this->calculateRecommendations();
    }

    /**
     * Calcule les recommandations alimentaires
     * en fonction du besoin énergétique et des macronutriments.
     *
     * @return array
     */
    private function calculateRecommendations(): array
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        // Besoin énergétique journalier
        $energy = $user->getEnergy();

        // Recommandations en macronutriments
        $macros = $this->nutrientHandler->getRecommendations();

        $protein = $macros['protein'];
        $fat = $macros['lipid'];
        $carb = $macros['carbohydrate'];

        // 🥩 VPO (Viandes Poissons Œufs)
        // 60% des protéines converties en portion (~20g protéines / 100g)
        $vpo = (($protein * 0.6) / 20) * 100;

        // 🥔 Féculents
        // 70% des glucides convertis en portion (~20g glucides / 100g)
        $starchy = (($carb * 0.7) / 20) * 100;

        // 🥦 Légumes
        // Ratio basé sur l'apport énergétique
        $veg = $energy / 5;

        // 🍎 Fruits
        // Portion standard
        $fruit = 250;

        // 🥛 Produits laitiers
        // Recommandation fixe
        $dairy = 300;

        // 🧈 Matières grasses
        // 80% des lipides convertis en quantité alimentaire
        $fatGroup = $fat * 0.8;

        // 🍬 Sucre
        // Limite de 8% de l'énergie totale
        $sugar = ($energy * 0.08) / 4;

        // 🧂 Condiments
        // Valeur standard
        $condiment = 50;

        return [
            'FGP_VPO' => round($vpo),
            'FGP_STARCHY' => round($starchy),
            'FGP_VEG' => round($veg),
            'FGP_FRUIT' => round($fruit),
            'FGP_DAIRY' => round($dairy),
            'FGP_FAT' => round($fatGroup),
            'FGP_SUGAR' => round($sugar),
            'FGP_CONDIMENT' => round($condiment),
        ];
    }
}