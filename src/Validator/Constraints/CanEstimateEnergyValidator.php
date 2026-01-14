<?php

namespace App\Validator\Constraints;

use App\Service\EnergyHandler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\ConstraintValidator;

class CanEstimateEnergyValidator extends ConstraintValidator
{
    private $energyHandler;
    private $security;

    public function __construct(EnergyHandler $energyHandler, Security $security)
    {
        $this->energyHandler = $energyHandler;
        $this->user = $security->getUser();
    }

    public function validate($value, Constraint $constraint)
    {
        if (
            $this->user->getAutomaticCalculateEnergy()
                &&
            count($this->energyHandler->profileMissingForEnergy()) > 0
        ) {

            $this->context->buildViolation($constraint->message)
                        ->addViolation();
                        
        }
    }
}