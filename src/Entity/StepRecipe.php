<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * StepRecipe
 *
 * @ORM\Table(name="step_recipe")
 * @ORM\Entity(repositoryClass="App\Repository\StepRecipeRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class StepRecipe
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
     * @ORM\Column(name="rank_step", type="integer", nullable=true)
     * @Assert\Positive(message="Le numéro de l'étape doit être positif", groups={"Default", "AddOrEdit"})
     */
    private $rankStep;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     * @Assert\NotNull(message="Merci de saisir la description de l'étape.", groups={"Default", "AddOrEdit"})
     * @Assert\Length(min=8, minMessage="La description doit contenir au moins {{ limit }} caractères.", groups={"Default", "AddOrEdit"})
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Dish", inversedBy="stepRecipes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $dish;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    // public function __construct($rankStep = 0, $description = 'Lorem ipsum...')
    // {
    //     $this->rankStep = $rankStep;
    //     $this->description = $description;
    // }

    public function devaluateRankStep()
    {
        $this->rankStep = $this->rankStep - 1;
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
     * @return StepRecipe
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return StepRecipe
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return StepRecipe
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set dish
     *
     * @param \App\Entity\Dish $dish
     *
     * @return StepRecipe
     */
    public function setDish(\App\Entity\Dish $dish)
    {
        $this->dish = $dish;

        return $this;
    }

    /**
     * Get dish
     *
     * @return \App\Entity\Dish
     */
    public function getDish()
    {
        return $this->dish;
    }

    public function getRankStep(): ?int
    {
        return $this->rankStep;
    }

    public function setRankStep(?int $rankStep): self
    {
        $this->rankStep = $rankStep;

        return $this;
    }

    
}
