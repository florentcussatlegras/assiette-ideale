<?php

namespace App\Entity\Diet;

use App\Entity\FoodGroup\FoodGroupParent;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "ratio_quantity_foodgroupparent_for_diet")]
#[ORM\Entity]
class RatioQuantityFoodGroupParent
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FoodGroupParent::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?FoodGroupParent $foodGroupParent = null;

    #[ORM\Column(name: "ratio", type: "integer")]
    private ?int $ratio = null;

    public function __toString(): string
    {
        return ($this->foodGroupParent?->getName() ?? '') . ' / ' . ($this->ratio ?? '');
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
