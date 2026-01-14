<?php

namespace App\Entity\Diet;

use App\Entity\FoodGroup\FoodGroupParent;
use Doctrine\ORM\Mapping as ORM;

/**
 * RatioQuantityFoodGroupParentForDiet
 *
 * @ORM\Table(name="ratio_quantity_foodgroupparent_for_diet")
 * @ORM\Entity
 */
class RatioQuantityFoodGroupParent
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
     * @ORM\JoinColumn(nullable=false)
     */
    private $foodGroupParent;

    /**
     * @ORM\Column(name="ratio", type="integer")
     */
    private $ratio;

    public function __toString()
    {
    	return $this->foodGroupParent->getName() . ' / ' . $this->ratio;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRatio(): ?int
    {
        return $this->ratio;
    }

    public function setRatio(int $ratio): self
    {
        $this->ratio = $ratio;

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