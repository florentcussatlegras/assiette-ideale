<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "type_meal")]
#[ORM\HasLifecycleCallbacks]
class TypeMeal
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "back_name", type: "string", length: 255, unique: true)]
    private ?string $backName = null;

    #[ORM\Column(name: "front_name", type: "string", length: 255, unique: true)]
    private ?string $frontName = null;

    #[ORM\Column(name: "short_cut", type: "string", length: 255, unique: true, nullable: true)]
    private ?string $shortCut = null;

    #[ORM\Column(name: "ranking", type: "integer", nullable: true)]
    private ?int $ranking = null;

    #[ORM\Column(name: "is_snack", type: "boolean")]
    private ?bool $isSnack = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBackName(): ?string
    {
        return $this->backName;
    }

    public function setBackName(string $backName): self
    {
        $this->backName = $backName;
        return $this;
    }

    public function getFrontName(): ?string
    {
        return $this->frontName;
    }

    public function setFrontName(string $frontName): self
    {
        $this->frontName = $frontName;
        return $this;
    }

    public function getIsSnack(): ?bool
    {
        return $this->isSnack;
    }

    public function setIsSnack(bool $isSnack): self
    {
        $this->isSnack = $isSnack;
        return $this;
    }

    public function getShortCut(): ?string
    {
        return $this->shortCut;
    }

    public function setShortCut(?string $shortCut): self
    {
        $this->shortCut = $shortCut;
        return $this;
    }

    public function getRanking(): ?int
    {
        return $this->ranking;
    }

    public function setRanking(?int $ranking): self
    {
        $this->ranking = $ranking;
        return $this;
    }
}
