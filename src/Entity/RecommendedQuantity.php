<?php

namespace App\Entity;

use App\Entity\EnergyGroup;
use App\Entity\FoodGroup\FoodGroupParent;
use Doctrine\ORM\Mapping as ORM;

/**
 * RecommendedQuantity
 *
 * @ORM\Table(name="recommended_quantity")
 * @ORM\Entity(repositoryClass="App\Repository\RecommendedQuantityRepository")
 */
class RecommendedQuantity
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
     * @ORM\ManyToOne(targetEntity="App\Entity\FoodGroup\FoodGroupParent")
     */
    private $foodGroupParent;

    /**
     * @ORM\Column(type="integer")
     */
    private $energy;

    /**
     * @ORM\Column(type="string")
     *
     */
    private $quantity;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->quantity;
    }

    public function __construct($foodGroupParent, $energy, $quantity)
    {
    	$this->foodGroupParent = $foodGroupParent;
    	$this->energy = $energy;
    	$this->quantity = $quantity;
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