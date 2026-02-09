<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: "App\Repository\UnitMeasureRepository")]
#[ORM\Table(name: "unit_measure")]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity("alias")]
class UnitMeasure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: "string", length: 10)]
    private ?string $alias = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $gramRatio = null;

    #[ORM\Column(type: "boolean")]
    private bool $isUnit = false;

    // ------------------- Constructor -------------------
    public function __construct(?string $name = null, ?string $alias = null, ?float $gramRatio = null, bool $isUnit = false)
    {
        $this->name = $name;
        $this->alias = $alias;
        $this->gramRatio = $gramRatio;
        $this->isUnit = $isUnit;
    }

    // ------------------- Magic Methods -------------------
    public function __toString(): string
    {
        return $this->name ?? 'NULL';
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

    public function isIsUnit(): bool
    {
        return $this->isUnit;
    }

    public function setIsUnit(bool $isUnit): self
    {
        $this->isUnit = $isUnit;
        return $this;
    }
}
