<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use App\Entity\User;
use App\Entity\TypeMeal;

/**
 * Meal
 *
 * @ORM\Table(name="meal")
 * @ORM\Entity(repositoryClass="App\Repository\MealRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Meal
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(name="rank_view", type="integer", nullable=true)
     */
    private $rankView;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="eated_at", type="string")
     */
    private $eatedAt;

     /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TypeMeal")
     */
    private $type;

    /**
     * @ORM\Column(name="dish_and_foods", type="array")
     */
    private $dishAndFoods;

    /**
     * @ORM\Column(name="alerts_on_dishes", type="array")
     */
    private $alertOnDishes;

    /**
     * @ORM\Column(name="alerts_on_foods", type="array")
     */
    private $alertOnFoods;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="meals")
     * @ORM\JoinColumn(nullable=true)
     */
    private $user;

    /**
     * @ORM\Column(name="alerts_all_meals_day", type="array")
     */
    private $alertsAllMealsDay;

    /**
     * @ORM\Column(name="energy_all_meals_day", type="float")
     */
    private $energyAllMealsDay;

    /**
     * @ORM\Column(name="list_fgp_all_meals_day", type="array")
     */
    private $listFgpAllMealsDay;

    /**
     * @ORM\Column(name="list_fgp_remaining_absent_all_meals_day", type="array")
     */
    private $listFgpRemainingAbsentAllMealsDay;

    public function __construct($name, $rankView, $eatedAt, $dishAndFoods, $type, $user, $alertOnDishes, $alertOnFoods)
    {
        $this->name = $name;
        $this->rankView = $rankView;
        $this->eatedAt = $eatedAt;
        $this->dishAndFoods = $dishAndFoods;
        $this->type = $type;
        $this->alertOnDishes = $alertOnDishes;
        $this->alertOnFoods = $alertOnFoods;
        $this->user = $user;
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

    

    public function getDishAndFoods(): ?array
    {
        return $this->dishAndFoods;
    }

    public function setDishAndFoods(array $dishAndFoods): self
    {
        $this->dishAndFoods = $dishAndFoods;

        return $this;
    }

    public function getEatedAt(): ?string
    {
        return $this->eatedAt;
    }

    public function setEatedAt(string $eatedAt): self
    {
        $this->eatedAt = $eatedAt;

        return $this;
    }

    public function getAlertOnDishes(): ?array
    {
        return $this->alertOnDishes;
    }

    public function setAlertOnDishes(array $alertOnDishes): self
    {
        $this->alertOnDishes = $alertOnDishes;

        return $this;
    }

    public function getAlertOnFoods(): ?array
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

    public function getAlertsAllMealsDay(): array|bool
    {
        return $this->alertsAllMealsDay;
    }

    public function setAlertsAllMealsDay(array $alertsAllMealsDay): static
    {
        $this->alertsAllMealsDay = $alertsAllMealsDay;

        return $this;
    }

    public function getEnergyAllMealsDay(): ?float
    {
        return $this->energyAllMealsDay;
    }

    public function setEnergyAllMealsDay(float $energyAllMealsDay): static
    {
        $this->energyAllMealsDay = $energyAllMealsDay;

        return $this;
    }

    public function getListFgpAllMealsDay(): array
    {
        return $this->listFgpAllMealsDay;
    }

    public function setListFgpAllMealsDay(array $listFgpAllMealsDay): static
    {
        $this->listFgpAllMealsDay = $listFgpAllMealsDay;

        return $this;
    }

    public function getListFgpRemainingAbsentAllMealsDay(): array
    {
        return $this->listFgpRemainingAbsentAllMealsDay;
    }

    public function setListFgpRemainingAbsentAllMealsDay(array $listFgpRemainingAbsentAllMealsDay): static
    {
        $this->listFgpRemainingAbsentAllMealsDay = $listFgpRemainingAbsentAllMealsDay;

        return $this;
    }
}
