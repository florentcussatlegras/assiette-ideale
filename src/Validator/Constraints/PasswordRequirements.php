<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

class PasswordRequirements extends Compound
{
    public function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\Length(['min' => 4]),
            // new Assert\NotCompromisedPassword()
        ];
    }
}
