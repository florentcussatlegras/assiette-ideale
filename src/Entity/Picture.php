<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use App\Service\UploaderHelper;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 */
class Picture
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"show_dish", "list_dish"})
     */
    private $name;

    private $file;

    /**
     * @ORM\ManyToOne(targetEntity=Dish::class, inversedBy="pictures")
     * @ORM\JoinColumn(nullable=true)
     */
    private $dish;

    public function __toString(): string
    {
        return $this->getPath().'/'.$this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile(File $file): void
    {
        $this->file = $file;
    }

    public function getDish(): ?Dish
    {
        return $this->dish;
    }

    public function setDish(?Dish $dish): self
    {
        $this->dish = $dish;

        return $this;
    }

    /**
     * @Groups({"show_dish", "list_dish"})
     */
    public function getPath()
    {
        return UploaderHelper::DISH.'/'.$this->getName();
    }
}
