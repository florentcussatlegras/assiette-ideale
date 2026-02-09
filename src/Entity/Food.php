<?php

namespace App\Entity;

use App\Entity\Diet\Diet;
use App\Entity\FoodGroup\FoodGroup;
use App\Entity\UnitMeasure;
use App\Service\UploaderHelper;
use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

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
    private \DateTime $createdAt;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: self::class, cascade: ["persist", "remove"])]
    #[ORM\JoinColumn(name: "sub_food_group_id", nullable: true)]
    private ?self $subFoodGroup = null;

    #[ORM\Column(type: "boolean")]
    private bool $isSubFoodGroup = false;

    #[ORM\ManyToOne(targetEntity: FoodGroup::class, cascade: ["persist", "remove"])]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private FoodGroup $foodGroup;

    #[ORM\Column(type: "float", nullable: true)]
    #[Assert\Type(type: "float", message: "{{ value }} n'est pas un nombre valide")]
    private ?float $equivalenceReferenceFoodGroup = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $picture = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $info = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $medianWeight = null;

    #[ORM\Column(type: "boolean", nullable: true)]
    private ?bool $showMedianWeight = null;

    #[ORM\ManyToMany(targetEntity: UnitMeasure::class, cascade: ["persist"])]
    private Collection $unitMeasures;

    #[ORM\Column(type: "boolean")]
    private bool $haveGluten = false;

    #[ORM\Column(type: "boolean")]
    private bool $haveLactose = false;

    #[ORM\Column(type: "boolean")]
    private bool $notConsumableRaw = false;

    #[ORM\Column(type: "boolean", name: 'can_be_a_part')]
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
        return $this->name ?? '';
    }

    #[ORM\PrePersist]
    public function setCreatedValue(): void
    {
        $this->createdAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setSlugValue(): void
    {
        $slugify = new Slugify();
        $this->slug = $slugify->slugify($this->name);
    }

    // --------------------- Getters & Setters ---------------------

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

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
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

    public function getIsSubFoodGroup(): bool
    {
        return $this->isSubFoodGroup;
    }

    public function setIsSubFoodGroup(bool $isSubFoodGroup): self
    {
        $this->isSubFoodGroup = $isSubFoodGroup;
        return $this;
    }

    public function getFoodGroup(): FoodGroup
    {
        return $this->foodGroup;
    }

    public function setFoodGroup(FoodGroup $foodGroup): self
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

    public function getPicturePath(): ?string
    {
        return $this->picture ? UploaderHelper::FOOD . '/' . $this->picture : null;
    }

    public function getAbsolutePicturePath(): ?string
    {
        return $this->getPicturePath() ? 'https://localhost:8000/uploads/' . $this->getPicturePath() : null;
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

    public function getHaveGluten(): bool
    {
        return $this->haveGluten;
    }

    public function setHaveGluten(bool $haveGluten): self
    {
        $this->haveGluten = $haveGluten;
        return $this;
    }

    public function getHaveLactose(): bool
    {
        return $this->haveLactose;
    }

    public function setHaveLactose(bool $haveLactose): self
    {
        $this->haveLactose = $haveLactose;
        return $this;
    }

    public function getNotConsumableRaw(): bool
    {
        return $this->notConsumableRaw;
    }

    public function setNotConsumableRaw(bool $notConsumableRaw): self
    {
        $this->notConsumableRaw = $notConsumableRaw;
        return $this;
    }

    public function getCanBeAPart(): bool
    {
        return $this->canBeAPart;
    }

    public function setCanBeAPart(bool $canBeAPart): self
    {
        $this->canBeAPart = $canBeAPart;
        return $this;
    }

    // Nutritional values (delegated)
    public function getEnergy(): ?float
    {
        return $this->nutritionalTable?->getEnergy() ?? $this->energy;
    }

    public function setEnergy(?float $energy): self
    {
        $this->energy = $energy;
        return $this;
    }

    public function getLipid(): ?float
    {
        return $this->nutritionalTable?->getLipid() ?? $this->lipid;
    }

    public function setLipid(?float $lipid): self
    {
        $this->lipid = $lipid;
        return $this;
    }

    public function getCarbohydrate(): ?float
    {
        return $this->nutritionalTable?->getCarbohydrate() ?? $this->carbohydrate;
    }

    public function setCarbohydrate(?float $carbohydrate): self
    {
        $this->carbohydrate = $carbohydrate;
        return $this;
    }

    public function getProtein(): ?float
    {
        return $this->nutritionalTable?->getProtein() ?? $this->protein;
    }

    public function setProtein(?float $protein): self
    {
        $this->protein = $protein;
        return $this;
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

    // ---------------- Forbidden Diets ----------------
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
}
