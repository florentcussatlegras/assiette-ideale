<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "hour")]
class Hour
{
    public const NORMAL = 'NORMAL_H';
    public const STAGGERED = 'STAGGERED_H';
    public const HALF_TIME = 'HALF_TIME_H';
    public const NONE = 'NONE_H';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255, unique: true, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private ?string $alias = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(string $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }
}
