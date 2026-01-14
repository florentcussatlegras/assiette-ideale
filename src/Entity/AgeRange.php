<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * AgeRange
 *
 * @ORM\Table(name="age_range")
 * @ORM\Entity(repositoryClass="App\Repository\AgeRangeRepository")
 */
class AgeRange
{
    const LESS_EIGHTEEN = '0_18';

    const NINETEEN_THIRTY_THREE = '19_33';

    const THIRTY_FOUR_FORTY_THREE = '34_43';

    const FORTY_FOUR_FIFTY_THREE = '44_53';

    const FIFTY_FOUR_SIXTY_THREE = '54_63';

    const SIXTY_FOUR_SEVENTY_THREE = '64_73';

    const SEVENTY_FOUR_EIGHTY_THREE = '74_83';

    const HEIGTY_FOUR_NINETY_THREE = '84_94';

    const MORE_NINETY_FOUR = '94_10000';

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
     * @ORM\Column(name="code", type="string", unique=true, nullable=true)
     */
    private $code;

    /**
     * @var int
     *
     * @ORM\Column(name="age_min", type="integer", nullable=false)
     */
    private $ageMin;

    /**
     * @var int
     *
     * @ORM\Column(name="age_max", type="integer", nullable=false)
     */
    private $ageMax;

    /**
     * @var int
     * 
     * @Assert\LessThanOrEqual(value=1, message="La valeur saisie {{ value }} doit être inférieur {{ compared_value_type }}")
     * @ORM\Column(name="coeff_energy", type="float", nullable=false)
     */
    private $coeffEnergy;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->description;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return AgeRange
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
     * Set ageMin
     *
     * @param integer $ageMin
     *
     * @return AgeRange
     */
    public function setAgeMin($ageMin)
    {
        $this->ageMin = $ageMin;

        return $this;
    }

    /**
     * Get ageMin
     *
     * @return integer
     */
    public function getAgeMin()
    {
        return $this->ageMin;
    }

    /**
     * Set ageMax
     *
     * @param integer $ageMax
     *
     * @return AgeRange
     */
    public function setAgeMax($ageMax)
    {
        $this->ageMax = $ageMax;

        return $this;
    }

    /**
     * Get ageMax
     *
     * @return integer
     */
    public function getAgeMax()
    {
        return $this->ageMax;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return AgeRange
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    public function getCoeffEnergy(): ?float
    {
        return $this->coeffEnergy;
    }

    public function setCoeffEnergy(float $coeffEnergy): self
    {
        $this->coeffEnergy = $coeffEnergy;

        return $this;
    }
}
