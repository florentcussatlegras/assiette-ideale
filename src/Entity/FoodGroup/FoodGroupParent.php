<?php

namespace App\Entity\FoodGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\FoodGroup\Model\ModelFoodGroup;

#[ORM\Entity(repositoryClass: "App\Repository\FoodGroupParentRepository")]
#[ORM\Table(name: "food_group_parent")]
class FoodGroupParent extends ModelFoodGroup
{
    #[ORM\Column(type: "string", length: 255)]
    private string $color;

    #[ORM\Column(type: "string", length: 255)]
    private string $degradedColor;

    #[ORM\OneToMany(
        targetEntity: FoodGroup::class,
        mappedBy: "parent",
        cascade: ["persist"]
    )]
    private Collection $foodGroups;

    #[ORM\Column(type: "boolean")]
    private bool $isPrincipal;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $content = null;

    public function __construct()
    {
        $this->foodGroups = new ArrayCollection();
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function addFoodGroup(FoodGroup $foodGroup): self
    {
        if (!$this->foodGroups->contains($foodGroup)) {
            $this->foodGroups[] = $foodGroup;
        }

        return $this;
    }

    public function removeFoodGroup(FoodGroup $foodGroup): self
    {
        $this->foodGroups->removeElement($foodGroup);

        return $this;
    }

    public function getFoodGroups(): Collection
    {
        return $this->foodGroups;
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

    public function getDegradedColor(): ?string
    {
        return $this->degradedColor;
    }

    public function setDegradedColor(string $degradedColor): self
    {
        $this->degradedColor = $degradedColor;

        return $this;
    }

    public function getIsPrincipal(): bool
    {
        return $this->isPrincipal;
    }

    public function setIsPrincipal(bool $isPrincipal): self
    {
        $this->isPrincipal = $isPrincipal;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }
}
