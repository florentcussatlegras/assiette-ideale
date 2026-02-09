<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\FoodGroup\FoodGroup;

#[ORM\Entity(repositoryClass: "App\Repository\DishFoodGroupRepository")]
#[ORM\Table(name: "dish_food_group")]
class DishFoodGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Dish::class, inversedBy: "dishFoodGroups")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dish $dish = null;

    #[ORM\ManyToOne(targetEntity: FoodGroup::class)]
    #[ORM\JoinColumn(name: "foodgroup_id", nullable: false)]
    private ?FoodGroup $foodGroup = null;

    #[ORM\Column(name: "quantity_for_one", type: "float", nullable: true)]
    private ?float $quantityForOne = null;

    // ------------------- Constructor -------------------
    public function __construct(FoodGroup $foodGroup, ?float $quantityForOne = null)
    {
        $this->foodGroup = $foodGroup;
        $this->quantityForOne = $quantityForOne;
    }

    // ------------------- Getters & Setters -------------------
    public function getId(): ?int
    {
        return $this->id;
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

    public function getFoodGroup(): ?FoodGroup
    {
        return $this->foodGroup;
    }

    public function setFoodGroup(FoodGroup $foodGroup): self
    {
        $this->foodGroup = $foodGroup;
        return $this;
    }

    public function getQuantityForOne(): ?float
    {
        return $this->quantityForOne;
    }

    public function setQuantityForOne(?float $quantityForOne): self
    {
        $this->quantityForOne = $quantityForOne;
        return $this;
    }

    // ------------------- Magic Methods -------------------
    public function __toString(): string
    {
        return ($this->foodGroup ? $this->foodGroup->getName() : 'N/A')
            . ' - '
            . ($this->dish ? $this->dish->getName() : 'N/A');
    }
}
