<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: "physical_activity")]
class PhysicalActivity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkingType::class, inversedBy: "physicalActivities", cascade: ["persist"])]
    #[Assert\NotBlank(message: "Veuillez cocher la difficulté de votre métier", groups: ["profile_life"])]
    private ?WorkingType $workingType = null;

    #[ORM\ManyToOne(targetEntity: SportingTime::class, inversedBy: "physicalActivities", cascade: ["persist"])]
    #[Assert\NotBlank(message: "Veuillez choisir une activité sportive", groups: ["profile_life"])]
    private ?SportingTime $sportingTime = null;

    #[ORM\Column(name: "value", type: "string", length: 255)]
    private string $value;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getWorkingType(): ?WorkingType
    {
        return $this->workingType;
    }

    public function setWorkingType(?WorkingType $workingType): self
    {
        $this->workingType = $workingType;
        return $this;
    }

    public function getSportingTime(): ?SportingTime
    {
        return $this->sportingTime;
    }

    public function setSportingTime(?SportingTime $sportingTime): self
    {
        $this->sportingTime = $sportingTime;
        return $this;
    }
}
