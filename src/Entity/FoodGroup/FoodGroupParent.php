<?php

namespace App\Entity\FoodGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\FoodGroup\Model\ModelFoodGroup;

#[ORM\Table(name: "food_group_parent")]
#[ORM\Entity(repositoryClass: "App\Repository\FoodGroupParentRepository")]
class FoodGroupParent extends ModelFoodGroup
{
    #[ORM\Column(name: "color", type: "string", length: 255)]
    private ?string $color = null;

    #[ORM\Column(name: "degraded_color", type: "string", length: 255)]
    private ?string $degradedColor = null;

    #[ORM\OneToMany(targetEntity: FoodGroup::class, mappedBy: "parent", cascade: ["persist"])]
    private Collection $foodGroups;

    #[ORM\Column(name: "is_principal", type: "boolean")]
    private ?bool $isPrincipal = null;

    public function __construct()
    {
        $this->foodGroups = new ArrayCollection();
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
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

    /** @return Collection|FoodGroup[] */
    public function getFoodGroups(): Collection
    {
        return $this->foodGroups;
    }

    public function addFoodGroup(FoodGroup $foodGroup): self
    {
        if (!$this->foodGroups->contains($foodGroup)) {
            $this->foodGroups->add($foodGroup);
        }
        return $this;
    }

    public function removeFoodGroup(FoodGroup $foodGroup): self
    {
        $this->foodGroups->removeElement($foodGroup);
        return $this;
    }

    public function hasOneChildren(): bool
    {
        return $this->foodGroups->count() === 1;
    }

    public function getSubFoodGroups(): array
    {
        $results = [];
        foreach ($this->foodGroups as $foodGroup) {
            $results[] = $foodGroup->getSubFoodGroups();
        }
        return $results;
    }
}
