<?php

namespace App\Entity;

use App\Entity\FoodGroup\FoodGroupParent;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\RecommendedQuantityRepository")]
#[ORM\Table(name: "recommended_quantity")]
class RecommendedQuantity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FoodGroupParent::class)]
    private ?FoodGroupParent $foodGroupParent = null;

    #[ORM\Column(type: "integer")]
    private ?int $energy = null;

    #[ORM\Column(type: "string")]
    private ?string $quantity = null;

    public function __construct(FoodGroupParent $foodGroupParent, int $energy, string $quantity)
    {
        $this->foodGroupParent = $foodGroupParent;
        $this->energy = $energy;
        $this->quantity = $quantity;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->quantity;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(?string $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getFoodGroupParent(): ?FoodGroupParent
    {
        return $this->foodGroupParent;
    }

    public function setFoodGroupParent(?FoodGroupParent $foodGroupParent): self
    {
        $this->foodGroupParent = $foodGroupParent;

        return $this;
    }

    public function getEnergy(): ?int
    {
        return $this->energy;
    }

    public function setEnergy(int $energy): self
    {
        $this->energy = $energy;

        return $this;
    }
}
