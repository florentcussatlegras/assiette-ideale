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

    public function __construct(
        private Security $security)
    {}

    public function validate($value, Constraint $constraint)
    {
        $user = $this->security->getUser();

        if (!$constraint instanceof CanEditProfile) {
            throw new UnexpectedTypeException(
                $constraint,
                CanEditProfile::class
            );
        };

        if (
            $user->getAutomaticCalculateEnergy()
                &&
            $user->getEnergy()
                &&
            null === $value
        ) {
            $this->context->buildViolation($constraint->message)
                        ->addViolation();
        }
    }
}
