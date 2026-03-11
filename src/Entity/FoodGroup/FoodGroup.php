<?php

namespace App\Entity\FoodGroup;

use Cocur\Slugify\Slugify;
use App\Entity\Food;
use App\Entity\UnitMeasure;
use App\Entity\Diet\Diet;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\FoodGroupRepository")]
#[ORM\Table(name: "food_group")]
class FoodGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    #[ORM\Column(type: "string", length: 255)]
    private string $semiShortName;

    #[ORM\Column(type: "string", length: 255)]
    private string $shortName;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $alias;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $slugAlias = null;

    #[ORM\Column(type: "integer")]
    private int $ranking;

    #[ORM\ManyToOne(targetEntity: FoodGroupParent::class, inversedBy: "foodGroups", cascade: ["persist"])]
    private ?FoodGroupParent $parent = null;

    #[ORM\ManyToOne(targetEntity: Food::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Food $representativeFood = null;

    #[ORM\Column(name: "quantity_reference", type: "integer", nullable: true)]
    private ?int $quantityReferenceOfRepresentativeFood = null;

    #[ORM\ManyToOne(targetEntity: UnitMeasure::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?UnitMeasure $representativeFoodMeasuredBy = null;

    #[ORM\Column(name: "order", type: "integer", nullable: true)]
    private ?int $order = null;

    #[ORM\ManyToMany(targetEntity: Diet::class, mappedBy: "forbiddenFoodGroups")]
    private Collection $forbiddenDiets;

    public function __toString(): string
    {
        return $this->name ?? 'FoodGroup';
    }

    public function __construct()
    {
        $this->forbiddenDiets = new ArrayCollection();
    }

    // ------------------- Getters & Setters -------------------

    public function getClassName(): string
    {
        return static::class;
    }

    public function getClass(): string
    {
        $arrayClass = explode("\\", static::class);
        return $arrayClass[count($arrayClass) - 1];
    }

    // ------------------- Getters & Setters -------------------

    public function getId(): ?int
    {
        return $this->id;
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

    public function getShortName(): string
    {
        return $this->shortName;
    }

    public function setShortName(string $shortName): self
    {
        $this->shortName = $shortName;
        return $this;
    }

    public function getSemiShortName(): string
    {
        return $this->semiShortName;
    }

    public function setSemiShortName(string $semiShortName): self
    {
        $this->semiShortName = $semiShortName;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    public function getSlugAlias(): ?string
    {
        return $this->slugAlias;
    }

    public function setSlugAlias(?string $slugAlias): self
    {
        $this->slugAlias = $slugAlias;
        return $this;
    }

    public function getRanking(): int
    {
        return $this->ranking;
    }

    public function setRanking(int $ranking): self
    {
        $this->ranking = $ranking;
        return $this;
    }

    // ------------------- Slug Generation -------------------

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setSlugValue(): void
    {
        $slugify = new Slugify();
        $this->slug = $slugify->slugify($this->name);
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setSlugAliasValue(): void
    {
        $slugify = new Slugify();
        $this->slugAlias = $slugify->slugify($this->alias);
    }

    public function getParent(): ?FoodGroupParent
    {
        return $this->parent;
    }

    public function setParent(?FoodGroupParent $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    public function getRepresentativeFood(): ?Food
    {
        return $this->representativeFood;
    }

    public function setRepresentativeFood(?Food $representativeFood): self
    {
        $this->representativeFood = $representativeFood;
        return $this;
    }

    public function getQuantityReferenceOfRepresentativeFood(): ?int
    {
        return $this->quantityReferenceOfRepresentativeFood;
    }

    public function setQuantityReferenceOfRepresentativeFood(?int $quantity): self
    {
        $this->quantityReferenceOfRepresentativeFood = $quantity;
        return $this;
    }

    public function getRepresentativeFoodMeasuredBy(): ?UnitMeasure
    {
        return $this->representativeFoodMeasuredBy;
    }

    public function setRepresentativeFoodMeasuredBy(?UnitMeasure $unitMeasure): self
    {
        $this->representativeFoodMeasuredBy = $unitMeasure;
        return $this;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(?int $order): self
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return Collection|Diet[]
     */
    public function getForbiddenDiets(): Collection
    {
        return $this->forbiddenDiets;
    }

    public function addForbiddenDiet(Diet $diet): self
    {
        if (!$this->forbiddenDiets->contains($diet)) {
            $this->forbiddenDiets[] = $diet;
            $diet->addForbiddenFoodGroup($this);
        }
        return $this;
    }

    public function removeForbiddenDiet(Diet $diet): self
    {
        if ($this->forbiddenDiets->removeElement($diet)) {
            $diet->removeForbiddenFoodGroup($this);
        }
        return $this;
    }
}
