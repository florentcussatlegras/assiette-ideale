<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsEnergyValid extends Constraint
{
    public $unitMeasure;
    public $minKcal = 300;
    public $maxKcal = 12000;
    public $minKj = 1000;
    public $maxKj = 30000;
    public $messageRange = "L'énergie doit être comprise entre {{ min }} et {{ max }} {{ unit }}";
}
