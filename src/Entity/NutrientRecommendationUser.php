<?php

namespace App\Entity;

use App\Repository\NutrientRecommendationUserRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Nutrient;

#[ORM\Entity(repositoryClass: NutrientRecommendationUserRepository::class)]
#[ORM\Table(name: "nutrient_recommendation_user")]
class NutrientRecommendationUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Nutrient::class)]
    private ?Nutrient $nutrient = null;

    #[ORM\Column(name: "recommended_quantity", type: "float")]
    private ?float $recommendedQuantity = null;

    public function __toString(): string
    {
        return self::class;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecommendedQuantity(): ?float
    {
        return $this->recommendedQuantity;
    }

    public function setRecommendedQuantity(float $recommendedQuantity): static
    {
        $this->recommendedQuantity = $recommendedQuantity;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getNutrient(): ?Nutrient
    {
        return $this->nutrient;
    }

    public function setNutrient(?Nutrient $nutrient): static
    {
        $this->nutrient = $nutrient;

        return $this;
    }
}
