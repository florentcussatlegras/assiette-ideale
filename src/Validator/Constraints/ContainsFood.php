<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Contrainte personnalisée permettant de vérifier
 * qu'un plat contient au moins un certain nombre d'aliments.
 */
class ContainsFood extends Constraint
{
    /**
     * Message retourné lorsque la validation échoue.
     *
     * @var string
     */
    public string $message = 'Le plat doit contenir au moins {{ min }} aliment';

    /**
     * Nombre minimum d'aliments requis.
     *
     * @var int
     */
    public int $min = 1;
}