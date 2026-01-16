<?php

namespace App\Entity;

use App\Service\TypeDishHandler;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\NutritionalTable;
use App\Entity\DishFoodGroupParent;
use App\Entity\FoodGroup\FoodGroup;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: "App\Repository\DishRepository")]
#[ORM\Table(name: "dish")]
#[UniqueEntity("slug")]
#[ORM\HasLifecycleCallbacks]
class Dish implements NormalizableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Cette valeur ne doit pas être vide.", groups: ["AddOrEdit"])]
    private string $name;

    #[ORM\Column(type: "string", length: 255, options: ["default" => "-"])]
    private string $slug = '-';

    #[ORM\Column(type: "integer", nullable: true)]
    #[Assert\NotBlank(groups: ["AddOrEdit"])]
    #[Assert\Positive(groups: ["AddOrEdit"])]
    #[Assert\LessThan(25, groups: ["AddOrEdit"])]
    private ?int $lengthPersonForRecipe = null;

    #[ORM\Column(type: "string", nullable: true)]
    #[Assert\Choice(callback: [TypeDishHandler::class, "getChoices"])]
    private ?string $level = null;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: "dish", targetEntity: DishFood::class, cascade: ["persist", "remove"])]
    private Collection $dishFoods;

    #[ORM\OneToMany(mappedBy: "dish", targetEntity: DishFoodGroup::class, cascade: ["persist", "remove"])]
    private Collection $dishFoodGroups;

    #[ORM\OneToMany(mappedBy: "dish", targetEntity: DishFoodGroupParent::class, cascade: ["persist", "remove"])]
    private Collection $dishFoodGroupParents;

    #[ORM\ManyToMany(targetEntity: Spice::class, cascade: ["persist"])]
    private Collection $spices;

    #[ORM\OneToMany(mappedBy: "dish", targetEntity: StepRecipe::class, cascade: ["persist", "remove"])]
    private Collection $stepRecipes;

    #[ORM\ManyToOne(targetEntity: FoodGroup::class)]
    private ?FoodGroup $principalFoodGroup = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $rankView = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $preparationTime = null;

    #[ORM\ManyToOne(targetEntity: UnitTime::class)]
    private ?UnitTime $preparationTimeUnitTime = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $cookingTime = null;

    #[ORM\ManyToOne(targetEntity: UnitTime::class)]
    private ?UnitTime $cookingTimeUnitTime = null;

    #[ORM\Column(type: "boolean", nullable: true)]
    private ?bool $isDessert = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $picture = null;

    #[ORM\OneToOne(targetEntity: NutritionalTable::class, cascade: ["persist"])]
    private ?NutritionalTable $nutritionalTable = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: "boolean", nullable: true)]
    private ?bool $haveGluten = null;

    #[ORM\Column(type: "boolean", nullable: true)]
    private ?bool $haveLactose = null;

    public function __construct()
    {
        $this->dishFoods = new ArrayCollection();
        $this->dishFoodGroups = new ArrayCollection();
        $this->dishFoodGroupParents = new ArrayCollection();
        $this->spices = new ArrayCollection();
        $this->stepRecipes = new ArrayCollection();
    }

    public function normalize(NormalizerInterface $serializer, $format = null, array $context = []): array
    {
        return [
            'name' => $this->getName(),
            'user' => $serializer->normalize($this->getUser(), $format, $context),
        ];
    }

    // --- Getters / Setters simplifiés avec typage ---
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): self { $this->slug = $slug; return $this; }
    public function computeSlug(SluggerInterface $slugger): self
    {
        if (!$this->slug || $this->slug === '-') {
            $this->slug = (string) $slugger->slug((string)$this)->lower();
        }
        return $this;
    }

    public function getLengthPersonForRecipe(): ?int { return $this->lengthPersonForRecipe; }
    public function setLengthPersonForRecipe(?int $length): self { $this->lengthPersonForRecipe = $length; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    // --- Méthodes utiles pour Collections ---
    public function getDishFoods(): Collection { return $this->dishFoods; }
    public function addDishFood(DishFood $dishFood): self { $this->dishFoods->add($dishFood); $dishFood->setDish($this); return $this; }
    public function removeDishFood(DishFood $dishFood): self { $this->dishFoods->removeElement($dishFood); return $this; }

    public function getDishFoodGroups(): Collection { return $this->dishFoodGroups; }
    public function addDishFoodGroup(DishFoodGroup $dishFoodGroup): self { $this->dishFoodGroups->add($dishFoodGroup); $dishFoodGroup->setDish($this); return $this; }
    public function removeDishFoodGroup(DishFoodGroup $dishFoodGroup): self { $this->dishFoodGroups->removeElement($dishFoodGroup); return $this; }

    public function getDishFoodGroupParents(): Collection { return $this->dishFoodGroupParents; }
    public function addDishFoodGroupParent(DishFoodGroupParent $parent): self { if (!$this->dishFoodGroupParents->contains($parent)) { $this->dishFoodGroupParents->add($parent); $parent->setDish($this); } return $this; }
    public function removeDishFoodGroupParent(DishFoodGroupParent $parent): self { $this->dishFoodGroupParents->removeElement($parent); return $this; }

    public function getSpices(): Collection { return $this->spices; }
    public function addSpice(Spice $spice): self { $this->spices->add($spice); return $this; }
    public function removeSpice(Spice $spice): self { $this->spices->removeElement($spice); return $this; }

    public function __toString(): string { return $this->name; }
}
