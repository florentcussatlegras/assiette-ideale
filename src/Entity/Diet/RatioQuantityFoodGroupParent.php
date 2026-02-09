<?php

namespace App\Entity\Diet;

use App\Entity\FoodGroup\FoodGroupParent;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "ratio_quantity_foodgroupparent_for_diet")]
class RatioQuantityFoodGroupParent
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: FoodGroupParent::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $foodGroupParent;

    #[ORM\Column(name: "ratio", type: "integer")]
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
