<?php
namespace App\Service;

class FoodGroupHandler
{
    public function getRecommendations()
    {
        return $this->calculateRecommendations();
    }

    public function calculateRecommendations()
    {
        // ALGORITHME A ECRIRE
        return  [
            'FGP_VPO' => 200,
            'FGP_STARCHY' => 200,
            'FGP_VEG' => 200,
            'FGP_FRUIT' => 200,
            'FGP_DAIRY' => 200,
            'FGP_FAT' => 200,
            'FGP_SUGAR' => 200,
            'FGP_CONDIMENT' => 200
        ];
    }
}