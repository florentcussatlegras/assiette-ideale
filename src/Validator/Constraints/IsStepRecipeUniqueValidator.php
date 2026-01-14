<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsStepRecipeUniqueValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $steps = $value->toArray();

        array_walk($steps, function (&$step, $key) {
            $step = $step->getRankStep();
        });

        $steps = array_count_values($steps);
        $steps = array_filter($steps, function ($var) {
            return $var > 1;
        });

        if (!empty($steps)) {
            foreach (array_keys($steps) as $step) {
                $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ step }}', $step)
                        ->addViolation();
            }
        }
    }
}
