<?php

namespace App\Entity\FoodGroup;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\FoodGroup\Model\ModelFoodGroup;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Diet\Diet;

/**
 * FoodGroup
 *
 * @ORM\Table(name="food_group")
 * @ORM\Entity(repositoryClass="App\Repository\FoodGroupRepository")
 */
class FoodGroup extends ModelFoodGroup
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FoodGroup\FoodGroupParent", inversedBy="foodGroups", cascade={"persist"})
     */
    private $parent;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Food")
     * @ORM\JoinColumn(nullable=true) 
     */
    private $representativeFood;

    /**
     * @ORM\Column(name="quantity_reference", type="integer", nullable=true)
     */
    private $quantityReferenceOfRepresentativeFood;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UnitMeasure")
     * @ORM\JoinColumn(nullable=true) 
     */
    private $representativeFoodMeasuredBy;

    /**
     * @ORM\Column(name="order", type="integer", nullable=true)
     */
    private $order;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Diet\Diet", mappedBy="forbiddenFoodGroups")
     */
    private $forbiddenDiets;

    public function __construct()
    {
        $this->forbiddenDiets = new ArrayCollection();
    }

    /**
     * Set representativeFood
     *
     * @param \AppBundle\Rntity\Food $representativeFood
     *
     * @return FoodGroup
     */
    public function setRepresentativeFood(\App\Entity\Food $representativeFood)
    {
        $this->representativeFood = $representativeFood;

        return $this;
    }

    /**
     * Get representativeFood
     *
     * @return \AppBundle\Entity\Food
     */
    public function getRepresentativeFood()
    {
        return $this->representativeFood;
    }

    /**
     * Set quantityReferenceOfRepresentativeFood
     *
     * @param integer $quantityReferenceOfRepresentativeFood
     *
     * @return FoodGroup
     */
    public function setQuantityReferenceOfRepresentativeFood($quantityReferenceOfRepresentativeFood)
    {
        $this->quantityReferenceOfRepresentativeFood = $quantityReferenceOfRepresentativeFood;

        return $this;
    }

    /**
     * Get quantityReferenceOfRepresentativeFood
     *
     * @return integer
     */
    public function getQuantityReferenceOfRepresentativeFood()
    {
        return $this->quantityReferenceOfRepresentativeFood;
    }

    /**
     * Set representativeFoodMeasuredBy
     *
     * @param \AppBundle\Entity\UnitMeasure $representativeFoodMeasuredBy
     *
     * @return FoodGroup
     */
    public function setRepresentativeFoodMeasuredBy(\App\Entity\UnitMeasure $representativeFoodMeasuredBy)
    {
        $this->representativeFoodMeasuredBy = $representativeFoodMeasuredBy;

        return $this;
    }

    /**
     * Get representativeFoodMeasuredBy
     *
     * @return \AppBundle\Entity\UnitMeasure
     */
    public function getRepresentativeFoodMeasuredBy()
    {
        return $this->representativeFoodMeasuredBy;
    }

    /**
     * Set parent
     *
     * @param \AppBundle\Entity\FoodGroup\FoodGroupParent $parent
     *
     * @return FoodGroup
     */
    public function setParent(\App\Entity\FoodGroup\FoodGroupParent $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \AppBundle\Entity\FoodGroupParent
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set canBeUsedAsPrincipal
     *
     * @param boolean $canBeUsedAsPrincipal
     *
     * @return FoodGroup
     */
    public function setCanBeUsedAsPrincipal($canBeUsedAsPrincipal)
    {
        $this->canBeUsedAsPrincipal = $canBeUsedAsPrincipal;

        return $this;
    }

    /**
     * Get canBeUsedAsPrincipal
     *
     * @return boolean
     */
    public function getCanBeUsedAsPrincipal()
    {
        return $this->canBeUsedAsPrincipal;
    }

    /**
     * Set canBeUsedAsComplement
     *
     * @param boolean $canBeUsedAsComplement
     *
     * @return FoodGroup
     */
    public function setCanBeUsedAsComplement($canBeUsedAsComplement)
    {
        $this->canBeUsedAsComplement = $canBeUsedAsComplement;

        return $this;
    }

    /**
     * Get canBeUsedAsComplement
     *
     * @return boolean
     */
    public function getCanBeUsedAsComplement()
    {
        return $this->canBeUsedAsComplement;
    }

    /**
     * Set foodGroupBrother
     *
     * @param \AppBundle\Entity\FoodGroup\FoodGroup $foodGroupBrother
     *
     * @return FoodGroup
     */
    public function setFoodGroupBrother(\App\Entity\FoodGroup\FoodGroup $foodGroupBrother = null)
    {
        $this->foodGroupBrother = $foodGroupBrother;

        return $this;
    }

    /**
     * Get foodGroupBrother
     *
     * @return \AppBundle\Entity\FoodGroup
     */
    public function getFoodGroupBrother()
    {
        return $this->foodGroupBrother;
    }

    public function hasOneChildren()
    {
        if ($this->subFoodGroups->count() == 1)
        {
            return true;
        }

        return false;
    }

    /**
     * Set canInsignifiantQuantityBeConsidered
     *
     * @param boolean $canInsignifiantQuantityBeConsidered
     *
     * @return FoodGroup
     */
    public function setCanInsignifiantQuantityBeConsidered($canInsignifiantQuantityBeConsidered)
    {
        $this->canInsignifiantQuantityBeConsidered = $canInsignifiantQuantityBeConsidered;
    
        return $this;
    }

    /**
     * Get canInsignifiantQuantityBeConsidered
     *
     * @return boolean
     */
    public function getCanInsignifiantQuantityBeConsidered()
    {
        return $this->canInsignifiantQuantityBeConsidered;
    }

    /**
     * Set rankForDishmealItem
     *
     * @param integer $rankForDishmealItem
     *
     * @return FoodGroup
     */
    public function setRankForDishmealItem($rankForDishmealItem)
    {
        $this->rankForDishmealItem = $rankForDishmealItem;
    
        return $this;
    }

    /**
     * Get rankForDishmealItem
     *
     * @return integer
     */
    public function getRankForDishmealItem()
    {
        return $this->rankForDishmealItem;
    }

    /**
     * Get the value of order
     * 
     * @return integer
     */ 
    public function getOrder(): ?int
    {
        return $this->order;
    }

    /**
     * Set the value of order
     */ 
    public function setOrder(int $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return Collection|Diet[]
     */
    public function getForbiddenDiets(): Collection
    {
        return $this->forbiddenDiets;
    }
    public function addForbiddenDiet(Diet $diet): self
    {
        if (!$this->forbiddenDiets->contains($diet)) {
            $this->forbiddenDiets[] = $diet;
            $diet->addForbiddenFood($this);
        }
        return $this;
    }
    public function removeForbiddenDiet(Diet $diet): self
    {
        if ($this->forbiddenDiets->removeElement($diet)) {
            $diet->removeForbiddenFood($this);
        }
        return $this;
    }
}
