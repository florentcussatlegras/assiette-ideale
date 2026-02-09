<?php

namespace App\Service;

class DashboardUtils
{
    public function getPrincipal(): array
    {
        return [
            [
                // 'title' => 'Vos repas du jour',
                'title' => 'dashboard.category.meal_day',
                'picto' => 'food',
                'route' => 'meal_day'
            ],
            [
                'title' => 'Vos repas de la semaine',
                'picto' => 'dish_week',
                'route' => 'menu_week_menu'
            ]
        ];
    }

    public function getSecondary($dateOfDay = null): array
    {
        return [
            [
                // 'title' => 'Repas du jour',
                'title' => 'dashboard.category.meal_day',
                'picto' => 'illustration_meal_day.jpg',
                'route' => 'menu_week_get_meals',
            ],
            [
                // 'title' => 'Repas de la semaine',
                'title' => 'dashboard.category.meal_week',
                'picto' => 'illustration_meals_week.jpg',
                'route' => 'menu_week_menu',
            ],
            // [
            //     // 'title' => 'Besoins énergétiques',
            //     'title' => 'dashboard.category.energy_needs',
            //     'picto' => 'energy',
            //     'route' => 'app_profile_index',
            // ],
            // [
            //     // 'title' => 'Recommendations nutritionnelles',
            //     'title' => 'dashboard.category.nutritional_recommendations',
            //     'picto' => 'quantity',
            //     'route' => 'app_recommendation_index',
            // ],
            // [
            //     // 'title' => 'Vos repas',
            //     'title' => 'dashboard.category.model_meals',
            //     'picto' => 'food',
            //     'route' => 'model_meal_list',
            // ],
            [
                // 'title' => 'Bilans',
                'title' => 'dashboard.category.balance_sheet',
                'picto' => 'illustration_balance_sheet.jpg',
                'route' => 'app_balance_sheet_index'
            ],
            // [
            //     // 'title' => 'Evolution de votre alimentation',
            //     'title' => 'dashboard.category.diet_evolution',
            //     'picto' => 'evolution',
            //     'route' => 'app_evolution_index'
            // ],
        ];
    }
}