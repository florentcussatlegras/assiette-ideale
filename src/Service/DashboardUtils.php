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
                'picto' => 'food',
                'route' => 'menu_week_get_meals',
            ],
            [
                // 'title' => 'Repas de la semaine',
                'title' => 'dashboard.category.meal_week',
                'picto' => 'dish_week',
                'route' => 'menu_week_menu',
            ],
            [
                // 'title' => 'Besoins énergétiques',
                'title' => 'dashboard.category.energy_needs',
                'picto' => 'energy',
                'route' => 'app_profile_index',
            ],
            [
                // 'title' => 'Recommendations nutritionnelles',
                'title' => 'dashboard.category.nutritional_recommendations',
                'picto' => 'quantity',
                'route' => 'app_recommendation_index',
            ],
            [
                // 'title' => 'Vos repas',
                'title' => 'dashboard.category.model_meals',
                'picto' => 'food',
                'route' => 'model_meal_list',
            ],
            [
                // 'title' => 'Recettes',
                'title' => 'dashboard.category.dishes',
                'picto' => 'library_dish',
                'route' => 'app_dish_list',
            ],
            [
                // 'title' => 'Aliments',
                'title' => 'dashboard.category.foods',
                'picto' => 'library_food',
                'route' => 'app_food_list',
            ],
            [
                // 'title' => 'Bilans',
                'title' => 'dashboard.category.balance_sheet',
                'picto' => 'result',
                'route' => 'app_balance_sheet_index'
            ],
            [
                // 'title' => 'Evolution de votre alimentation',
                'title' => 'dashboard.category.diet_evolution',
                'picto' => 'evolution',
                'route' => 'app_evolution_index'
            ],
        ];
    }
}