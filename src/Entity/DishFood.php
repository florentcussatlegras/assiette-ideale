<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\DishFoodRepository")]
#[ORM\Table(name: "dish_food")]
#[ORM\HasLifecycleCallbacks]
class DishFood
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "quantity_g", type: "integer", nullable: true)]
    private ?int $quantityG = null;

    #[ORM\Column(name: "quantity_real", type: "integer")]
    private int $quantityReal;

    #[ORM\ManyToOne(targetEntity: UnitMeasure::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?UnitMeasure $unitMeasure = null;

    #[ORM\ManyToOne(targetEntity: Dish::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dish $dish = null;

    #[ORM\ManyToOne(targetEntity: Food::class, cascade: ["persist"])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Food $food = null;

    // ------------------- Magic Methods -------------------
    public function __toString(): string
    {
        return ($this->food ? $this->food->getName() : '') 
            . ' - ' 
            . ($this->dish ? $this->dish->getName() : '');
    }

    // ------------------- Getters & Setters -------------------
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantityG(): ?int
    {
        return $this->quantityG;
    }

    public function setQuantityG(?int $quantityG): self
    {
        $this->quantityG = $quantityG;
        return $this;
    }

    public function getQuantityReal(): int
    {
        return $this->quantityReal;
    }

    public function setQuantityReal(int $quantityReal): self
    {
        $this->quantityReal = $quantityReal;
        return $this;
    }

    public function getUnitMeasure(): ?UnitMeasure
    {
        return $this->unitMeasure;
    }

    public function setUnitMeasure(?UnitMeasure $unitMeasure): self
    {
        $this->unitMeasure = $unitMeasure;
        return $this;
    }

    public function getDish(): ?Dish
    {
        return $this->dish;
    }

    public function setDish(Dish $dish): self
    {
        $this->dish = $dish;
        return $this;
    }

    public function getFood(): ?Food
    {
        return $this->food;
    }

    public function setFood(Food $food): self
    {
        $this->food = $food;
        return $this;
    }
}
