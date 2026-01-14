<?php

namespace App\Entity;

use App\Entity\FoodGroup\FoodGroupParent;
use Doctrine\ORM\Mapping as ORM;

/**
 * DishFoodGroupParent
 *
 * @ORM\Table(name="dish_food_group_parent")
 * @ORM\Entity
 */
class DishFoodGroupParent
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Dish", inversedBy="dishFoodGroupParents")
     * @ORM\JoinColumn(nullable=false)
     */
    private $dish; 

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FoodGroup\FoodGroupParent")
     * @ORM\JoinColumn(name="foodgroupparent_id", nullable=false)
     */
    private $foodGroupParent;

    public function __construct($dish, $foodGroupParent)
    {
        $this->dish = $dish;
        $this->foodGroupParent = $foodGroupParent;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDish(): ?Dish
    {
        return $this->dish;
    }

    public function setDish(?Dish $dish): self
    {
        $this->dish = $dish;

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

    
}
