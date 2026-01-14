<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Cocur\Slugify\Slugify;

/**
 * TypeMeal
 *
 * @ORM\Table(name="type_meal")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class TypeMeal
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
     * @ORM\Column(name="back_name", type="string", length=255, unique=true)
     */
    private $backName;

    /**
     * @var string
     *
     * @ORM\Column(name="front_name", type="string", length=255, unique=true)
     */
    private $frontName;

    /**
     * @var string
     *
     * @ORM\Column(name="short_cut", type="string", length=255, unique=true, nullable=true)
     */
    private $shortCut;

    /**
     * @var string
     *
     * @ORM\Column(name="ranking", type="integer", nullable=true)
     */
    private $ranking;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_snack", type="boolean")
     */
    private $isSnack;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getBackName(): ?string
    {
        return $this->backName;
    }

    public function setBackName(string $backName): self
    {
        $this->backName = $backName;

        return $this;
    }

    public function getFrontName(): ?string
    {
        return $this->frontName;
    }

    public function setFrontName(string $frontName): self
    {
        $this->frontName = $frontName;

        return $this;
    }

    public function getIsSnack(): ?bool
    {
        return $this->isSnack;
    }

    public function setIsSnack(bool $isSnack): self
    {
        $this->isSnack = $isSnack;

        return $this;
    }

    public function getShortCut(): ?string
    {
        return $this->shortCut;
    }

    public function setShortCut(?string $shortCut): self
    {
        $this->shortCut = $shortCut;

        return $this;
    }

    public function getRanking(): ?int
    {
        return $this->ranking;
    }

    public function setRanking(?int $ranking): self
    {
        $this->ranking = $ranking;

        return $this;
    }
}
