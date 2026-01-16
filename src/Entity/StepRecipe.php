<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: "step_recipe")]
#[ORM\Entity(repositoryClass: "App\Repository\StepRecipeRepository")]
#[ORM\HasLifecycleCallbacks]
class StepRecipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "rank_step", type: "integer", nullable: true)]
    #[Assert\Positive(message: "Le numéro de l'étape doit être positif", groups: ["Default", "AddOrEdit"])]
    private ?int $rankStep = null;

    #[ORM\Column(name: "description", type: "text")]
    #[Assert\NotNull(message: "Merci de saisir la description de l'étape.", groups: ["Default", "AddOrEdit"])]
    #[Assert\Length(
        min: 8, 
        minMessage: "La description doit contenir au moins {{ limit }} caractères.", 
        groups: ["Default", "AddOrEdit"]
    )]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Dish", inversedBy: "stepRecipes")]
    #[ORM\JoinColumn(nullable: false)]
    private $dish;

    #[ORM\Column(name: "created_at", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: "updated_at", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function devaluateRankStep(): void
    {
        $this->rankStep = $this->rankStep - 1;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setDish(\App\Entity\Dish $dish): self
    {
        $this->dish = $dish;

        return $this;
    }

    public function getDish(): ?\App\Entity\Dish
    {
        return $this->dish;
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
}
