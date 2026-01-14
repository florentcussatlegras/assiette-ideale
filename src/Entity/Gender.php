<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Gender
 *
 * @ORM\Table(name="gender")
 * @ORM\Entity
 */
class Gender
{
    public const MALE = "H";
    
    public const FEMALE = "F";
    
    public const OTHER = "A";

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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="long_name", type="string", length=255)
     */
    private $longName;

    /**
     * @var string
     *
     * @ORM\Column(name="alias", type="string", length=255, nullable=true)
     */
    private $alias;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Gender
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get longName
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set longName
     *
     * @param string $longName
     *
     * @return Gender
     */
    public function setLongname($longName)
    {
        $this->longName = $longName;

        return $this;
    }

    /**
     * Get longName
     *
     * @return string
     */
    public function getLongName()
    {
        return $this->longName;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }
}
