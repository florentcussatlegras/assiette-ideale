<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: "App\Repository\StepRecipeRepository")]
#[ORM\Table(name: "step_recipe")]
#[ORM\HasLifecycleCallbacks]
class StepRecipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "rank_step", type: "integer", nullable: true)]
    #[Assert\Positive(message: "Le numéro de l'étape doit être positif", groups: ["Default", "AddOrEdit"])]
    private ?int $rankStep = null;

    #[ORM\Column(type: "text")]
    #[Assert\NotNull(message: "Merci de saisir la description de l'étape.", groups: ["Default", "AddOrEdit"])]
    #[Assert\Length(min: 8, minMessage: "La description doit contenir au moins {{ limit }} caractères.", groups: ["Default", "AddOrEdit"])]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Dish", inversedBy: "stepRecipes")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dish $dish = null;

    #[ORM\Column(name: "created_at", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: "updated_at", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    // ------------------- Constructor -------------------
    public function __construct(?int $rankStep = null, ?string $description = null)
    {
        $this->rankStep = $rankStep;
        $this->description = $description;
    }

    // ------------------- Magic Methods -------------------
    public function __toString(): string
    {
        return $this->description ?? 'Étape sans description';
    }

    // ------------------- Custom Methods -------------------
    public function devaluateRankStep(): void
    {
        if ($this->rankStep !== null) {
            $this->rankStep--;
        }
    }

    // ------------------- Getters & Setters -------------------
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRankStep(): ?int
    {
        return $this->rankStep;
    }

    public function setRankStep(?int $rankStep): self
    {
        $this->rankStep = $rankStep;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDish(): ?Dish
    {
        return $this->dish;
    }

    public function setDish(Dish $dish): self
    {
        $this->dish = $dish;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
