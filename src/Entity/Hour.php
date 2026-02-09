<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "hour")]
#[ORM\Entity]
class Hour
{
    public const NORMAL = 'NORMAL_H';
    public const STAGGERED = 'STAGGERED_H';
    public const HALF_TIME = 'HALF_TIME_H';
    public const NONE = 'NONE_H';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private $id;

    #[ORM\Column(name: "title", type: "string", length: 255, unique: true, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(name: "details", type: "string", length: 255, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(name: "alias", type: "string", length: 255, unique: true)]
    private string $alias;

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

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
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
