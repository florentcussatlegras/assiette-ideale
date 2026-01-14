<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CanEstimateEnergy extends Constraint
{
    public $message = "Il manque des informations pour que nous puissions estimer votre besoin énergétique journalier. Vous pouvez compléter ces informations depuis votre profil ou saisir votre besoin si vous le connaissez.";
}
