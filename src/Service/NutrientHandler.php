<?php
namespace App\Service;

use Symfony\Component\Security\Core\Security;
use App\Service\EnergyHandler;

class NutrientHandler
{
    const PROTEIN = 'protein';
    const LIPID = 'lipid';
    const CARBOHYDRATE = 'carbohydrate';
    const SODIUM = 'sodium';

    public function __construct(
        private Security $security,
        private EnergyHandler $energyHandler,
    ){}

    public function getRecommendations()
    {
        return $this->calculateRecommendations();
    }

    private function calculateRecommendations(): array
    {
        /** @var App\Entity\User $user */
        $user = $this->security->getUser();

        $energy = $this->energyHandler->evaluateEnergy($user);

        $weight = $user->getWeight();
        $activity = $user->getPhysicalActivity();

        // 1️⃣ Protéines
        $proteinPerKg = match(true) {
            $activity <= 1.0 => 0.9,
            $activity <= 1.2 => 1.1,
            default => 1.3,
        };

        $proteinGrams = $weight * $proteinPerKg;
        $proteinKcal = $proteinGrams * 4;

        // 2️⃣ Lipides (30%)
        $fatKcal = $energy * 0.30;
        $fatGrams = $fatKcal / 9;

        // 3️⃣ Glucides = reste
        $carbKcal = $energy - ($proteinKcal + $fatKcal);
        $carbGrams = $carbKcal / 4;

        return [
            'protein' => round($proteinGrams),
            'lipid' => round($fatGrams),
            'carbohydrate' => round($carbGrams),
            'sodium' => 2,
        ];
    }
}