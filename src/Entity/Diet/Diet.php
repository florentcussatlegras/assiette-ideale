<?php

namespace App\Entity\Diet;

use App\Entity\Food;
use App\Entity\FoodGroup\FoodGroup;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\DietRepository")]
#[ORM\Table(name: "diet")]
#[ORM\HasLifecycleCallbacks]
class Diet
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Food::class, inversedBy: "diets")]
    #[ORM\JoinTable(name: "diet_forbidden_food")]
    private Collection $forbiddenFoods;

    #[ORM\ManyToMany(targetEntity: Food::class)]
    #[ORM\JoinTable(name: "diet_authorized_food")]
    private Collection $authorizedFoods;

    #[ORM\ManyToMany(targetEntity: FoodGroup::class, inversedBy: "diets")]
    #[ORM\JoinTable(name: "diet_forbidden_food_group")]
    private Collection $forbiddenFoodGroups;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: RatioQuantityFoodGroupParent::class)]
    private Collection $ratios;

    #[ORM\OneToMany(mappedBy: "diet", targetEntity: SubDiet::class)]
    private Collection $subDiets;

    public function __construct()
    {
        $this->forbiddenFoods = new ArrayCollection();
        $this->forbiddenFoodGroups = new ArrayCollection();
        $this->ratios = new ArrayCollection();
        $this->subDiets = new ArrayCollection();
        $this->authorizedFoods = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
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

    public function addForbiddenFood(Food $food): self
    {
        if (!$this->forbiddenFoods->contains($food)) {
            $this->forbiddenFoods->add($food);
        }
        return $this;
    }

    public function removeForbiddenFood(Food $food): self
    {
        $this->forbiddenFoods->removeElement($food);
        return $this;
    }

    /** @return Collection|FoodGroup[] */
    public function getForbiddenFoodGroups(): Collection
    {
        return $this->forbiddenFoodGroups;
    }

    public function addForbiddenFoodGroup(FoodGroup $group): self
    {
        if (!$this->forbiddenFoodGroups->contains($group)) {
            $this->forbiddenFoodGroups->add($group);
        }
        return $this;
    }

    public function removeForbiddenFoodGroup(FoodGroup $group): self
    {
        $this->forbiddenFoodGroups->removeElement($group);
        return $this;
    }

    /** @return Collection|SubDiet[] */
    public function getSubDiets(): Collection
    {
        return $this->subDiets;
    }

    public function addSubDiet(SubDiet $subDiet): self
    {
        if (!$this->subDiets->contains($subDiet)) {
            $this->subDiets->add($subDiet);
            $subDiet->setDiet($this);
        }
        return $this;
    }

    public function removeSubDiet(SubDiet $subDiet): self
    {
        if ($this->subDiets->removeElement($subDiet)) {
            if ($subDiet->getDiet() === $this) {
                $subDiet->setDiet(null);
            }
        }
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
            $this->ratios->add($ratio);
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

    public function addAuthorizedFood(Food $food): self
    {
        if (!$this->authorizedFoods->contains($food)) {
            $this->authorizedFoods->add($food);
        }
        return $this;
    }

    public function removeAuthorizedFood(Food $food): self
    {
        $this->authorizedFoods->removeElement($food);
        return $this;
    }
}
