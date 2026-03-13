<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Contrainte personnalisée permettant de vérifier
 * si l'application dispose de toutes les informations
 * nécessaires pour estimer le besoin énergétique journalier
 * d'un utilisateur.
 *
 * Si certaines données du profil sont manquantes,
 * la validation échoue et un message est retourné.
 */
#[\Attribute]
class CanEstimateEnergy extends Constraint
{
    /**
     * Message retourné lorsque la validation échoue.
     *
     * @var string
     */
    public string $message = "Il manque des informations pour que nous puissions estimer votre besoin énergétique journalier. Vous pouvez compléter ces informations depuis votre profil ou saisir votre besoin si vous le connaissez.";
}