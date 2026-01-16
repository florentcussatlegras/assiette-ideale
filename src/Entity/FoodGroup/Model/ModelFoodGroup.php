<?php

namespace App\Entity\FoodGroup\Model;

use Doctrine\ORM\Mapping as ORM;
use Cocur\Slugify\Slugify;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class ModelFoodGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "name", type: "string", length: 255)]
    private ?string $name = null;

    #[ORM\Column(name: "semi_short_name", type: "string", length: 255)]
    private ?string $semiShortName = null;

    #[ORM\Column(name: "short_name", type: "string", length: 255)]
    private ?string $shortName = null;

    #[ORM\Column(name: "slug", type: "string", length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(name: "alias", type: "string", length: 255)]
    private ?string $alias = null;

    #[ORM\Column(name: "slug_alias", type: "string", length: 255, nullable: true)]
    private ?string $slugAlias = null;

    #[ORM\Column(name: "ranking", type: "integer")]
    private ?int $ranking = null;

    public function getClassName(): string
    {
        return get_class($this);
    }

    public function getClass(): string
    {
        $arrayClass = explode("\\", get_class($this));
        return $arrayClass[count($arrayClass) - 1];
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setShortName(string $shortName): self
    {
        $this->shortName = $shortName;
        return $this;
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function setSemiShortName(string $semiShortName): self
    {
        $this->semiShortName = $semiShortName;
        return $this;
    }

    public function getSemiShortName(): ?string
    {
        return $this->semiShortName;
    }

    public function setRanking(int $ranking): self
    {
        $this->ranking = $ranking;
        return $this;
    }

    public function getRanking(): ?int
    {
        return $this->ranking;
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

    public function getAlias(): ?string
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

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setSlugValue(): self
    {
        $slugify = new Slugify();
        $this->slug = $slugify->slugify($this->name);
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setSlugAliasValue(): self
    {
        $slugify = new Slugify();
        $this->slugAlias = $slugify->slugify($this->alias);
        return $this;
    }
}
