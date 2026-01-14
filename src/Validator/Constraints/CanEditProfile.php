<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CanEditProfile extends Constraint
{
    public $message = 'Cette information est nécessaire pour calculer votre besoin énergétique journalier.';
}
