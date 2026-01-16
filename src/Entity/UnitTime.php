<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "unit_time")]
#[ORM\Entity(repositoryClass: "App\Repository\UnitTimeRepository")]
class UnitTime
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "text", type: "string")]
    private ?string $text = null;

    #[ORM\Column(name: "alias", type: "string")]
    private ?string $alias = null;

    public function __toString(): string
    {
        return $this->text ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }
}
