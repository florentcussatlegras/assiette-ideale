<?php

namespace App\Entity;

use App\Repository\SportingTimeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SportingTimeRepository::class)]
class SportingTime
{
    const NO_SPORT = 'NO_SPORT';
    const LESS_5_H = 'LESS_5_H';
    const MORE_5_H = 'MORE_5_H';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "description", type: "string", length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $duration = null;

    #[ORM\OneToMany(targetEntity: "App\Entity\PhysicalActivity", mappedBy: "sportingTime")]
    private Collection $physicalActivities;

    public function __construct()
    {
        $this->physicalActivities = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->description ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration = null): self
    {
        $this->duration = $duration;
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
            $physicalActivity->setSportingTime($this);
        }

        return $this;
    }

    public function removePhysicalActivity(PhysicalActivity $physicalActivity): self
    {
        if ($this->physicalActivities->removeElement($physicalActivity)) {
            if ($physicalActivity->getSportingTime() === $this) {
                $physicalActivity->setSportingTime(null);
            }
        }

        return $this;
    }
}
