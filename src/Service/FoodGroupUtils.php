<?php 

namespace App\Service;

use Symfony\Component\Serializer\SerializerInterface;

class FoodGroupUtils
{
    public function getFoodGroupParentAlias(): array
    {
        return [
            'FGP_VPO',
            'FGP_STARCHY',
            'FGP_VEG',
            'FGP_FRUIT',
            'FGP_DAIRY',
            'FGP_FAT',
            'FGP_SUGAR',
        ];
    }

    public function getFoodGroupAlias(): array
    {
        return [
            'FG_MEAT',
            'FG_FISH',
            'FG_EGG',
            'FG_STARCHY',
            'FG_RAW_VEG',
            'FG_COOKED_VEG',
            'FG_RAW_FRUIT',
            'FG_COOKED_FRUIT',
            'FG_MILK',
            'FG_CHEESE',
            'FG_FAT_VEG',
            'FG_FAT_ANIMAL',
            'FG_SUGAR',
        ];
    }

    public function getDatas(): array
    {
        return [
            0 => [
                'name' => 'Aliments protidiques',
                'short_name' => 'VPO',
                'semi_short_name' => 'Al. protidiques',
                'alias' => 'FGP_VPO',
                'color' => '#c11200',
                'degraded_color' => '#fde5e2',
                'ranking' => 1,
                'principal' => true,
                'childs' => [
                    0 => [
                        'name' => 'Viandes',
                        'short_name' => 'Viandes',
                        'semi_short_name' => 'Viandes',
                        'alias' => 'FG_MEAT',
                        'ranking' => 1
                    ],
                    1 => [
                        'name' => 'Poissons',
                        'short_name' => 'Poissons',
                        'semi_short_name' => 'Poissons',
                        'alias' => 'FG_FISH',
                        'ranking' => 2
                    ],
                    2 => [
                        'name' => 'Oeufs',
                        'short_name' => 'Oeufs',
                        'semi_short_name' => 'Oeufs',
                        'alias' => 'FG_EGG',
                        'ranking' => 3
                    ]
                ]
            ],
            1 => [
                'name' => 'FECULENTS',
                'short_name' => 'Fec',
                'semi_short_name' => 'Féculents',
                'alias' => 'FGP_STARCHY',
                'color' => '#9e4b10',
                'degraded_color' => '#fceee3',
                'ranking' => 2,
                'principal' => true,
                'childs' => [
                    0 => [
                       'name' => 'Féculents',
                       'short_name' => 'Al. protidiques',
                       'semi_short_name' => 'Al. protidiques',
                       'alias' => 'FG_STARCHY',
                       'ranking' => 4,
                    ]
                ]
            ],
            2 => [
                'name' => 'LEGUMES',
                'short_name' => 'Légumes',
                'semi_short_name' => 'Lég.',
                'alias' => 'FGP_VEG',
                'color' => '#216959',
                'degraded_color' => '#e6f7f4',
                'ranking' => 3,
                'principal' => 1,
                'childs' => [
                    0 => [
                       'name' => 'Légumes crus',
                       'short_name' => 'L. crus',
                       'semi_short_name' => 'Lég. crus',    
                       'alias' => 'FG_RAW_VEG',
                       'ranking' => 5
                    ],
                    1 => [
                        'name' => 'Légumes cuits',
                        'short_name' => 'L. cuits',
                        'semi_short_name' => 'Lég. cuits',
                        'alias' => 'FG_COOKED_VEG',
                        'ranking' => 6
                    ]
                ]
            ],
            3 => [
                'name' => 'FRUITS',
                'short_name' => 'Fruits',
                'semi_short_name' => 'Fruits',
                'alias' => 'FGP_FRUIT',
                'color' => '#00bc00',
                'degraded_color' => '#deffde',
                'ranking' => 4,
                'principal' => true,
                'childs' => [
                    0 => [
                       'name' => 'Fruits crus',
                       'short_name' => 'Fc',
                       'semi_short_name' => 'Fr. crus',  
                       'alias' => 'FG_RAW_FRUIT',
                       'ranking' => 7
                    ],
                    1 => [
                       'name' => 'Fruits cuits',
                       'short_name' => 'Fc',
                       'semi_short_name' => 'Fr. cuits',  
                       'alias' => 'FG_COOKED_FRUIT',
                       'ranking' => 8
                    ]
                ]
            ],
            4 => [
                'name' => 'PRODUITS LAITIERS',
                'short_name' => 'Pl',
                'semi_short_name' => 'P. laitiers',
                'alias' => 'FGP_DAIRY',
                'color' => '#0048da',
                'degraded_color' => '#ebf1ff',
                'ranking' => 5,
                'principal' => true,
                'childs' => [
                    0 => [
                        'name' => 'Laitages',
                        'short_name' => 'L.',
                        'semi_short_name' => 'Lait.',
                        'alias' => 'FG_MILK',
                        'ranking' => 9
                    ],
                    1 => [
                        'name' => 'Fromages',
                        'short_name' => 'Fr.',
                        'semi_short_name' => 'From.',
                        'alias' => 'FG_CHEESE',
                        'ranking' => 10
                    ]
                ]
            ],
            5 => [
                'name' => 'MATIERES GRASSES',
                'short_name' => 'MG',
                'semi_short_name' => 'MG',
                'alias' => 'FGP_FAT',
                'color' => '#dbd77d',
                'degraded_color' => '#fcfcf5',
                'ranking' => 6,
                'principal' => false,
                'childs' => [
                    0 => [
                       'name' => 'Matières grasses végétales',
                       'short_name' => 'MGV',
                       'semi_short_name' => 'Mat. grasses végétales',
                       'alias' => 'FG_FAT_VEG',
                       'ranking' => 11
                    ],
                    1 => [
                        'name' => 'Matières grasses animales',
                        'short_name' => 'MGA',
                        'semi_short_name' => 'Mat. grasses animales',
                        'alias' => 'FG_FAT_ANIMAL',
                        'ranking' => 12
                     ]
                ]
            ],
            // 6 => [
            //     'name' => 'MATIERES GRASSES ANIMALES',
            //     'short_name' => 'Mga',
            //     'semi_short_name' => 'MG Animales',
            //     'code' => 'FGP_FAT_ANIMAL',
            //     'color' => '#e97810',
            //     'degraded_color' => '#fcead9',
            //     'ranking' => 7,
            //     'principal' => false,
            //     'childs' => [
            //         0 => [
            //            'name' => 'Matières grasses animales',
            //            'short_name' => 'MGA',
            //            'semi_short_name' => 'Mat. grasse animale',
            //            'code' => 'FG_FAT_ANIMAL',
            //            'ranking' => 10 
            //         ]
            //     ]
            // ],
            8 => [
                'name' => 'PRODUITS SUCRES',
                'short_name' => 'Ps',
                'semi_short_name' => 'P. Sucrés',
                'alias' => 'FGP_SUGAR',
                'color' => '#697882',
                'degraded_color' => '#f4f5f6',
                'ranking' => 8,
                'principal' => false,
                'childs' => [
                    0 => [
                       'name' => 'Produits sucrés',
                       'short_name' => 'PS',
                       'semi_short_name' => 'Pr sucrés',
                       'alias' => 'FG_SUGAR',
                       'ranking' => 13
                    ]
                ]
            ]
        ];
    }
}