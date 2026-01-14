<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * UnitMeasure
 *
 * @ORM\Table(name="unit_measure")
 * @ORM\Entity(repositoryClass="App\Repository\UnitMeasureRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity("alias")
 */
// class UnitMeasure implements UnitMeasureInterface
class UnitMeasure
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
	 * @ORM\Column(name="name", type="string", length=255)
	 */
	private $name;

    /**
     * @ORM\Column(name="alias", type="string", length=10)
     */
    private $alias;

    /**
     * @ORM\Column(name="gram_ratio", type="float", nullable=true)
     */
    private $gramRatio;

    /**
     * @ORM\Column(name="is_unit", type="boolean")
     */
    private $isUnit;
    

    public function __toString()
    {
        if(null === $this->name)
            return 'NULL';

        return $this->name;
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

    /**
     * Set name
     *
     * @param string $name
     *
     * @return UnitMeasure
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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

    public function getGramRatio(): ?float
    {
        return $this->gramRatio;
    }

    public function setGramRatio(?float $gramRatio): self
    {
        $this->gramRatio = $gramRatio;

        return $this;
    }

    public function isIsUnit(): ?bool
    {
        return $this->isUnit;
    }

    public function setIsUnit(bool $isUnit): static
    {
        $this->isUnit = $isUnit;

        return $this;
    }

   
}
