<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\AgeRangeRepository")]
#[ORM\Table(name: "age_range")]
class AgeRange
{
    const LESS_EIGHTEEN = '0_18';
    const NINETEEN_THIRTY_THREE = '19_33';
    const THIRTY_FOUR_FORTY_THREE = '34_43';
    const FORTY_FOUR_FIFTY_THREE = '44_53';
    const FIFTY_FOUR_SIXTY_THREE = '54_63';
    const SIXTY_FOUR_SEVENTY_THREE = '64_73';
    const SEVENTY_FOUR_EIGHTY_THREE = '74_83';
    const HEIGTY_FOUR_NINETY_THREE = '84_94';
    const MORE_NINETY_FOUR = '94_10000';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "description", type: "string", unique: true)]
    private ?string $description = null;

    #[ORM\Column(name: "code", type: "string", unique: true, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(name: "age_min", type: "integer", nullable: false)]
    private ?int $ageMin = null;

    #[ORM\Column(name: "age_max", type: "integer", nullable: false)]
    private ?int $ageMax = null;

    #[Assert\LessThanOrEqual(
        value: 1, 
        message: "La valeur saisie {{ value }} doit être inférieur {{ compared_value_type }}"
    )]
    #[ORM\Column(name: "coeff_energy", type: "float", nullable: false)]
    private ?float $coeffEnergy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->description ?? '';
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

    public function setAgeMin(int $ageMin): self
    {
        $this->ageMin = $ageMin;
        return $this;
    }

    public function getAgeMin(): ?int
    {
        return $this->ageMin;
    }

    public function setAgeMax(int $ageMax): self
    {
        $this->ageMax = $ageMax;
        return $this;
    }

    public function getAgeMax(): ?int
    {
        return $this->ageMax;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCoeffEnergy(float $coeffEnergy): self
    {
        $this->coeffEnergy = $coeffEnergy;
        return $this;
    }

    public function getCoeffEnergy(): ?float
    {
        return $this->coeffEnergy;
    }
}
