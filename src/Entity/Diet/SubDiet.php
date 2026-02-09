<?php

namespace App\Entity\Diet;

use App\Entity\Food;
use App\Entity\FoodGroup\FoodGroup;
use App\Entity\Diet\RatioQuantityFoodGroupParent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "sub_diet")]
#[ORM\HasLifecycleCallbacks]
class SubDiet
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(name: "name", type: "string", length: 255, nullable: true)]
    private $name;

    #[ORM\ManyToMany(targetEntity: Food::class)]
    #[ORM\JoinTable(name: "sub_diet_forbidden_food")]
    private $forbiddenFoods;

    #[ORM\ManyToMany(targetEntity: Food::class)]
    #[ORM\JoinTable(name: "sub_diet_authorized_food")]
    private $authorizedFoods;

    #[ORM\ManyToMany(targetEntity: FoodGroup::class)]
    #[ORM\JoinTable(name: "sub_diet_forbidden_food_group")]
    private $forbiddenFoodGroups;

    #[ORM\Column(name: "description", type: "string", length: 255, nullable: true)]
    private $description;

    #[ORM\ManyToMany(targetEntity: RatioQuantityFoodGroupParent::class)]
    private $ratios;

    #[ORM\ManyToOne(targetEntity: Diet::class, inversedBy: "subDiets")]
    private $diet;

    public function __construct()
    {
        $this->forbiddenFoods = new ArrayCollection();
        $this->forbiddenFoodGroups = new ArrayCollection();
        $this->ratios = new ArrayCollection();
        $this->authorizedFoods = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /** @return Collection|Food[] */
    public function getForbiddenFoods(): Collection
    {
        return $this->forbiddenFoods;
    }

    public function addForbiddenFood(Food $forbiddenFood): self
    {
        if (!$this->forbiddenFoods->contains($forbiddenFood)) {
            $this->forbiddenFoods[] = $forbiddenFood;
        }
        return $this;
    }

    public function removeForbiddenFood(Food $forbiddenFood): self
    {
        $this->forbiddenFoods->removeElement($forbiddenFood);
        return $this;
    }

    /** @return Collection|FoodGroup[] */
    public function getForbiddenFoodGroups(): Collection
    {
        return $this->forbiddenFoodGroups;
    }

    public function addForbiddenFoodGroup(FoodGroup $forbiddenFoodGroup): self
    {
        if (!$this->forbiddenFoodGroups->contains($forbiddenFoodGroup)) {
            $this->forbiddenFoodGroups[] = $forbiddenFoodGroup;
        }
        return $this;
    }

    public function removeForbiddenFoodGroup(FoodGroup $forbiddenFoodGroup): self
    {
        $this->forbiddenFoodGroups->removeElement($forbiddenFoodGroup);
        return $this;
    }

    public function getDiet(): ?Diet
    {
        return $this->diet;
    }

    public function setDiet(?Diet $diet): self
    {
        $this->diet = $diet;
        return $this;
    }

    /** @return Collection|RatioQuantityFoodGroupParent[] */
    public function getRatios(): Collection
    {
        return $this->ratios;
    }

    public function addRatio(RatioQuantityFoodGroupParent $ratio): self
    {
        if (!$this->ratios->contains($ratio)) {
            $this->ratios[] = $ratio;
        }
        return $this;
    }

    public function removeRatio(RatioQuantityFoodGroupParent $ratio): self
    {
        $this->ratios->removeElement($ratio);
        return $this;
    }

    /** @return Collection|Food[] */
    public function getAuthorizedFoods(): Collection
    {
        return $this->authorizedFoods;
    }

    public function addAuthorizedFood(Food $authorizedFood): self
    {
        if (!$this->authorizedFoods->contains($authorizedFood)) {
            $this->authorizedFoods[] = $authorizedFood;
        }
        return $this;
    }

    public function removeAuthorizedFood(Food $authorizedFood): self
    {
        $this->authorizedFoods->removeElement($authorizedFood);
        return $this;
    }
}
