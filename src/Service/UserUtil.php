<?php

namespace App\Service;

use App\Entity\Gender;
use Symfony\Component\Security\Core\Security;

/**
 * UserUtil.php
 * 
 * Service utilitaire pour les utilisateurs.
 *
 * Fonctionnalités principales :
 * - Calculer le poids idéal en fonction de la taille et du genre. 
 *    Utilise la formule de Lorentz pour calculer le poids idéal :
 *      - Homme : taille (cm) - 100 - ((taille - 150) / 4)
 *      - Femme : taille (cm) - 100 - ((taille - 150) / 2.5)
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class UserUtil
{
    public function __construct(
        private Security $security
    ) {}

    /**
     * Calcule le poids idéal de l'utilisateur connecté selon la formule de Lorentz.
     *
     * @return int|null Poids idéal en kg ou null si la taille n'est pas définie
     */
    public function setIdealWeight(): ?int
    {
        $user = $this->security->getUser();
        $height = $user->getHeight();

        if (!$height) {
            return null;
        }

        switch ($user->getGender()->getAlias()) 
        {
            case Gender::MALE:
                return round($height - 100 - (($height - 150) / 4));
            case Gender::FEMALE:
                return round($height - 100 - (($height - 150) / 2.5));
            default:
                // Par défaut, on utilise la formule masculine
                return round($height - 100 - (($height - 150) / 4));
        }
    }
}