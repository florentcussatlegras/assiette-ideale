<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WorkingTypeRepository")
 */
class WorkingType
{
    const SOFT = false;

    const HARD = true;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    private $description;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isHard;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\PhysicalActivity", mappedBy="workingType")
     * @Ignore()
     */
    private $physicalActivities;

    public function __construct()
    {
        $this->physicalActivities = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->description;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsHard(): ?bool
    {
        return $this->isHard;
    }

    public function setIsHard(bool $isHard): self
    {
        $this->isHard = $isHard;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|PhysicalActivity[]
     */
    public function getPhysicalActivities(): Collection
    {
        return $this->physicalActivities;
    }

    public function addPhysicalActivity(PhysicalActivity $physicalActivity): self
    {
        if (!$this->physicalActivities->contains($physicalActivity)) {
            $this->physicalActivities[] = $physicalActivity;
            $physicalActivity->setWorkingType($this);
        }

        return $this;
    }

    public function removePhysicalActivity(PhysicalActivity $physicalActivity): self
    {
        if ($this->physicalActivities->removeElement($physicalActivity)) {
            // set the owning side to null (unless already changed)
            if ($physicalActivity->getWorkingType() === $this) {
                $physicalActivity->setWorkingType(null);
            }
        }

        return $this;
    }
}
