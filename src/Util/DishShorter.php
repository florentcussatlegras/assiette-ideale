<?php

namespace App\Util;

use App\Entity\Dish;

/**
 * Comparateurs pour trier les plats.
 */
class DishSorter
{
    /**
     * Trie les plats par nom.
     */
    public static function byName(Dish $a, Dish $b): int
    {
        return strcmp(
            strtolower($a->getName()),
            strtolower($b->getName())
        );
    }

    /**
     * Trie les plats par rang.
     */
    public static function byRank(Dish $a, Dish $b): int
    {
        return $a->getRank() <=> $b->getRank();
    }
}