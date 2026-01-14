<?php

namespace App\Service;

use App\Entity\Gender;
use Symfony\Component\Security\Core\Security;

class UserUtil
{
    public function __construct(
        private Security $security
    ) {}

    /*  
        Formule de Lorentz
        Poids idéal d’un homme (en Kg) = Taille (en cm) – 100 – ((Taille (en cm) – 150) /4 ).
        Poids idéal d’une femme (en Kg) = Taille (en cm) – 100 – ((Taille (en cm) – 150) /2,5 ).
    */
    public function setIdealWeight(): ?int
    {
        $user = $this->security->getUser();
        $height = $user->getHeight();

        switch ($user->getGender()->getAlias()) 
        {
            case Gender::MALE:
                return round($height - 100 - (($height - 150) / 4));
                break;
            case Gender::FEMALE:
                return round($height - 100 - (($height - 150) / 2.5));
                break;
            default:
                return round($height - 100 - (($height - 150) / 4));
                break;
        }

        return null;
    }
}