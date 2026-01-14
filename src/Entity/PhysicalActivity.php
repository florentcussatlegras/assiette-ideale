<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * PhysicalActivity
 *
 * @ORM\Table(name="physical_activity")
 * @ORM\Entity
 */
class PhysicalActivity
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
     * @ORM\ManyToOne(targetEntity="App\Entity\WorkingType", inversedBy="physicalActivities", cascade={"persist"})
     * @Assert\NotBlank(message="Veuillez cocher la difficulté de votre métier", groups={"profile_life"})
     */
    private $workingType;
    
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\SportingTime", inversedBy="physicalActivities", cascade={"persist"})
     * @Assert\NotBlank(message="Veuillez choisir une activité sportive", groups={"profile_life"})
     */
    private $sportingTime;
    
    /**
     * @ORM\Column(name="value", type="string", length=255)
     */
    private $value;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return PhysicalActivity
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getWorkingType(): ?WorkingType
    {
        return $this->workingType;
    }

    public function setWorkingType(?WorkingType $workingType): self
    {
        $this->workingType = $workingType;

        return $this;
    }

    public function getSportingTime(): ?SportingTime
    {
        return $this->sportingTime;
    }

    public function setSportingTime(?SportingTime $sportingTime): self
    {
        $this->sportingTime = $sportingTime;

        return $this;
    }
}
