<?php

namespace App\Entity\DummiesForTest;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 */
class Book
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\DummiesForTest\Author")
     * @Assert\NotEqualTo("Florent")
     * @Assert\NotBlank
     */
    private $author;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     * @Assert\NotBlank(message="Le titre du livre ne doit pas être vide.")
     * @Assert\Length(min=5, minMessage="Le titre {{ value }} du livre doit comporter au moins {{ limit }} caractères.")
     */
    private $title;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function isAuthoredBy(Author $author)
    {
        return $this->getAuthor() === $author;
    }
}