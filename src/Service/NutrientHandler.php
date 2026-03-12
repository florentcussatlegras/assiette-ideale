<?php

namespace App\Service;

use App\Entity\Alert\LevelAlert;
use Symfony\Component\Security\Core\Security;

/**
 * NutrientHandler.php
 * 
 * Service de gestion et recommandations nutritionnelles.
 *
 * Objectif :
 *  - Fournir des recommandations alimentaires personnalisées basées sur les besoins de l'utilisateur.
 *  - Calculer les apports en protéines, lipides, glucides et sodium selon le poids et l'activité physique.
 *  - Fournir des messages et suggestions adaptés aux niveaux (bien, faible, élevé) pour chaque nutriment.
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
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

    // Messages de suggestion affichés dans la modale des bilans des repas saisi/édités
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

    /**
     * Retourne les recommandations nutritionnelles pour l'utilisateur connecté.
     *
     * @return array Tableau associatif : ['protein' => int, 'lipid' => int, 'carbohydrate' => int, 'sodium' => int]
     */
    public function getRecommendations(): array
    {
        return $this->calculateRecommendations();
    }

    /**
     * Calcule les besoins nutritionnels journaliers selon le poids, l'énergie et l'activité physique.
     *
     * @return array Tableau avec les grammes recommandés pour chaque nutriment.
     */
    private function calculateRecommendations(): array
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        $energy = $user->getEnergy();
        $weight = $user->getWeight();
        $activity = $user->getPhysicalActivity();

        // Protéines en g/kg selon activité
        $proteinPerKg = match(true) {
            $activity <= 1.0 => 0.9,
            $activity <= 1.2 => 1.1,
            default => 1.3,
        };
        $proteinGrams = $weight * $proteinPerKg;
        $proteinKcal = $proteinGrams * 4;

        // Lipides = 30% de l'énergie
        $fatKcal = $energy * 0.30;
        $fatGrams = $fatKcal / 9;

        // Glucides = reste de l'énergie
        $carbKcal = $energy - ($proteinKcal + $fatKcal);
        $carbGrams = $carbKcal / 4;

        return [
            self::PROTEIN => round($proteinGrams),
            self::LIPID => round($fatGrams),
            self::CARBOHYDRATE => round($carbGrams),
            self::SODIUM => 2,
        ];
    }
}