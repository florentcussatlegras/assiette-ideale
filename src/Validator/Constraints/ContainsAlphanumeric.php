<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ContainsAlphanumeric extends Constraint
{
    public $message = 'The value {{ string }} must only contains numbe and letters';
    public $mode = 'strict';
}
