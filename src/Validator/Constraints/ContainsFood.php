<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ContainsFood extends Constraint
{
    public $message = 'Le plat doit contenir au moins {{ min }} aliment';
    public $min = 1;
}
