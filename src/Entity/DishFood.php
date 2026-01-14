<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DishFood
 *
 * @ORM\Table(name="dish_food")
 * @ORM\Entity(repositoryClass="App\Repository\DishFoodRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class DishFood
{
	/**
   * @ORM\Column(name="id", type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * var integer
   *
   * @ORM\Column(name="quantity_g", type="integer", nullable=true)
   */
  private $quantityG;

  /**
   * var integer
   *
   * @ORM\Column(name="quantity_real", type="integer")
   */
  private $quantityReal;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\UnitMeasure")
   * @ORM\JoinColumn(nullable=false)
   */
  private $unitMeasure;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\Dish")
   * @ORM\JoinColumn(nullable=false)
   */
  private $dish;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\Food", cascade={"persist"})
   * @ORM\JoinColumn(nullable=false)
   */
  private $food;

//   public function __construct($food, $quantityReal, $quantityG, $unitMeasure)
//   {
//       $this->food = $food;
//       $this->quantityG = $quantityG;
//       $this->quantityReal = $quantityReal;
//       $this->unitMeasure = $unitMeasure;
//   }

  public function __toString()
  {
    return $this->food->getName() . ' - ' . $this->dish->getName(); 
  }

    /**
     * Get id
     *
     * @return integer
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
     * @return DishFood
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
     * Set food
     *
     * @param \App\Entity\Food $food
     *
     * @return DishFood
     */
    public function setFood(\App\Entity\Food $food)
    {
        $this->food = $food;

        return $this;
    }

    /**
     * Get food
     *
     * @return \App\Entity\Food
     */
    public function getFood()
    {
        return $this->food;
    }

    /**
     * Set quantityG
     *
     * @param integer $quantityG
     *
     * @return DishFood
     */
    public function setQuantityG($quantityG)
    {
        $this->quantityG = $quantityG;
    
        return $this;
    }

    /**
     * Get quantityG
     *
     * @return integer
     */
    public function getQuantityG()
    {
        return $this->quantityG;
    }

    /**
     * Set quantityReal
     *
     * @param integer $quantityReal
     *
     * @return DishFood
     */
    public function setQuantityReal($quantityReal)
    {
        $this->quantityReal = $quantityReal;
    
        return $this;
    }

    /**
     * Get quantityReal
     *
     * @return integer
     */
    public function getQuantityReal()
    {
        return $this->quantityReal;
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

 

    
}
