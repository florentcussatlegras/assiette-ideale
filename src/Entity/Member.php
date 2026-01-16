<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Member
{
    #[Assert\NotBlank(message: "Merci de saisir un identifiant")]
    #[Assert\Length(
        min: 3, 
        minMessage: "Votre nom doit comporter au moins {{ limit }} caractères."
    )]
    private string $username;

    #[Assert\NotBlank(
        payload: ["severity" => "warning"], 
        message: "Merci de saisir une adresse email", 
        groups: ["registration"]
    )]
    #[Assert\Email(groups: ["profile_parameters", "registration"])]
    private ?string $email = null;

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }
}
