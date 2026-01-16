<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Table(name: "unit_measure")]
#[ORM\Entity(repositoryClass: "App\Repository\UnitMeasureRepository")]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity("alias")]
class UnitMeasure
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "name", type: "string", length: 255)]
    private ?string $name = null;

    #[ORM\Column(name: "alias", type: "string", length: 10)]
    private ?string $alias = null;

    #[ORM\Column(name: "gram_ratio", type: "float", nullable: true)]
    private ?float $gramRatio = null;

    #[ORM\Column(name: "is_unit", type: "boolean")]
    private ?bool $isUnit = null;

    public function __toString(): string
    {
        return $this->name ?? 'NULL';
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function getGramRatio(): ?float
    {
        return $this->gramRatio;
    }

    public function setGramRatio(?float $gramRatio): self
    {
        $this->gramRatio = $gramRatio;

        return $this;
    }

    public function isIsUnit(): ?bool
    {
        return $this->isUnit;
    }

    public function setIsUnit(bool $isUnit): static
    {
        $this->isUnit = $isUnit;

        return $this;
    }
}
