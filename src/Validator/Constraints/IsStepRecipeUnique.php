<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Contrainte pour vérifier que chaque étape d'une recette est unique.
 *
 * Utilisée pour s'assurer qu'il n'existe pas de doublons
 * dans la numérotation des étapes d'une recette.
 */
#[\Attribute]
class IsStepRecipeUnique extends Constraint
{
    /**
     * Message affiché en cas de violation de la contrainte.
     *
     * Le placeholder {{ step }} sera remplacé par le numéro d'étape en doublon.
     *
     * @var string
     */
    public $message = "L'étape {{ step }} est en plusieurs exemplaires !";
}