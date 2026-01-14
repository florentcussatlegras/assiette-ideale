<?php

namespace App\Entity\Diet;

use App\Entity\Food;
use App\Entity\FoodGroup\FoodGroup;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Diet
 *
 * @ORM\Table(name="diet")
 * @ORM\Entity(repositoryClass="App\Repository\DietRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Diet
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
     * @ORM\ManyToMany(targetEntity="App\Entity\Food", inversedBy="diets")
     * @ORM\JoinTable(name="diet_forbidden_food")
     */
    private $forbiddenFoods;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Food")
     * @ORM\JoinTable(name="diet_authorized_food")
     */
    private $authorizedFoods;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\FoodGroup\FoodGroup", inversedBy="diets")
     * @ORM\JoinTable(name="diet_forbidden_food_group")
     */
    private $forbiddenFoodGroups;

    /**
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Diet\RatioQuantityFoodGroupParent")
     */
    private $ratios;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Diet\SubDiet", mappedBy="diet")
     */
    private $subDiets;

    public function __toString()
    {
        return $this->name;
    }

    public function __construct()
    {
        $this->forbiddenFoods = new ArrayCollection();
        $this->forbiddenFoodGroups = new ArrayCollection();
        $this->ratios = new ArrayCollection();
        $this->subDiets = new ArrayCollection();
        $this->authorizedFoods = new ArrayCollection();
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

    /**
     * @return Collection|Food[]
     */
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
        if ($this->forbiddenFoods->contains($forbiddenFood)) {
            $this->forbiddenFoods->removeElement($forbiddenFood);
        }

        return $this;
    }

    /**
     * @return Collection|FoodGroup[]
     */
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
        if ($this->forbiddenFoodGroups->contains($forbiddenFoodGroup)) {
            $this->forbiddenFoodGroups->removeElement($forbiddenFoodGroup);
        }

        return $this;
    }

    /**
     * @return Collection|SubDiet[]
     */
    public function getSubDiets(): Collection
    {
        return $this->subDiets;
    }

    public function addSubDiet(SubDiet $subDiet): self
    {
        if (!$this->subDiets->contains($subDiet)) {
            $this->subDiets[] = $subDiet;
            $subDiet->setDiet($this);
        }

        return $this;
    }

    public function removeSubDiet(SubDiet $subDiet): self
    {
        if ($this->subDiets->contains($subDiet)) {
            $this->subDiets->removeElement($subDiet);
            // set the owning side to null (unless already changed)
            if ($subDiet->getDiet() === $this) {
                $subDiet->setDiet(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|RatioQuantityFoodGroupParent[]
     */
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
        if ($this->ratios->contains($ratio)) {
            $this->ratios->removeElement($ratio);
        }

        return $this;
    }

    /**
     * @return Collection|Food[]
     */
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