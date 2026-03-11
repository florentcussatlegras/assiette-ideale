<?php

namespace App\Service;

/**
 * DashboardUtils.php
 *
 * Service utilitaire permettant de générer la configuration
 * des blocs affichés sur le tableau de bord de l'application.
 *
 * Il fournit les catégories principales et secondaires
 * utilisées pour construire les cartes de navigation du dashboard
 * (repas du jour, semaine, bilans, etc.).
 *
 * Les titres utilisent des clés de traduction Symfony.
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class DashboardUtils
{
    /**
     * Retourne les blocs principaux du tableau de bord.
     *
     * Ces éléments correspondent aux actions principales
     * accessibles directement depuis la page d'accueil.
     *
     * @return array Liste des catégories principales
     */
    public function getPrincipal(): array
    {
        return [
            [
                // Clé de traduction du titre
                'title' => 'dashboard.category.meal_day',

                // Nom du pictogramme associé
                'picto' => 'food',

                // Route Symfony vers la page correspondante
                'route' => 'meal_day'
            ],
            [
                'title' => 'Vos repas de la semaine',
                'picto' => 'dish_week',
                'route' => 'menu_week_menu'
            ]
        ];
    }

    /**
     * Retourne les blocs secondaires du tableau de bord.
     *
     * Ces éléments correspondent aux fonctionnalités
     * complémentaires accessibles depuis le dashboard
     * (repas, bilans, etc.).
     *
     * @param string|null $dateOfDay Date optionnelle du jour
     *
     * @return array Liste des catégories secondaires
     */
    public function getSecondary($dateOfDay = null): array
    {
        return [
            [
                // Clé de traduction du titre
                'title' => 'dashboard.category.meal_day',

                // Illustration associée à la carte
                'picto' => 'illustration_meal_day.jpg',

                // Route vers les repas du jour
                'route' => 'menu_week_get_meals',
            ],
            [
                'title' => 'dashboard.category.meal_week',
                'picto' => 'illustration_meals_week.jpg',
                'route' => 'menu_week_menu',
            ],
            [
                'title' => 'dashboard.category.balance_sheet',
                'picto' => 'illustration_balance_sheet.jpg',
                'route' => 'app_balance_sheet_index'
            ],
        ];
    }
}