<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\TypeMeal;

#[ORM\Entity(repositoryClass: "App\Repository\MealModelRepository")]
#[ORM\Table(name: "meal_model")]
class MealModel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string")]
    private string $name;

    #[ORM\ManyToOne(targetEntity: TypeMeal::class)]
    private ?TypeMeal $type = null;

    #[ORM\Column(name: "dish_and_foods", type: "array")]
    private array $dishAndFoods = [];

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ["persist"])]
    private ?User $user = null;

    public function __construct(string $name, ?TypeMeal $type, array $dishAndFoods, ?User $user)
    {
        $this->name = $name;
        $this->type = $type;
        $this->dishAndFoods = $dishAndFoods;
        $this->user = $user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDishAndFoods(): array
    {
        return $this->dishAndFoods;
    }

    public function setDishAndFoods(array $dishAndFoods): self
    {
        $this->dishAndFoods = $dishAndFoods;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getType(): ?TypeMeal
    {
        return $this->type;
    }

    public function setType(?TypeMeal $type): self
    {
        $this->type = $type;
        return $this;
    }
}
