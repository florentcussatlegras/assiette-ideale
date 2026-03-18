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
    public const FGP_VPO = 'FGP_VPO';
    public const FGP_STARCHY = 'FGP_STARCHY';
    public const FGP_VEG = 'FGP_VEG';
    public const FGP_FRUIT = 'FGP_FRUIT';
    public const FGP_DAIRY = 'FGP_DAIRY';
    public const FGP_FAT = 'FGP_FAT';
    public const FGP_SUGAR = 'FGP_SUGAR';
    public const FGP_CONDIMENT = 'FGP_CONDIMENT';

    public const FG_MEAT = 'FG_MEAT';
    public const FG_FISH = 'FG_FISH';
    public const FG_EGG = 'FG_EGG';
    public const FG_STARCHY = 'FG_STARCHY';
    public const FG_RAW_VEG = 'FG_RAW_VEG';
    public const FG_COOKED_VEG = 'FG_COOKED_VEG';
    public const FG_RAW_FRUIT = 'FG_RAW_FRUIT';
    public const FG_COOKED_FRUIT = 'FG_COOKED_FRUIT';
    public const FG_MILK = 'FG_MILK';
    public const FG_CHEESE = 'FG_CHEESE';
    public const FG_FAT_VEG = 'FG_FAT_VEG';
    public const FG_FAT_ANIMAL = 'FG_FAT_ANIMAL';
    public const FG_SUGAR = 'FG_SUGAR';

    public const FOOD_GROUP_PARENT = [
        self::FGP_VPO,
        self::FGP_STARCHY,
        self::FGP_VEG,
        self::FGP_FRUIT,
        self::FGP_DAIRY,
        self::FGP_FAT,
        self::FGP_SUGAR,
        self::FGP_CONDIMENT,
    ];

    public const FOOD_GROUP = [
        self::FG_MEAT,
        self::FG_FISH,
        self::FG_EGG,
        self::FG_STARCHY,
        self::FG_RAW_VEG,
        self::FG_COOKED_VEG,
        self::FG_RAW_FRUIT,
        self::FG_COOKED_FRUIT,
        self::FG_MILK,
        self::FG_CHEESE,
        self::FG_FAT_VEG,
        self::FG_FAT_ANIMAL,
        self::FG_SUGAR,
    ];

    /**
     * Injection des dépendances via constructeur
     */
    public function __construct(
        private Security $security,             // Permet de récupérer l'utilisateur connecté
        private NutrientHandler $nutrientHandler // Service fournissant les recommandations nutritionnelles
    ) {}

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
            self::FGP_VPO => round($vpo),
            self::FGP_STARCHY => round($starchy),
            self::FGP_VEG => round($veg),
            self::FGP_FRUIT => round($fruit),
            self::FGP_DAIRY => round($dairy),
            self::FGP_FAT => round($fatGroup),
            self::FGP_SUGAR => round($sugar),
            self::FGP_CONDIMENT => round($condiment),
        ];
    }

    /**
     * Retourne la liste des alias des groupes parents.
     *
     * @return array
     */
    public function getFoodGroupParentAlias(): array
    {
        return self::FOOD_GROUP_PARENT;
    }

    /**
     * Retourne la liste des alias des groupes enfants.
     *
     * @return array
     */
    public function getFoodGroupAlias(): array
    {
        return self::FOOD_GROUP;
    }

    /**
     * Retourne les données complètes des groupes alimentaires,
     * incluant les groupes parents et leurs enfants.
     *
     * Chaque groupe contient :
     * - name : nom complet
     * - short_name : nom court
     * - semi_short_name : nom semi-court
     * - alias : code du groupe
     * - color : couleur principale
     * - degraded_color : couleur dégradée
     * - ranking : ordre dans le tableau
     * - principal : booléen indiquant si c'est un groupe parent
     * - childs : liste des sous-groupes enfants
     *
     * @return array
     */
    public function getDatas(): array
    {
        return [
            0 => [
                'name' => 'Aliments protidiques',
                'short_name' => 'VPO',
                'semi_short_name' => 'Al. protidiques',
                'alias' => self::FGP_VPO,
                'color' => '#c11200',
                'degraded_color' => '#fde5e2',
                'ranking' => 1,
                'principal' => true,
                'childs' => [
                    [
                        'name' => 'Viandes',
                        'short_name' => 'Viandes',
                        'semi_short_name' => 'Viandes',
                        'alias' => self::FG_MEAT,
                        'ranking' => 1
                    ],
                    [
                        'name' => 'Poissons',
                        'short_name' => 'Poissons',
                        'semi_short_name' => 'Poissons',
                        'alias' => self::FG_FISH,
                        'ranking' => 2
                    ],
                    [
                        'name' => 'Oeufs',
                        'short_name' => 'Oeufs',
                        'semi_short_name' => 'Oeufs',
                        'alias' => self::FG_EGG,
                        'ranking' => 3
                    ]
                ]
            ],
            1 => [
                'name' => 'FECULENTS',
                'short_name' => 'Fec',
                'semi_short_name' => 'Féculents',
                'alias' => self::FGP_STARCHY,
                'color' => '#9e4b10',
                'degraded_color' => '#fceee3',
                'ranking' => 2,
                'principal' => true,
                'childs' => [
                    [
                        'name' => 'Féculents',
                        'short_name' => 'Al. protidiques',
                        'semi_short_name' => 'Al. protidiques',
                        'alias' => self::FG_STARCHY,
                        'ranking' => 4,
                    ]
                ]
            ],
            2 => [
                'name' => 'LEGUMES',
                'short_name' => 'Légumes',
                'semi_short_name' => 'Lég.',
                'alias' => self::FGP_VEG,
                'color' => '#216959',
                'degraded_color' => '#e6f7f4',
                'ranking' => 3,
                'principal' => true,
                'childs' => [
                    [
                        'name' => 'Légumes crus',
                        'short_name' => 'L. crus',
                        'semi_short_name' => 'Lég. crus',
                        'alias' => self::FG_RAW_VEG,
                        'ranking' => 5
                    ],
                    [
                        'name' => 'Légumes cuits',
                        'short_name' => 'L. cuits',
                        'semi_short_name' => 'Lég. cuits',
                        'alias' => self::FG_COOKED_VEG,
                        'ranking' => 6
                    ]
                ]
            ],
            3 => [
                'name' => 'FRUITS',
                'short_name' => 'Fruits',
                'semi_short_name' => 'Fruits',
                'alias' => self::FGP_FRUIT,
                'color' => '#00bc00',
                'degraded_color' => '#deffde',
                'ranking' => 4,
                'principal' => true,
                'childs' => [
                    [
                        'name' => 'Fruits crus',
                        'short_name' => 'Fc',
                        'semi_short_name' => 'Fr. crus',
                        'alias' => self::FG_RAW_FRUIT,
                        'ranking' => 7
                    ],
                    [
                        'name' => 'Fruits cuits',
                        'short_name' => 'Fc',
                        'semi_short_name' => 'Fr. cuits',
                        'alias' => self::FG_COOKED_FRUIT,
                        'ranking' => 8
                    ]
                ]
            ],
            4 => [
                'name' => 'PRODUITS LAITIERS',
                'short_name' => 'Pl',
                'semi_short_name' => 'P. laitiers',
                'alias' => self::FGP_DAIRY,
                'color' => '#0048da',
                'degraded_color' => '#ebf1ff',
                'ranking' => 5,
                'principal' => true,
                'childs' => [
                    [
                        'name' => 'Laitages',
                        'short_name' => 'L.',
                        'semi_short_name' => 'Lait.',
                        'alias' => self::FG_MILK,
                        'ranking' => 9
                    ],
                    [
                        'name' => 'Fromages',
                        'short_name' => 'Fr.',
                        'semi_short_name' => 'From.',
                        'alias' => self::FG_CHEESE,
                        'ranking' => 10
                    ]
                ]
            ],
            5 => [
                'name' => 'MATIERES GRASSES',
                'short_name' => 'MG',
                'semi_short_name' => 'MG',
                'alias' => self::FGP_FAT,
                'color' => '#dbd77d',
                'degraded_color' => '#fcfcf5',
                'ranking' => 6,
                'principal' => false,
                'childs' => [
                    [
                        'name' => 'Matières grasses végétales',
                        'short_name' => 'MGV',
                        'semi_short_name' => 'Mat. grasses végétales',
                        'alias' => self::FG_FAT_VEG,
                        'ranking' => 11
                    ],
                    [
                        'name' => 'Matières grasses animales',
                        'short_name' => 'MGA',
                        'semi_short_name' => 'Mat. grasses animales',
                        'alias' => self::FG_FAT_ANIMAL,
                        'ranking' => 12
                    ]
                ]
            ],
            8 => [
                'name' => 'PRODUITS SUCRES',
                'short_name' => 'Ps',
                'semi_short_name' => 'P. Sucrés',
                'alias' => self::FGP_SUGAR,
                'color' => '#697882',
                'degraded_color' => '#f4f5f6',
                'ranking' => 8,
                'principal' => false,
                'childs' => [
                    [
                        'name' => 'Produits sucrés',
                        'short_name' => 'PS',
                        'semi_short_name' => 'Pr sucrés',
                        'alias' => self::FG_SUGAR,
                        'ranking' => 13
                    ]
                ]
            ]
        ];
    }
}
