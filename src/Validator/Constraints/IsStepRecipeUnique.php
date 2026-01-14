<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsStepRecipeUnique extends Constraint
{
    public $message = "L'étape {{ step }} est en plusieurs exemplaires !";
}
