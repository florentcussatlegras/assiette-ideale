<?php
namespace App\Service;

use App\Entity\Alert\LevelAlert;
use Symfony\Component\Security\Core\Security;

class NutrientHandler
{
    const PROTEIN = 'protein';
    const LIPID = 'lipid';
    const CARBOHYDRATE = 'carbohydrate';
    const SODIUM = 'sodium';

    public const NUTRIENTS = [
        self::PROTEIN,
        self::LIPID,
        self::CARBOHYDRATE,
        self::SODIUM,
    ];

    public const LACK_SUGGESTIONS = [
        self::PROTEIN => "Ajoutez des aliments riches en protéines : viande, poisson, œufs ou légumineuses.",
        self::CARBOHYDRATE => "Ajoutez des féculents : riz, pâtes, pommes de terre ou pain.",
        self::LIPID => "Ajoutez de bonnes graisses : huile d'olive, noix, avocat.",
        self::SODIUM => "Vous pouvez légèrement saler votre plat si nécessaire.",
    ];

    public const EXCESS_SUGGESTIONS = [
        self::PROTEIN => "Réduisez les portions d'aliments très riches en protéines.",
        self::CARBOHYDRATE => "Limitez les féculents ou produits sucrés.",
        self::LIPID => "Réduisez les aliments gras (fritures, sauces, fromage).",
        self::SODIUM => "Réduisez les produits salés et transformés.",
    ];

    public const MESSAGES_SUGGESTIONS = [
        self::PROTEIN => [
            LevelAlert::WELL_ALERTS_LABEL => 'Votre consommation de protéines est bonne.',
            LevelAlert::LOW_ALERTS_LABEL => 'Consommez plus de protéines : privilégiez viandes, œufs, légumineuses.',
            LevelAlert::HIGH_ALERTS_LABEL => 'Attention, vous consommez trop de protéines, réduisez les portions.',
        ],
        self::LIPID => [
            LevelAlert::WELL_ALERTS_LABEL => 'Votre consommation de lipides est bonne.',
            LevelAlert::LOW_ALERTS_LABEL => 'Ajoutez des lipides sains : huiles végétales, noix, avocat.',
            LevelAlert::HIGH_ALERTS_LABEL => 'Réduisez votre consommation de lipides pour rester équilibré.',
        ],
        self::CARBOHYDRATE => [
            LevelAlert::WELL_ALERTS_LABEL => 'Votre consommation de glucides est bonne.',
            LevelAlert::LOW_ALERTS_LABEL => 'Augmentez les glucides : pain complet, riz, pâtes, fruits.',
            LevelAlert::HIGH_ALERTS_LABEL => 'Diminuez les glucides pour rester dans vos apports quotidiens.',
        ],
        self::SODIUM => [
            LevelAlert::WELL_ALERTS_LABEL => 'Votre consommation de sodium est bonne.',
            LevelAlert::LOW_ALERTS_LABEL => 'Votre apport en sodium est faible, utilisez un peu plus de sel si besoin.',
            LevelAlert::HIGH_ALERTS_LABEL => 'Attention au sodium, réduisez les aliments salés.',
        ],
    ];

    public function __construct(
        private Security $security,
    ){}

    public function getRecommendations()
    {
        return $this->calculateRecommendations();
    }

    private function calculateRecommendations(): array
    {
        /** @var App\Entity\User $user */
        $user = $this->security->getUser();

        $energy = $user->getEnergy();

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