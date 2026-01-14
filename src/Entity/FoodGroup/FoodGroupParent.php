<?php

namespace App\Entity\FoodGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\FoodGroup\Model\ModelFoodGroup;

/**
 * FoodGroupParent
 *
 * @ORM\Table(name="food_group_parent")
 * @ORM\Entity(repositoryClass="App\Repository\FoodGroupParentRepository")
 */
class FoodGroupParent extends ModelFoodGroup
{
    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=255)
     */    
    private $color;

    /**
     * @var string
     *
     * @ORM\Column(name="degraded_color", type="string", length=255)
     */    
    private $degradedColor;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\FoodGroup\FoodGroup", mappedBy="parent", cascade={"persist"})
     */
    private $foodGroups;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_principal", type="boolean")
     */
    private $isPrincipal;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->foodGroups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set color
     *
     *
     * @return FoodGroupParent
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color
     *
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Add foodGroup
     *
     * @param \App\Entity\FoodGroup\FoodGroup $foodGroup
     *
     * @return FoodGroupParent
     */
    public function addFoodGroup(\App\Entity\FoodGroup\FoodGroup $foodGroup)
    {
        $this->foodGroups[] = $foodGroup;

        return $this;
    }

    /**
     * Remove foodGroup
     *
     */
    public function removeFoodGroup(\App\Entity\FoodGroup\FoodGroup $foodGroup)
    {
        $this->foodGroups->removeElement($foodGroup);
    }

    /**
     * Get foodGroups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFoodGroups()
    {
        return $this->foodGroups;
    }

    public function hasOneChildren()
    {
        if ($this->foodGroups->count() == 1)
        {
            return true;
        }

        return false;
    }

    /**
     * Set colorCode
     *
     * @param string $colorCode
     *
     * @return FoodGroupParent
     */
    public function setColorCode($colorCode)
    {
        $this->colorCode = $colorCode;
    
        return $this;
    }

    /**
     * Get colorCode
     *
     * @return string
     */
    public function getColorCode()
    {
        return $this->colorCode;
    }

    public function getSubFoodGroups()
    {
        $results = [];
        
        foreach ($this->foodGroups->toArray() as $foodGroup) {
            $results[] = $foodGroup->getSubFoodGroups();
        }

        return $results;
    }

    public function getDegradedColor(): ?string
    {
        return $this->degradedColor;
    }

    public function setDegradedColor(string $degradedColor): self
    {
        $this->degradedColor = $degradedColor;

        return $this;
    }

    public function getIsPrincipal(): ?bool
    {
        return $this->isPrincipal;
    }

    public function setIsPrincipal(bool $isPrincipal): self
    {
        $this->isPrincipal = $isPrincipal;

        return $this;
    }
}
