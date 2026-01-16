<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\TypeMeal;

#[ORM\Entity(repositoryClass: "App\Repository\MealRepository")]
#[ORM\Table(name: "meal")]
#[ORM\HasLifecycleCallbacks]
class Meal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(name: "rank_view", type: "integer", nullable: true)]
    private ?int $rankView = null;

    #[ORM\Column(name: "eated_at", type: "string")]
    private string $eatedAt;

    #[ORM\ManyToOne(targetEntity: TypeMeal::class)]
    private ?TypeMeal $type = null;

    #[ORM\Column(name: "dish_and_foods", type: "array")]
    private array $dishAndFoods = [];

    #[ORM\Column(name: "alerts_on_dishes", type: "array")]
    private array $alertOnDishes = [];

    #[ORM\Column(name: "alerts_on_foods", type: "array")]
    private array $alertOnFoods = [];

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "meals")]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(name: "alerts_all_meals_day", type: "array")]
    private array $alertsAllMealsDay = [];

    #[ORM\Column(name: "energy_all_meals_day", type: "float")]
    private float $energyAllMealsDay;

    #[ORM\Column(name: "list_fgp_all_meals_day", type: "array")]
    private array $listFgpAllMealsDay = [];

    #[ORM\Column(name: "list_fgp_remaining_absent_all_meals_day", type: "array")]
    private array $listFgpRemainingAbsentAllMealsDay = [];

    public function __construct(
        ?string $name,
        ?int $rankView,
        string $eatedAt,
        array $dishAndFoods,
        ?TypeMeal $type,
        ?User $user,
        array $alertOnDishes,
        array $alertOnFoods
    ) {
        $this->name = $name;
        $this->rankView = $rankView;
        $this->eatedAt = $eatedAt;
        $this->dishAndFoods = $dishAndFoods;
        $this->type = $type;
        $this->user = $user;
        $this->alertOnDishes = $alertOnDishes;
        $this->alertOnFoods = $alertOnFoods;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
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

    public function getDishAndFoods(): array
    {
        return $this->dishAndFoods;
    }

    public function setDishAndFoods(array $dishAndFoods): self
    {
        $this->dishAndFoods = $dishAndFoods;
        return $this;
    }

    public function getEatedAt(): string
    {
        return $this->eatedAt;
    }

    public function setEatedAt(string $eatedAt): self
    {
        $this->eatedAt = $eatedAt;
        return $this;
    }

    public function getAlertOnDishes(): array
    {
        return $this->alertOnDishes;
    }

    public function setAlertOnDishes(array $alertOnDishes): self
    {
        $this->alertOnDishes = $alertOnDishes;
        return $this;
    }

    public function getAlertOnFoods(): array
    {
        return $this->alertOnFoods;
    }

    public function setAlertOnFoods(array $alertOnFoods): self
    {
        $this->alertOnFoods = $alertOnFoods;
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

    public function getRankView(): ?int
    {
        return $this->rankView;
    }

    public function setRankView(?int $rankView): self
    {
        $this->rankView = $rankView;
        return $this;
    }

    public function getAlertsAllMealsDay(): array
    {
        return $this->alertsAllMealsDay;
    }

    public function setAlertsAllMealsDay(array $alertsAllMealsDay): self
    {
        $this->alertsAllMealsDay = $alertsAllMealsDay;
        return $this;
    }

    public function getEnergyAllMealsDay(): float
    {
        return $this->energyAllMealsDay;
    }

    public function setEnergyAllMealsDay(float $energyAllMealsDay): self
    {
        $this->energyAllMealsDay = $energyAllMealsDay;
        return $this;
    }

    public function getListFgpAllMealsDay(): array
    {
        return $this->listFgpAllMealsDay;
    }

    public function setListFgpAllMealsDay(array $listFgpAllMealsDay): self
    {
        $this->listFgpAllMealsDay = $listFgpAllMealsDay;
        return $this;
    }

    public function getListFgpRemainingAbsentAllMealsDay(): array
    {
        return $this->listFgpRemainingAbsentAllMealsDay;
    }

    public function setListFgpRemainingAbsentAllMealsDay(array $listFgpRemainingAbsentAllMealsDay): self
    {
        $this->listFgpRemainingAbsentAllMealsDay = $listFgpRemainingAbsentAllMealsDay;
        return $this;
    }
}
