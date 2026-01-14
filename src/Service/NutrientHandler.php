<?php
namespace App\Service;

use App\Repository\NutrientRepository;
use App\Entity\NutrientRecommendationUser;
use Symfony\Component\Security\Core\Security;


class NutrientHandler
{
    const PROTEIN = 'protein';
    const LIPID = 'lipid';
    const CARBOHYDRATE = 'carbohydrate';
    const SODIUM = 'sodium';

    public function __construct(
        private NutrientRepository $nutrientRepository,
        private Security $security
    ){}

    public function getRecommendations()
    {
        return $this->calculateRecommendations();
    }

    private function calculateRecommendations()
    {
        $user = $this->security->getUser();
        // $recommendations = [];

        // foreach($this->nutrientRepository->findAll() as $nutrient) {
        //     $nutrientRecommendationUser = new NutrientRecommendationUser();
        //     $nutrientRecommendationUser->setUser($user);
        //     $nutrientRecommendationUser->setNutrient($nutrient);

        //     // ALGORITHME DE CALCUL DES QUANTITES RECOMMENDEES A ECRIRE
        //     $nutrientRecommendationUser->setRecommendedQuantity(165);

        //     $recommendations[] = $nutrientRecommendationUser;
        // };

        // ALGO CALCUL DES RECOMMENDATIONS EN FONCTION DES DONNEES DE L'UTILISATEUR A ECRIRE !!
        $recommendations[self::PROTEIN] = 165;
        $recommendations[self::LIPID] = 165;
        $recommendations[self::CARBOHYDRATE] = 165;
        $recommendations[self::SODIUM] = 2;

        return $recommendations;
    }
}