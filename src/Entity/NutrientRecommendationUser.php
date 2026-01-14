<?php

namespace App\Entity;

use App\Repository\NutrientRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * NutrientRecommendationsUser
 *
 * @ORM\Table(name="nutrient_recommendation_user")
 * @ORM\Entity(repositoryClass="App\Repository\NutrientRecommendationUserRepository")
 */
class NutrientRecommendationUser
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $user;

    /**
     * @var Nutrient
     *
     * @ORM\ManyToOne(targetEntity=Nutrient::class)
     */
    private $nutrient;

    /**
     * @var RecommendedQuantity
     *
     * @ORM\Column(name="recommended_quantity", type="float")
     */
    private $recommendedQuantity;

    public function __toString()
    {
        return self::class;
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
