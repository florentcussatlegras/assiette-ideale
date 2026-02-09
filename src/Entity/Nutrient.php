<?php

namespace App\Entity;

use App\Repository\NutrientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NutrientRepository::class)]
#[ORM\Table(name: "nutrient")]
class Nutrient
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer", name: "id")]
    private ?int $id = null;

    #[ORM\Column(type: "string", name: "name")]
    private ?string $name = null;

    #[ORM\Column(type: "string", name: "code")]
    private ?string $code = null;

    #[ORM\Column(type: "string", name: "color")]
    private ?string $color = null;

    #[ORM\Column(type: "text", name: "description")]
    private ?string $description = null;

    #[ORM\Column(type: "integer", name: "order")]
    private ?int $order = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(int $order): static
    {
        $this->order = $order;
        return $this;
    }
}
