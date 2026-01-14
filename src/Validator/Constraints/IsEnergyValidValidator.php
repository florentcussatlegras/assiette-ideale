<?php

namespace App\Validator\Constraints;

use App\Service\EnergyHandler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsEnergyValidValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        switch ($constraint->unitMeasure) {
            case EnergyHandler::KCAL:
                if ($value >= $constraint->maxKcal || $value < $constraint->minKcal) {
                    $this->context->buildViolation($constraint->messageRange, [
                                        '{{ min }}' => $constraint->minKcal,
                                        '{{ max }}' => $constraint->maxKcal,
                                        '{{ unit }}' => EnergyHandler::KCAL
                                    ])
                                    ->addViolation();
                }
                break;
            case EnergyHandler::KJ:
                if ((int)$value >= $constraint->maxKj || (int)$value < $constraint->minKj) {
                    $this->context->buildViolation($constraint->messageRange, [
                                    '{{ min }}' => $constraint->minKj,
                                    '{{ max }}' => $constraint->maxKj,
                                    '{{ unit }}' => EnergyHandler::KJ
                                ])
                                ->addViolation();
                }
                break;
        }
    }
}
