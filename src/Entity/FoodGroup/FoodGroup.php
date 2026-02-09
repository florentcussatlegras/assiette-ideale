<?php

namespace App\Entity\FoodGroup;

use App\Entity\Food;
use App\Entity\UnitMeasure;
use App\Entity\Diet\Diet;
use App\Entity\FoodGroup\Model\ModelFoodGroup;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\FoodGroupRepository")]
#[ORM\Table(name: "food_group")]
class FoodGroup extends ModelFoodGroup
{
    #[ORM\ManyToOne(targetEntity: FoodGroupParent::class, inversedBy: "foodGroups", cascade: ["persist"])]
    private ?FoodGroupParent $parent = null;

    #[ORM\ManyToOne(targetEntity: Food::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Food $representativeFood = null;

    #[ORM\Column(name: "quantity_reference", type: "integer", nullable: true)]
    private ?int $quantityReferenceOfRepresentativeFood = null;

    #[ORM\ManyToOne(targetEntity: UnitMeasure::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?UnitMeasure $representativeFoodMeasuredBy = null;

    #[ORM\Column(name: "order", type: "integer", nullable: true)]
    private ?int $order = null;

    #[ORM\ManyToMany(targetEntity: Diet::class, mappedBy: "forbiddenFoodGroups")]
    private Collection $forbiddenDiets;

    public function __construct()
    {
        $this->forbiddenDiets = new ArrayCollection();
    }

    // ------------------- Getters & Setters -------------------

    public function getParent(): ?FoodGroupParent
    {
        return $this->parent;
    }

    public function setParent(?FoodGroupParent $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    public function getRepresentativeFood(): ?Food
    {
        return $this->representativeFood;
    }

    public function setRepresentativeFood(?Food $representativeFood): self
    {
        $this->representativeFood = $representativeFood;
        return $this;
    }

    public function getQuantityReferenceOfRepresentativeFood(): ?int
    {
        return $this->quantityReferenceOfRepresentativeFood;
    }

    public function setQuantityReferenceOfRepresentativeFood(?int $quantity): self
    {
        $this->quantityReferenceOfRepresentativeFood = $quantity;
        return $this;
    }

    public function getRepresentativeFoodMeasuredBy(): ?UnitMeasure
    {
        return $this->representativeFoodMeasuredBy;
    }

    public function setRepresentativeFoodMeasuredBy(?UnitMeasure $unitMeasure): self
    {
        $this->representativeFoodMeasuredBy = $unitMeasure;
        return $this;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(?int $order): self
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
            $diet->addForbiddenFoodGroup($this);
        }
        return $this;
    }

    public function removeForbiddenDiet(Diet $diet): self
    {
        if ($this->forbiddenDiets->removeElement($diet)) {
            $diet->removeForbiddenFoodGroup($this);
        }
        return $this;
    }
}
