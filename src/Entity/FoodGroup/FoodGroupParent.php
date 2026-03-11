<?php

namespace App\Entity\FoodGroup;

use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\FoodGroup\Model\ModelFoodGroup;

#[ORM\Entity(repositoryClass: "App\Repository\FoodGroupParentRepository")]
#[ORM\Table(name: "food_group_parent")]
class FoodGroupParent
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

    #[ORM\Column(type: "string", length: 255)]
    private string $color;

    #[ORM\Column(type: "string", length: 255)]
    private string $degradedColor;

    #[ORM\OneToMany(
        targetEntity: FoodGroup::class,
        mappedBy: "parent",
        cascade: ["persist"]
    )]
    private Collection $foodGroups;

    #[ORM\Column(type: "boolean")]
    private bool $isPrincipal;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $content = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $picture = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $funFact = null;

    public function __toString(): string
    {
        return $this->name ?? 'FoodGroupParent';
    }

    public function __construct()
    {
        $this->foodGroups = new ArrayCollection();
    }

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

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function addFoodGroup(FoodGroup $foodGroup): self
    {
        if (!$this->foodGroups->contains($foodGroup)) {
            $this->foodGroups[] = $foodGroup;
            $foodGroup->setParent($this); // 🔥 TRÈS IMPORTANT
        }

        return $this;
    }

    public function removeFoodGroup(FoodGroup $foodGroup): self
    {
        if ($this->foodGroups->removeElement($foodGroup)) {
            if ($foodGroup->getParent() === $this) {
                $foodGroup->setParent(null);
            }
        }

        return $this;
    }

    public function getFoodGroups(): Collection
    {
        return $this->foodGroups;
    }

    public function hasOneChildren(): bool
    {
        return $this->foodGroups->count() === 1;
    }

    public function getSubFoodGroups(): array
    {
        $results = [];

        foreach ($this->foodGroups as $foodGroup) {
            $results[] = $foodGroup->getSubFoodGroups();
        }

        return $results;
    }

    public function getDegradedColor(): ?string
    {
        return $this->degradedColor;
    }

    public function setDegradedColor(string $degradedColor): self
    {
        $this->degradedColor = $degradedColor;

        return $this;
    }

    public function getIsPrincipal(): bool
    {
        return $this->isPrincipal;
    }

    public function setIsPrincipal(bool $isPrincipal): self
    {
        $this->isPrincipal = $isPrincipal;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): self
    {
        $this->picture = $picture;

        return $this;
    }

    public function getFunFact(): ?string
    {
        return $this->funFact;
    }

    public function setFunFact(?string $funFact): self
    {
        $this->funFact = $funFact;
        return $this;
    }
}
