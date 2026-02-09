<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "gender")]
#[ORM\Entity]
class Gender
{
    public const MALE = "H";
    public const FEMALE = "F";
    public const OTHER = "A";

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private $id;

    #[ORM\Column(name: "name", type: "string", length: 255)]
    private $name;

    #[ORM\Column(name: "long_name", type: "string", length: 255)]
    private $longName;

    #[ORM\Column(name: "alias", type: "string", length: 255, nullable: true)]
    private $alias;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setLongName(string $longName): self
    {
        $this->longName = $longName;

        return $this;
    }

    public function getLongName(): string
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
