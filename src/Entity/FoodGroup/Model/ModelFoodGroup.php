<?php

namespace App\Entity\FoodGroup\Model;

use Doctrine\ORM\Mapping as ORM;
use Cocur\Slugify\Slugify;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class ModelFoodGroup
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

    // ------------------- Méthodes utilitaires -------------------

    public function getClassName(): string
    {
        return static::class;
    }

    public function getClass(): string
    {
        $arrayClass = explode("\\", static::class);
        return $arrayClass[count($arrayClass) - 1];
    }

    public function __toString(): string
    {
        return $this->name;
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
}
