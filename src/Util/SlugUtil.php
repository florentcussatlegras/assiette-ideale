<?php

namespace App\Util;

/**
 * Outils pour manipuler les slugs.
 */
class SlugUtil
{
    /**
     * Génère un slug à partir d'une chaîne.
     */
    public static function slugify(string $str): string
    {
        $slug = strtolower($str);

        $slug = preg_replace("/[^a-z0-9\s-]/", "", $slug);
        $slug = trim(preg_replace("/[\s-]+/", " ", $slug));
        $slug = preg_replace("/\s/", "-", $slug);

        return $slug;
    }
}