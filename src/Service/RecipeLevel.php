<?php

namespace App\Service;

class RecipeLevel
{
    public static function getLevels(): array
    {
        return ['recipe.level.easy' => 'recipe.level.easy', 'recipe.level.average' => 'recipe.level.average', 'recipe.level.hard' => 'recipe.level.hard'];
    }
}