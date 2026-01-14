<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\HttpFoundation\RequestStack;

class ContainsFoodValidator extends ConstraintValidator
{
    private $session;

    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
    }

    public function validate($value, $constraint)
    {
        if (!$this->session->has('recipe_foods') || empty($this->session->get('recipe_foods'))) {
            $this->context->buildViolation($constraint->message)
                            ->setParameter('{{ min }}', $constraint->min)
                            ->addViolation();
        }
    }
}
