<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class IsStepRecipeUnique extends Constraint
{
    public $message = "L'étape {{ step }} est en plusieurs exemplaires !";
}
