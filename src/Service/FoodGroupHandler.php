<?php
namespace App\Service;

use Symfony\Component\Security\Core\Security;
use App\Service\NutrientHandler;

class FoodGroupHandler
{
    public function __construct(
        private Security $security,
        private  NutrientHandler $nutrientHandler
    )
    {}

    public function getRecommendations()
    {
        return $this->calculateRecommendations();
    }

    private function calculateRecommendations(): array
    {
        /** @var App\Entity\User $user */
        $user = $this->security->getUser();

        $energy = $user->getEnergy();
        $macros = $this->nutrientHandler->getRecommendations();

        $protein = $macros['protein'];
        $fat = $macros['lipid'];
        $carb = $macros['carbohydrate'];

        // 🥩 VPO
        $vpo = (($protein * 0.6) / 20) * 100;

        // 🥔 Féculents
        $starchy = (($carb * 0.7) / 20) * 100;

        // 🥦 Légumes
        $veg = $energy / 5;

        // 🍎 Fruits
        $fruit = 250;

        // 🥛 Produits laitiers
        $dairy = 300;

        // 🧈 Matières grasses
        $fatGroup = $fat * 0.8;

        // 🍬 Sucre
        $sugar = ($energy * 0.08) / 4;

        // 🧂 Condiments
        $condiment = 15;

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
