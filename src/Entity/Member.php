<?php

namespace App\Entity;

use App\Entity\Hours;
use App\Entity\Gender;
use App\Entity\Diet\Diet;
use App\Entity\EnergyGroup;
use App\Entity\Diet\SubDiet;
use Doctrine\DBAL\Types\Types;
use App\Service\ProfileHandler;
use App\Service\UploaderHelper;
use App\Entity\PhysicalActivity;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\NutrientRecommendationUser;
use App\Validator\Constraints as AppAssert;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;


class Member
{
    /**
     * @Assert\NotBlank(
     *      message="Merci de saisir un identifiant",
     * )
     * @Assert\Length(
     *      min=3, 
     *      minMessage="Votre nom doit comporter au moins {{ limit }} caractères.",
     * )
     */
    private $username;

    /**
     * @Assert\NotBlank(payload={"severity"="warning"}, message="Merci de saisir une adresse email", groups={"registration"})
     * @Assert\Email(groups={"profile_parameters", "registration"})
     */
    private $email;

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }
}
