<?php

namespace App\Service;

/**
 * TypeDishHandler.php
 * 
 * Service de gestion des types de plats (entrée, plat principal, dessert).
 *
 * Objectif :
 *  - Centraliser les types de plats utilisés dans l'application.
 *  - Fournir un tableau de choix utilisable dans les formulaires ou filtres.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class TypeDishHandler
{
    // Lés des types de plats.
    const TYPE_NAMES = [
        'dish.type.entry',
        'dish.type.main',
        'dish.type.dessert'
    ];

    /**
     * Retourne les types de plats sous forme clé => valeur.
     *
     * @return array
     */
    public static function getChoices(): array
    {
        return array_combine(array_values(self::TYPE_NAMES), array_values(self::TYPE_NAMES));
    }
}