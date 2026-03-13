<?php

namespace App\Util;

/**
 * Outils pour manipuler des tableaux.
 */
class ArrayUtil
{
    /**
     * Supprime les doublons d'un tableau multidimensionnel.
     */
    public static function uniqueByKey(array $array, string $key): array
    {
        $result = [];
        $seen = [];

        foreach ($array as $item) {
            if (!in_array($item[$key], $seen)) {
                $seen[] = $item[$key];
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Filtre un tableau pour garder uniquement les ids uniques.
     */
    public static function uniqueIdFilter(array $obj): bool
    {
        static $ids = [];

        if (in_array($obj['id'], $ids)) {
            return false;
        }

        $ids[] = $obj['id'];

        return true;
    }
}