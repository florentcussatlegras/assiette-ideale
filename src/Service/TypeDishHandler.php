<?php

namespace App\Service;

class TypeDishHandler
{
    const TYPE_NAMES = [
        'dish.type.entry',
        'dish.type.main',
        'dish.type.dessert'
    ];

    public static function getChoices(): array
    {
        return array_combine(array_values(self::TYPE_NAMES), array_values(self::TYPE_NAMES));
    }
}