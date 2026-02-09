<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: "App\Repository\SpiceRepository")]
#[ORM\Table(name: "spice")]
#[ORM\HasLifecycleCallbacks]
class Spice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Veuillez saisir un nom")]
    #[Assert\Regex("/[0-9]+/", message: "Doit contenir un chiffre", groups: ["group_1"])]
    #[Assert\Length(min: 8, minMessage: "{{ limit }} characters at least", groups: ["group_2"])]
    private ?string $name = null;

    public ?string $firstname = null;

    // ------------------- Constructor -------------------
    public function __construct(?string $name = null, ?int $id = null)
    {
        $this->name = $name;
        $this->id = $id;
    }

    // ------------------- Magic Methods -------------------
    public function __toString(): string
    {
        return $this->name ?? '';
    }

    // ------------------- Getters & Setters -------------------
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
}
