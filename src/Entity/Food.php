<?php

namespace App\Entity;

use Cocur\Slugify\Slugify;
use App\Service\UploaderHelper;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\FoodGroup\FoodGroup;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\Diet\Diet;
use App\Entity\NutritionalTable;

#[ORM\Entity(repositoryClass: "App\Repository\FoodRepository")]
#[ORM\Table(name: "food")]
#[ORM\HasLifecycleCallbacks]
class Food
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Veuillez saisir un nom")]
    #[Groups(["searchable"])]
    private ?string $name = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: self::class, cascade: ["persist", "remove"])]
    #[ORM\JoinColumn(name: "sub_food_group_id", nullable: true)]
    private ?Food $subFoodGroup = null;

    #[ORM\Column(type: "boolean")]
    private bool $isSubFoodGroup = false;

    #[ORM\ManyToOne(targetEntity: FoodGroup::class, cascade: ["persist", "remove"])]
    #[ORM\JoinColumn(name: "food_group_id", nullable: false, onDelete: "CASCADE")]
    private ?FoodGroup $foodGroup = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $equivalenceReferenceFoodGroup = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $picture = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $info = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $medianWeight = null;

    #[ORM\Column(type: "boolean", nullable: true)]
    private ?bool $showMedianWeight = null;

    #[ORM\ManyToMany(targetEntity: "App\Entity\UnitMeasure", cascade: ["persist"])]
    private Collection $unitMeasures;

    #[ORM\Column(type: "boolean")]
    private bool $haveGluten = false;

    #[ORM\Column(type: "boolean")]
    private bool $haveLactose = false;

    #[ORM\Column(type: "boolean")]
    private bool $notConsumableRaw = false;

    #[ORM\Column(type: "boolean")]
    private bool $canBeAPart = false;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $energy = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $lipid = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $carbohydrate = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $protein = null;

    #[ORM\OneToOne(targetEntity: NutritionalTable::class, cascade: ["persist"])]
    private ?NutritionalTable $nutritionalTable = null;

    #[ORM\ManyToMany(targetEntity: Diet::class, mappedBy: "forbiddenFoods")]
    private Collection $forbiddenDiets;

    public function __construct()
    {
        $this->unitMeasures = new ArrayCollection();
        $this->forbiddenDiets = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    #[ORM\PrePersist]
    public function setCreatedValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function setSlugValue(): self
    {
        $slugify = new Slugify();
        $this->slug = $slugify->slugify($this->name);
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getSubFoodGroup(): ?self
    {
        return $this->subFoodGroup;
    }

    public function setSubFoodGroup(?self $subFoodGroup): self
    {
        $this->subFoodGroup = $subFoodGroup;
        return $this;
    }

    public function getIsSubFoodGroup(): ?bool
    {
        return $this->isSubFoodGroup;
    }

    public function setIsSubFoodGroup(bool $isSubFoodGroup): self
    {
        $this->isSubFoodGroup = $isSubFoodGroup;
        return $this;
    }

    public function getFoodGroup(): ?FoodGroup
    {
        return $this->foodGroup;
    }

    public function setFoodGroup(?FoodGroup $foodGroup): self
    {
        $this->foodGroup = $foodGroup;
        return $this;
    }

    public function getEquivalenceReferenceFoodGroup(): ?float
    {
        return $this->equivalenceReferenceFoodGroup;
    }

    public function setEquivalenceReferenceFoodGroup(?float $equivalenceReferenceFoodGroup): self
    {
        $this->equivalenceReferenceFoodGroup = $equivalenceReferenceFoodGroup;
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

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setInfo(?string $info): self
    {
        $this->info = $info;
        return $this;
    }

    public function getMedianWeight(): ?float
    {
        return $this->medianWeight;
    }

    public function setMedianWeight(?float $medianWeight): self
    {
        $this->medianWeight = $medianWeight;
        return $this;
    }

    public function getShowMedianWeight(): ?bool
    {
        return $this->showMedianWeight;
    }

    public function setShowMedianWeight(?bool $showMedianWeight): self
    {
        $this->showMedianWeight = $showMedianWeight;
        return $this;
    }

    public function getHaveGluten(): ?bool
    {
        return $this->haveGluten;
    }

    public function setHaveGluten(bool $haveGluten): self
    {
        $this->haveGluten = $haveGluten;
        return $this;
    }

    public function getHaveLactose(): ?bool
    {
        return $this->haveLactose;
    }

    public function setHaveLactose(bool $haveLactose): self
    {
        $this->haveLactose = $haveLactose;
        return $this;
    }

    public function getNotConsumableRaw(): ?bool
    {
        return $this->notConsumableRaw;
    }

    public function setNotConsumableRaw(?bool $notConsumableRaw): self
    {
        $this->notConsumableRaw = $notConsumableRaw;
        return $this;
    }

    public function getCanBeAPart(): ?bool
    {
        return $this->canBeAPart;
    }

    public function setCanBeAPart(bool $canBeAPart): self
    {
        $this->canBeAPart = $canBeAPart;
        return $this;
    }

    public function getUnitMeasures(): Collection
    {
        return $this->unitMeasures;
    }

    public function addUnitMeasure(UnitMeasure $unitMeasure): self
    {
        if (!$this->unitMeasures->contains($unitMeasure)) {
            $this->unitMeasures->add($unitMeasure);
        }
        return $this;
    }

    public function removeUnitMeasure(UnitMeasure $unitMeasure): self
    {
        $this->unitMeasures->removeElement($unitMeasure);
        return $this;
    }

    public function getEnergy(): ?float
    {
        return $this->nutritionalTable?->getEnergy();
    }

    public function getLipid(): ?float
    {
        return $this->nutritionalTable?->getLipid();
    }

    public function getCarbohydrate(): ?float
    {
        return $this->nutritionalTable?->getCarbohydrate();
    }

    public function getProtein(): ?float
    {
        return $this->nutritionalTable?->getProtein();
    }

    public function getSodium(): ?float
    {
        return $this->nutritionalTable?->getSalt();
    }

    public function getNutritionalTable(): ?NutritionalTable
    {
        return $this->nutritionalTable;
    }

    public function setNutritionalTable(?NutritionalTable $nutritionalTable): self
    {
        $this->nutritionalTable = $nutritionalTable;
        return $this;
    }

    public function getForbiddenDiets(): Collection
    {
        return $this->forbiddenDiets;
    }

    public function addForbiddenDiet(Diet $diet): self
    {
        if (!$this->forbiddenDiets->contains($diet)) {
            $this->forbiddenDiets->add($diet);
            $diet->addForbiddenFood($this);
        }
        return $this;
    }

    public function removeForbiddenDiet(Diet $diet): self
    {
        if ($this->forbiddenDiets->removeElement($diet)) {
            $diet->removeForbiddenFood($this);
        }
        return $this;
    }

    public function getPicturePath(): ?string
    {
        return $this->picture ? UploaderHelper::FOOD . '/' . $this->picture : null;
    }

    #[Groups(["searchable"])]
    public function getAbsolutePicturePath(): ?string
    {
        $path = $this->getPicturePath();
        return $path ? 'https://localhost:8000/uploads/' . $path : null;
    }

    #[Groups(["searchable"])]
    public function getSubFoodGroupName(): ?string
    {
        return $this->subFoodGroup?->getName();
    }

    #[Groups(["searchable"])]
    public function getFoodGroupName(): ?string
    {
        return $this->foodGroup?->getName();
    }
}
