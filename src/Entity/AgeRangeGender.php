<?php

namespace App\Entity;

use App\Repository\AgeRangeGenderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgeRangeGenderRepository::class)]
#[ORM\Table(name: "age_range_gender")]
class AgeRangeGender
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", unique: true)]
    private $description;

    #[ORM\ManyToOne(targetEntity: AgeRange::class)]
    private $ageRange;

    #[ORM\ManyToOne(targetEntity: Gender::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $gender;

    public function __construct(AgeRange $ageRange, Gender $gender, $description)
    {
        $this->ageRange = $ageRange;
        $this->gender = $gender;
        $this->description = $description;
    }

    public function __toString()
    {
        if (is_null($this->description)) {
            return 'NULL';
        }

        return $this->description;
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

    public function setGender(Gender $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setAgeRange(?AgeRange $ageRange): self
    {
        $this->ageRange = $ageRange;

        return $this;
    }

    public function getAgeRange(): ?AgeRange
    {
        return $this->ageRange;
    }
}
