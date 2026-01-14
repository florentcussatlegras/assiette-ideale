<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DishFoodGroup
 *
 * @ORM\Table(name="dish_food_group")
 * @ORM\Entity(repositoryClass="App\Repository\DishFoodGroupRepository")
 */
class DishFoodGroup
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Dish", inversedBy="dishFoodGroups")
     * @ORM\JoinColumn(nullable=false)
     */
    private $dish; 

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FoodGroup\FoodGroup")
     * @ORM\JoinColumn(name="foodgroup_id", nullable=false)
     */
    private $foodGroup;

    /**
     * @ORM\Column(name="quantity_for_one", type="float", nullable=true)
     */
    private $quantityForOne;

    public function __construct($foodGroup, $quantityForOne)
    {
        $this->foodGroup = $foodGroup;
        $this->quantityForOne = $quantityForOne;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set dish
     *
     * @param \App\Entity\Dish $dish
     *
     * @return DishFoodGroup
     */
    public function setDish(\App\Entity\Dish $dish)
    {
        $this->dish = $dish;

        return $this;
    }

    /**
     * Get dish
     *
     * @return \App\Entity\Dish
     */
    public function getDish()
    {
        return $this->dish;
    }

    /**
     * Set foodGroup
     *
     * @param \App\Entity\FoodGroup\FoodGroup $foodGroup
     *
     * @return DishFoodGroup
     */
    public function setFoodGroup(\App\Entity\FoodGroup\FoodGroup $foodGroup)
    {
        $this->foodGroup = $foodGroup;

        return $this;
    }

    /**
     * Get foodGroup
     *
     * @return \App\Entity\FoodGroup
     */
    public function getFoodGroup()
    {
        return $this->foodGroup;
    }

    /**
     * Set quantityForOne
     *
     * @param float $quantityForOne
     *
     * @return DishFoodGroup
     */
    public function setQuantityForOne($quantityForOne)
    {
        $this->quantityForOne = $quantityForOne;
    
        return $this;
    }

    /**
     * Get quantityForOne
     *
     * @return float
     */
    public function getQuantityForOne()
    {
        return $this->quantityForOne;
    }
}
