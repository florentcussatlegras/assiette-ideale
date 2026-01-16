<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "age_range_gender")]
#[ORM\Entity(repositoryClass: "App\Repository\AgeRangeGenderRepository")]
class AgeRangeGender
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", unique: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: AgeRange::class)]
    private ?AgeRange $ageRange = null;

    #[ORM\ManyToOne(targetEntity: Gender::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Gender $gender = null;

    public function __construct(AgeRange $ageRange, Gender $gender, string $description)
    {
        $this->ageRange = $ageRange;
        $this->gender = $gender;
        $this->description = $description;
    }

    public function __toString(): string
    {
        return $this->description ?? 'NULL';
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAgeRange(): ?AgeRange
    {
        return $this->ageRange;
    }

    public function setAgeRange(?AgeRange $ageRange): self
    {
        $this->ageRange = $ageRange;
        return $this;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(Gender $gender): self
    {
        $this->gender = $gender;
        return $this;
    }
}
