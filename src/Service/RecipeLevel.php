<?php

namespace App\Service;

/**
 * RecipeLevel.php
 * 
 * Service fournissant les différents niveaux de difficulté pour une recette.
 * Les niveaux sont utilisés pour catégoriser les recettes dans l'application.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class RecipeLevel
{
    /**
     * Retourne la liste des niveaux de difficulté disponibles pour les recettes.
     *
     * @return array Tableau associatif des niveaux, où la clé et la valeur sont identiques.
     *               Les valeurs sont des clés de traduction (ex. pour Symfony Translator)
     *               - 'recipe.level.easy' => facile
     *               - 'recipe.level.average' => moyenne
     *               - 'recipe.level.hard' => difficile
     */
    public static function getLevels(): array
    {
        return [
            'recipe.level.easy' => 'recipe.level.easy',
            'recipe.level.average' => 'recipe.level.average',
            'recipe.level.hard' => 'recipe.level.hard'
        ];
    }
}