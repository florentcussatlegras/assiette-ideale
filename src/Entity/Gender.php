<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "gender")]
class Gender
{
    public const MALE = "H";
    public const FEMALE = "F";
    public const OTHER = "A";

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $name = null;

    #[ORM\Column(name: "long_name", type: "string", length: 255)]
    private ?string $longName = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $alias = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setLongname(string $longName): self
    {
        $this->longName = $longName;
        return $this;
    }

    public function getLongName(): ?string
    {
        return $this->longName;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }
}
