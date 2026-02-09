<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\ImcMessageRepository")]
class ImcMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", unique: true)]
    private string $alertCode;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $explanationWithoutDiet = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $explanationWithDiet = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $message = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAlertCode(): string
    {
        return $this->alertCode;
    }

    public function setAlertCode(string $alertCode): self
    {
        $this->alertCode = $alertCode;
        return $this;
    }


    public function getExplanationWithoutDiet(): ?string
    {
        return $this->explanationWithoutDiet;
    }

    public function setExplanationWithoutDiet(?string $text): self
    {
        $this->explanationWithoutDiet = $text;
        return $this;
    }


    public function getExplanationWithDiet(): ?string
    {
        return $this->explanationWithDiet;
    }

    public function setExplanationWithDiet(?string $text): self
    {
        $this->explanationWithDiet = $text;
        return $this;
    }


    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;
        return $this;
    }
}

