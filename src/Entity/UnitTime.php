<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UnitTime
 *
 * @ORM\Table(name="unit_time")
 * @ORM\Entity(repositoryClass="App\Repository\UnitTimeRepository")
 * @ORM\Entity
 */
class UnitTime
{
	/**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="string")
     */
    private $text;
    
    /**
     * @var string
     *
     * @ORM\Column(name="alias", type="string")
     */
    private $alias;

    public function __toString()
    {
        return $this->text;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

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
