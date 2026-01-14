<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AgeRangeGender
 *
 * @ORM\Table(name="age_range_gender")
 * @ORM\Entity(repositoryClass="App\Repository\AgeRangeGenderRepository")
 */
class AgeRangeGender
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
     * @var int
     *
     * @ORM\Column(name="description", type="string", unique=true)
     */
    private $description;

    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\AgeRange")
     */
    private $ageRange;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Gender")
     * @ORM\JoinColumn(nullable=false)
     */
    private $gender;

    public function __construct(AgeRange $ageRange, Gender $gender, $description)
    {
        $this->ageRange = $ageRange;
        $this->gender = $gender;
        $this->description = $description;
    }

    public function __toString()
    {
        if(is_null($this->description)) {
            return 'NULL';
        }
        return $this->description;
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
     * Set description
     *
     * @param string $description
     *
     * @return AgeAndGenderCode
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set gender
     *
     * @param \App\Entity\Gender $gender
     *
     * @return AgeAndGenderCode
     */
    public function setGender(\App\Entity\Gender $gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return \App\Entity\Gender
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set ageRange
     *
     * @param \App\Entity\AgeRange $ageRange
     *
     * @return AgeAndGenderCode
     */
    public function setAgeRange(\App\Entity\AgeRange $ageRange = null)
    {
        $this->ageRange = $ageRange;

        return $this;
    }

    /**
     * Get ageRange
     *
     * @return \App\Entity\AgeRange
     */
    public function getAgeRange()
    {
        return $this->ageRange;
    }
}
