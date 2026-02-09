<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class CanEditProfile extends Constraint
{
    public $message = 'Cette information est nécessaire pour calculer votre besoin énergétique journalier.';
}
