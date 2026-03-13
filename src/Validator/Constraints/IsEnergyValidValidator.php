<?php

namespace App\Validator\Constraints;

use App\Service\EnergyHandler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validator pour la contrainte IsEnergyValid.
 *
 * Vérifie que la valeur de l'énergie saisie est comprise
 * dans la plage autorisée selon l'unité (kcal ou kJ).
 */
class IsEnergyValidValidator extends ConstraintValidator
{
    /**
     * Valide la valeur d'énergie.
     *
     * @param mixed $value La valeur de l'énergie à valider
     * @param Constraint|IsEnergyValid $constraint La contrainte appliquée
     */
    public function validate($value, Constraint $constraint)
    {
        // Si aucune valeur n'est fournie, on déclenche une violation
        if ($value === null || $value === '') {
            $this->context->buildViolation('Veuillez saisir une valeur d\'énergie.')
                ->addViolation();
            return; 
        }

        // Validation selon l'unité définie dans la contrainte
        switch ($constraint->unitMeasure) {

            case EnergyHandler::KCAL:
                // Vérifie que la valeur est comprise entre minKcal et maxKcal
                if ($value < $constraint->minKcal || $value > $constraint->maxKcal) {
                    $this->context->buildViolation($constraint->messageRange, [
                                        '{{ min }}' => $constraint->minKcal,
                                        '{{ max }}' => $constraint->maxKcal,
                                        '{{ unit }}' => EnergyHandler::KCAL
                                    ])
                                    ->addViolation();
                }
                break;

            case EnergyHandler::KJ:
                // Vérifie que la valeur est comprise entre minKj et maxKj
                if ((int)$value < $constraint->minKj || (int)$value > $constraint->maxKj) {
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