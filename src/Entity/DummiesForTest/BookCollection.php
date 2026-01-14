<?php

namespace App\Entity\DummiesForTest;

use App\Entity\DummiesForTest\Book;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 */
class BookCollection
{
     /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    
    /**
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank(message="Le nom ne doit pas être vide.")
     * @Assert\Length(min=3)
     */
    protected $name;

    /**
     * @var Collection|Book[]
     * 
     * @ORM\ManyToMany(targetEntity="App\Entity\DummiesForTest\Book", cascade={"persist"})
     * @Assert\Valid
     */
    protected $books;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return \Generator|Book[] The books for a givan author
     */
    public function getBooksForAuthor(Author $author): iterable
    {
        foreach($this->books as $book) {
            if($book->isAuthoredBy($author)) {
                yield $book;
            }
        }
    }

    public function getBooks()
    {
        return $this->books;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function addBook(Book $book): self
    {
        if (!$this->books->contains($book)) {
            $this->books[] = $book;
        }

        return $this;
    }

    public function removeBook(Book $book): self
    {
        $this->books->removeElement($book);

        return $this;
    }
}