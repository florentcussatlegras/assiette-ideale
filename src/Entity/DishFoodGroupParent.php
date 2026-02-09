<?php

namespace App\Entity;

use App\Entity\FoodGroup\FoodGroupParent;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "dish_food_group_parent")]
class DishFoodGroupParent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Dish::class, inversedBy: "dishFoodGroupParents")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dish $dish = null;

    #[ORM\ManyToOne(targetEntity: FoodGroupParent::class)]
    #[ORM\JoinColumn(name: "foodgroupparent_id", nullable: false)]
    private ?FoodGroupParent $foodGroupParent = null;

    // ------------------- Constructor -------------------
    public function __construct(Dish $dish, FoodGroupParent $foodGroupParent)
    {
        $this->dish = $dish;
        $this->foodGroupParent = $foodGroupParent;
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

    public function getFoodGroupParent(): ?FoodGroupParent
    {
        return $this->foodGroupParent;
    }

    public function setFoodGroupParent(FoodGroupParent $foodGroupParent): self
    {
        $this->foodGroupParent = $foodGroupParent;
        return $this;
    }

    // ------------------- Magic Methods -------------------
    public function __toString(): string
    {
        return ($this->foodGroupParent ? $this->foodGroupParent->getName() : 'N/A')
            . ' - '
            . ($this->dish ? $this->dish->getName() : 'N/A');
    }
}
