<?php

namespace App\Entity\Alert;

use App\Entity\Dish;
use App\Entity\Food;
use App\Entity\FoodGroup\FoodGroupParent;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Alert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Dish::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Dish $dish = null;

    #[ORM\ManyToOne(targetEntity: Food::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Food $food = null;

    #[ORM\ManyToOne(targetEntity: FoodGroupParent::class)]
    private ?FoodGroupParent $foodGroupParent = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $rankMeal = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $rankDish = null;

    #[ORM\ManyToOne(targetEntity: LevelAlert::class)]
    private ?LevelAlert $levelAlert = null;

    #[ORM\Column(type: "boolean", name: "already_not_recommended")]
    private bool $alreadyNotRecommended;

    public function __construct(
        ?Dish $dish,
        ?Food $food,
        ?FoodGroupParent $foodGroupParent,
        ?int $rankMeal,
        ?int $rankDish,
        ?LevelAlert $levelAlert,
        bool $alreadyNotRecommended
    ) {
        $this->dish = $dish;
        $this->food = $food;
        $this->foodGroupParent = $foodGroupParent;
        $this->rankMeal = $rankMeal;
        $this->rankDish = $rankDish;
        $this->levelAlert = $levelAlert;
        $this->alreadyNotRecommended = $alreadyNotRecommended;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDish(): ?Dish
    {
        return $this->dish;
    }

    public function setDish(?Dish $dish): self
    {
        $this->dish = $dish;
        return $this;
    }

    public function getFood(): ?Food
    {
        return $this->food;
    }

    public function setFood(?Food $food): self
    {
        $this->food = $food;
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

    public function getRankMeal(): ?int
    {
        return $this->rankMeal;
    }

    public function setRankMeal(?int $rankMeal): self
    {
        $this->rankMeal = $rankMeal;
        return $this;
    }

    public function getRankDish(): ?int
    {
        return $this->rankDish;
    }

    public function setRankDish(?int $rankDish): self
    {
        $this->rankDish = $rankDish;
        return $this;
    }

    public function getLevelAlert(): ?LevelAlert
    {
        return $this->levelAlert;
    }

    public function setLevelAlert(?LevelAlert $levelAlert): self
    {
        $this->levelAlert = $levelAlert;
        return $this;
    }

    public function getAlreadyNotRecommended(): bool
    {
        return $this->alreadyNotRecommended;
    }

    public function setAlreadyNotRecommended(bool $alreadyNotRecommended): self
    {
        $this->alreadyNotRecommended = $alreadyNotRecommended;
        return $this;
    }
}
