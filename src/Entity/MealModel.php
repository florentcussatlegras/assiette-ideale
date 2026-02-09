<?php

namespace App\Entity;

use App\Repository\MealModelRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MealModelRepository::class)]
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

    #[ORM\Column(type: "array")]
    private array $dishAndFoods = [];

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ["persist"])]
    private ?User $user = null;

    /**
     * Énergie calorique calculée
     */
    #[ORM\Column(type: "integer", options: ["default" => 0])]
    private int $energy = 0;

    public function __construct(
        string $name,
        ?TypeMeal $type,
        array $dishAndFoods,
        User $user
    ) {
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

    public function getEnergy(): int
    {
        return $this->energy;
    }

    public function setEnergy(int $energy): self
    {
        $this->energy = $energy;

        return $this;
    }
}
