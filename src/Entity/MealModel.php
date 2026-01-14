<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * MealModel
 *
 * @ORM\Table(name="meal_model")
 * @ORM\Entity(repositoryClass="App\Repository\MealModelRepository")
 */
class MealModel
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
     * @ORM\Column(name="name", type="string")
     */
    private $name;

     /**
     * @var \Entity
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TypeMeal")
     */
    private $type;  

    /**
     * @ORM\Column(name="dish_and_foods", type="array")
     */
    private $dishAndFoods;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", cascade={"persist"})
     */
    private $user;

    public function __construct($name, $type, $dishAndFoods, $user)
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

    public function getDishAndFoods(): ?array
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

    public function getName(): ?string
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