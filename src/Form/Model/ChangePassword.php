<?php

namespace App\Form\Model;

use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;

class ChangePassword
{
    /**
     * @SecurityAssert\UserPassword(
     *  message = "Ce mot de passe ne correspond pas à votre mot de passe actuel"
     * )
     */
    protected $oldPassword;

    /**
     * Get message = "Ce mot de passe ne correspond pas à votre mot de passe actuel"
     */ 
    public function getOldPassword()
    {
        return $this->oldPassword;
    }

    /**
     * Set message = "Ce mot de passe ne correspond pas à votre mot de passe actuel"
     *
     * @return  self
     */ 
    public function setOldPassword($oldPassword)
    {
        $this->oldPassword = $oldPassword;

        return $this;
    }
}