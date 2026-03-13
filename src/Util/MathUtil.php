<?php

namespace App\Util;

/**
 * Outils mathématiques.
 */
class MathUtil
{
    /**
     * Arrondit un coefficient vers la valeur la plus proche.
     */
    public static function roundCoeff(float $n): float
    {
        $values = [0, 0.25, 0.33, 0.5, 0.66, 0.75, 1];

        $closest = $values[0];

        foreach ($values as $value) {
            if (abs($n - $value) < abs($n - $closest)) {
                $closest = $value;
            }
        }

        return $closest;
    }

    /**
     * Convertit un coefficient en fraction lisible.
     */
    public static function coeffToFraction(float $n): string
    {
        return match ($n) {
            0 => '0',
            0.25 => '1/4',
            0.33 => '1/3',
            0.5 => '1/2',
            0.66 => '2/3',
            0.75 => '3/4',
            1 => '1',
            default => (string)$n
        };
    }

    /**
     * Trouve la valeur la plus proche dans un tableau.
     */
    public static function closest(float $n, array $values): float
    {
        $closest = $values[0];

        foreach ($values as $value) {
            if (abs($n - $value) < abs($n - $closest)) {
                $closest = $value;
            }
        }

        return $closest;
    }
}