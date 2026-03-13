<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Contrainte vérifiant si l'utilisateur a bien saisi toutes les données nécessaires au calcul de son besoin énergétique
 */
#[\Attribute]
class CanEditProfile extends Constraint
{
    /**
     * Message retourné lorsque la validation échoue.
     *
     * @var string
     */
    public $message = 'Cette information est nécessaire pour calculer votre besoin énergétique journalier.';
}