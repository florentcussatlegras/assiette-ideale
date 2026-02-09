<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\UnitTimeRepository")]
#[ORM\Table(name: "unit_time")]
class UnitTime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string")]
    private string $text;

    #[ORM\Column(type: "string")]
    private string $alias;

    // ------------------- Magic Methods -------------------

    public function __toString(): string
    {
        return $this->text;
    }

    // ------------------- Getters & Setters -------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }
}
