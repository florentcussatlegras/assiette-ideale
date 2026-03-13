<?php

namespace App\Validator\Constraints;

use App\Service\EnergyHandler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\ConstraintValidator;

class CanEstimateEnergyValidator extends ConstraintValidator
{
    public function __construct(
        private EnergyHandler $energyHandler, 
        private Security $security)
    {}

    public function validate($value, Constraint $constraint)
    {
        $user = $this->security->getUser();

        if (
            $user->getAutomaticCalculateEnergy()
                &&
            count($this->energyHandler->profileMissingForEnergy()) > 0
        ) {

            $this->context->buildViolation($constraint->message)
                        ->addViolation();
                        
        }
    }
}