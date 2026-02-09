<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\NutritionalTableRepository")]
#[ORM\Table(name: "nutritional_table")]
class NutritionalTable
{
    public const NUTRIENTS_TYPE_COLORS = [
        'protein' => '#c11200',
        'lipid' => '#dbd77d',
        'carbohydrate' => '#697882',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "float", nullable: true)]
    #[Groups('group_chart')]
    private ?float $protein = null;

    #[ORM\Column(type: "float", nullable: true)]
    #[Groups('group_chart')]
    private ?float $lipid = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $saturatedFattyAcid = null;

    #[ORM\Column(type: "float", nullable: true)]
    #[Groups('group_chart')]
    private ?float $carbohydrate = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $sugar = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $salt = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $fiber = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $energy = null;

    #[ORM\Column(type: "string", length: 1)]
    private string $nutriscore;

    // ------------------- Getters & Setters -------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProtein(): ?float
    {
        return $this->protein;
    }

    public function setProtein(?float $protein): self
    {
        $this->protein = $protein;
        return $this;
    }

    public function getLipid(): ?float
    {
        return $this->lipid;
    }

    public function setLipid(?float $lipid): self
    {
        $this->lipid = $lipid;
        return $this;
    }

    public function getSaturatedFattyAcid(): ?float
    {
        return $this->saturatedFattyAcid;
    }

    public function setSaturatedFattyAcid(?float $saturatedFattyAcid): self
    {
        $this->saturatedFattyAcid = $saturatedFattyAcid;
        return $this;
    }

    public function getCarbohydrate(): ?float
    {
        return $this->carbohydrate;
    }

    public function setCarbohydrate(?float $carbohydrate): self
    {
        $this->carbohydrate = $carbohydrate;
        return $this;
    }

    public function getSugar(): ?float
    {
        return $this->sugar;
    }

    public function setSugar(?float $sugar): self
    {
        $this->sugar = $sugar;
        return $this;
    }

    public function getSalt(): ?float
    {
        return $this->salt;
    }

    public function setSalt(?float $salt): self
    {
        $this->salt = $salt;
        return $this;
    }

    public function getFiber(): ?float
    {
        return $this->fiber;
    }

    public function setFiber(?float $fiber): self
    {
        $this->fiber = $fiber;
        return $this;
    }

    public function getEnergy(): ?float
    {
        return $this->energy;
    }

    public function setEnergy(?float $energy): self
    {
        $this->energy = $energy;
        return $this;
    }

    public function getNutriscore(): string
    {
        return $this->nutriscore;
    }

    public function setNutriscore(string $nutriscore): self
    {
        $this->nutriscore = $nutriscore;
        return $this;
    }
}
