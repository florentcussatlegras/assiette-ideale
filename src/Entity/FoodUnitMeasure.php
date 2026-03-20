<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "food_unit_measure")]
class FoodUnitMeasure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    // Lien vers l'aliment
    #[ORM\ManyToOne(targetEntity: "App\Entity\Food", inversedBy: "foodUnitMeasures")]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Food $food;

    // Lien vers l'unité
    #[ORM\ManyToOne(targetEntity: "App\Entity\UnitMeasure")]
    #[ORM\JoinColumn(nullable: false)]
    private UnitMeasure $unitMeasure;

    // Le poids en grammes de cet aliment pour cette unité
    #[ORM\Column(type: "float")]
    private float $weightInGrams;

    public function __toString()
    {
        return $this->unitMeasure;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFood(): Food
    {
        return $this->food;
    }

    public function setFood(Food $food): self
    {
        $this->food = $food;
        return $this;
    }

    public function getUnitMeasure(): UnitMeasure
    {
        return $this->unitMeasure;
    }

    public function setUnitMeasure(UnitMeasure $unitMeasure): self
    {
        $this->unitMeasure = $unitMeasure;
        return $this;
    }

    public function getWeightInGrams(): float
    {
        return $this->weightInGrams;
    }

    public function setWeightInGrams(float $weightInGrams): self
    {
        $this->weightInGrams = $weightInGrams;
        return $this;
    }
}