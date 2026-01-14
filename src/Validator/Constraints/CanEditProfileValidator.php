<?php

namespace App\Validator\Constraints;

use App\Service\EnergyHandler;
use Symfony\Component\Validator\Constraint;
use App\Validator\Constraints\CanEditProfile;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CanEditProfileValidator extends ConstraintValidator
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
        if (!$constraint instanceof CanEditProfile) {
            throw new UnexpectedTypeException(
                $constraint,
                CanEditProfile::class
            );
        };

        if (
            $this->user->getAutomaticCalculateEnergy()
                &&
            $this->user->getEnergy()
                &&
            null === $value
        ) {
            $this->context->buildViolation($constraint->message)
                        ->addViolation();
        }
    }
}
