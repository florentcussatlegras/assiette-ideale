<?php

namespace App\Entity;


use App\Service\TypeDishHandler;
use Doctrine\DBAL\Types\Types;
use App\Service\UploaderHelper;
use App\Entity\NutritionalTable;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\DishFoodGroupParent;
use App\Entity\FoodGroup\FoodGroup;
use App\Validator\Constraints as MyAssert;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;

/**
 * Dish
 * @ORM\Table(name="dish")
 * @ORM\Entity(repositoryClass="App\Repository\DishRepository")
 * @UniqueEntity("slug")
 * @ORM\HasLifecycleCallbacks()
 * @Assert\GroupSequence({"AddOrEdit", "Dish", "Step"})
 */
class Dish implements NormalizableInterface
{
    public function normalize(NormalizerInterface $serializer, $format = null, array $context = []): array
    {
        return [
            'name' => $this->getName(),
            'user' => $serializer->normalize($this->getUser(), $format, $context)
        ];
    }

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
     * @Assert\NotBlank(message="Cette valeur ne doit pas être vide.", groups={"AddOrEdit"}))
     * @Groups({"searchable"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255, options={"default" : "-"})
     */
    private $slug;

    /**
     * @var integer
     *
     * @ORM\Column(name="length_person", type="integer", nullable=true)
     * @Assert\NotBlank(message="Cette valeur ne doit pas être vide.", groups={"AddOrEdit"})
     * @Assert\Positive(message="Cette valeur doit être positive", groups={"AddOrEdit"})
     * @Assert\LessThan(25, message="Cette valeur doit être inférieure à {{ compared_value }}", groups={"AddOrEdit"})
     */
    private $lengthPersonForRecipe;

    /**
     * @var integer
     *
     * @ORM\Column(name="level", type="string", nullable=true)
     * @Assert\Choice(callback={"App\Service\RecipeLevel", "getLevels"}, message="La difficulté {{ value }} n'est pas valide, elle doit appartenir à la liste {{ choices }}")
     */
    private $level;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=true)
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DishFood", mappedBy="dish", cascade={"persist", "remove"})
     */
    private $dishFoods;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DishFoodGroup", mappedBy="dish", cascade={"persist", "remove"})
     */
    private $dishFoodGroups;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DishFoodGroupParent", mappedBy="dish", cascade={"persist", "remove"})
     */
    private $dishFoodGroupParents;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Spice", cascade={"persist"})
     */
    private $spices;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\StepRecipe", mappedBy="dish", cascade={"persist", "remove"})
     * @MyAssert\IsStepRecipeUnique(groups={"Step"})
     * @Assert\Valid(groups={"AddOrEdit"})
     */
    private $stepRecipes;

    /**
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\FoodGroup\FoodGroup")
     */
    private $principalFoodGroup;

    /**
     * @var integer
     *
     * @ORM\Column(name="rank_view", type="integer", nullable=true)
     */
    private $rankView;

    /**
     * @var text
     *
     * @ORM\Column(name="preparation_time", type="integer", nullable=true)
     * @Assert\NotBlank(groups={"AddOrEdit"})
     * @Assert\PositiveOrZero(message="Cette valeur doit être positive", groups={"AddOrEdit"})
     * @Assert\LessThan(100, message="Cette valeur doit être inférieure à {{ compared_value }}", groups={"AddOrEdit"})
     */
    private $preparationTime;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UnitTime")
     */
    private $preparationTimeUnitTime;

    /**
     * @var text
     *
     * @ORM\Column(name="cooking_time", type="integer", nullable=true)
     * @Assert\NotBlank(groups={"AddOrEdit"})
     * @Assert\PositiveOrZero(message="Cette valeur doit être positive", groups={"AddOrEdit"})
     * @Assert\LessThan(100, message="Cette valeur doit être inférieure à {{ compared_value }}", groups={"AddOrEdit"})
     */
    private $cookingTime;

    /**
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UnitTime")
     */
    private $cookingTimeUnitTime;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_dessert", type="boolean", nullable=true)
     */
    private $isDessert;

    /**
     * @var string
     * 
      * @ORM\Column(name="picture", type="string", length=255, nullable=true)
     */
    private $picture;

    /**
     * @var NutritionalTable
     *
     * @ORM\OneToOne(targetEntity="App\Entity\NutritionalTable", cascade={"persist"})
     */
    private $nutritionalTable;

    #[Assert\Choice(callback: [TypeDishHandler::class, 'getChoices'])]
    /**
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    private ?string $type = null;

    /**
     * @var bool
     * 
     * @ORM\Column(name="have_gluten", type="boolean", nullable=true)
     */
    private $haveGluten;

    /**
     * @var bool
     * 
     * @ORM\Column(name="have_lactose", type="boolean", nullable=true)
     */
    private $haveLactose;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dishFoods = new \Doctrine\Common\Collections\ArrayCollection();
        $this->dishFoodGroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->spices = new \Doctrine\Common\Collections\ArrayCollection();
        $this->stepRecipes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->foodGroupParents = new ArrayCollection();
        $this->dishFoodGroupParents = new ArrayCollection();
    }

    public function __toString()
    {
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
     * @return DishMeal
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
    public function getName()
    {
        return $this->name;
    }

    // /**
    //  * Set slug
    //  *
    //  * @param string $slug
    //  *
    //  * @return DishMeal
    //  */
    // public function setSlug($slug)
    // {
    //     $this->slug = $slug;
    
    //     return $this;
    // }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

     /**
     * Set slugNameValue
     *
     */
    public function computeSlug(SluggerInterface $slugger)
    {
        if(!$this->slug || '-' === $this->slug) {
            $this->slug = (string) $slugger->slug((string) $this)->lower();
        }

        return $this;
    }

    /**
     * Set lengthPersonForRecipe
     *
     * @param integer $lengthPersonForRecipe
     *
     * @return DishMeal
     */
    public function setLengthPersonForRecipe($lengthPersonForRecipe)
    {
        $this->lengthPersonForRecipe = $lengthPersonForRecipe;
    
        return $this;
    }

    /**
     * Get lengthPersonForRecipe
     *
     * @return integer
     */
    public function getLengthPersonForRecipe()
    {
        return $this->lengthPersonForRecipe;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return DishMeal
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
     * @ORM\PrePersist
     */
    public function setCreatedAtValue()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAtValue()
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return DishMeal
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
     * Set user
     *
     * @param \App\Entity\User $user
     *
     * @return DishMeal
     */
    public function setUser(\App\Entity\User $user = null)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \App\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Add dishFoodGroup
     *
     * @param \App\Entity\DishFoodGroup $dishFoodGroup
     *
     * @return DishMeal
     */
    public function addDishFoodGroup(\App\Entity\DishFoodGroup $dishFoodGroup)
    {
        $this->dishFoodGroups[] = $dishFoodGroup;

        $dishFoodGroup->setDish($this);
    
        return $this;
    }

    /**
     * Remove dishFoodGroup
     *
     * @param \App\Entity\DishFoodGroup $dishFoodGroup
     */
    public function removeDishFoodGroup(\App\Entity\DishFoodGroup $dishFoodGroup)
    {
        $this->dishFoodGroups->removeElement($dishFoodGroup);
    }

    /**
     * Get dishFoodGroups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDishFoodGroups()
    {
        return $this->dishFoodGroups;
    }

    public function clearDishFoodGroups()
    {
        $this->dishFoodGroups->clear();
    }

    /**
     * Add spice
     *
     * @param \App\Entity\Spice $spice
     *
     * @return DishMeal
     */
    public function addSpice(\App\Entity\Spice $spice)
    {
        $this->spices[] = $spice;
    
        return $this;
    }

    /**
     * Remove spice
     *
     * @param \App\Entity\Spice $spice
     */
    public function removeSpice(\App\Entity\Spice $spice)
    {
        $this->spices->removeElement($spice);
    }

    /**
     * Get spices
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSpices()
    {
        return $this->spices;
    } 

    /**
     * Add stepRecipe
     *
     * @param \App\Entity\StepRecipe $stepRecipe
     *
     * @return DishMeal
     */
    public function addStepRecipe(\App\Entity\StepRecipe $stepRecipe)
    {
        $this->stepRecipes[] = $stepRecipe;
        $stepRecipe->setDish($this);
    
        return $this;
    }

    /**
     * Remove stepRecipe
     *
     * @param \App\Entity\StepRecipe $stepRecipe
     */
    public function removeStepRecipe(\App\Entity\StepRecipe $stepRecipe)
    {
        $this->stepRecipes->removeElement($stepRecipe);
    }

    /**
     * Get stepRecipes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStepRecipes()
    {
        return $this->stepRecipes;
    }

    /**
     * Set principalFoodGroup
     *
     * @param \App\Entity\FoodGroup $principalFoodGroup
     *
     * @return DishMeal
     */
    public function setPrincipalFoodGroup(\App\Entity\FoodGroup\FoodGroup $principalFoodGroup = null)
    {
        $this->principalFoodGroup = $principalFoodGroup;
    
        return $this;
    }

    /**
     * Get principalFoodGroup
     *
     * @return \App\Entity\FoodGroup
     */
    public function getPrincipalFoodGroup()
    {
        return $this->principalFoodGroup;
    }

    /**
     * Add dishFood
     *
     * @param \App\Entity\DishFood $dishFood
     *
     * @return Dish
     */
    public function addDishFood(\App\Entity\DishFood $dishFood)
    {
        $this->dishFoods[] = $dishFood;
        $dishFood->setDish($this);
    
        return $this;
    }

    /**
     * Remove dish
     *
     * @param \App\Entity\Dish $dish
     */
    public function removeDishFood(\App\Entity\DishFood $dish)
    {
        $this->dishFoods->removeElement($dishFood);
    }

    /**
     * Get dishFoods
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDishFoods()
    {
        return $this->dishFoods;
    }

    public function clearDishFoods()
    {
        $this->dishFoods->clear();
    }

    /**
     * Set preparationTime
     *
     * @param integer $preparationTime
     *
     * @return DishMeal
     */
    public function setPreparationTime($preparationTime)
    {
        $this->preparationTime = $preparationTime;
    
        return $this;
    }

    /**
     * Get preparationTime
     *
     * @return integer
     */
    public function getPreparationTime()
    {
        return $this->preparationTime;
    }

    /**
     * Set cookingTime
     *
     * @param integer $cookingTime
     *
     * @return DishMeal
     */
    public function setCookingTime($cookingTime)
    {
        $this->cookingTime = $cookingTime;
    
        return $this;
    }

    /**
     * Get cookingTime
     *
     * @return integer
     */
    public function getCookingTime()
    {
        return $this->cookingTime;
    }

    /**
     * @return Collection|DishFoodGroupParent[]
     */
    public function getDishFoodGroupParents(): Collection
    {
        return $this->dishFoodGroupParents;
    }

    public function addDishFoodGroupParent(DishFoodGroupParent $dishFoodGroupParent): self
    {
        if (!$this->dishFoodGroupParents->contains($dishFoodGroupParent)) {
            $this->dishFoodGroupParents[] = $dishFoodGroupParent;
            $dishFoodGroupParent->setDish($this);
        }

        return $this;
    }

    public function removeDishFoodGroupParent(DishFoodGroupParent $dishFoodGroupParent): self
    {
        if ($this->dishFoodGroupParents->contains($dishFoodGroupParent)) {
            $this->dishFoodGroupParents->removeElement($dishFoodGroupParent);
            // set the owning side to null (unless already changed)
            if ($dishFoodGroupParent->getDish() === $this) {
                $dishFoodGroupParent->setDish(null);
            }
        }

        return $this;
    }

    public function clearDishFoodGroupParents()
    {
        $this->dishFoodGroupParents->clear();
    }

    public function getIsDessert(): ?bool
    {
        return $this->isDessert;
    }

    public function setIsDessert(bool $isDessert): self
    {
        $this->isDessert = $isDessert;

        return $this;
    }

    public function getRankView(): ?int
    {
        return $this->rankView;
    }

    public function setRankView(?int $rankView): self
    {
        $this->rankView = $rankView;

        return $this;
    }

    public function getPreparationTimeUnitTime(): ?UnitTime
    {
        return $this->preparationTimeUnitTime;
    }

    public function setPreparationTimeUnitTime(?UnitTime $preparationTimeUnitTime): self
    {
        $this->preparationTimeUnitTime = $preparationTimeUnitTime;

        return $this;
    }

    public function getCookingTimeUnitTime(): ?UnitTime
    {
        return $this->cookingTimeUnitTime;
    }

    public function setCookingTimeUnitTime(?UnitTime $cookingTimeUnitTime): self
    {
        $this->cookingTimeUnitTime = $cookingTimeUnitTime;

        return $this;
    }

    public function clear(): self
    {
        $this->clearDishFoods();
        $this->clearDishFoodGroups();
        $this->clearDishFoodGroupParents();

        return $this;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function isIsDessert(): ?bool
    {
        return $this->isDessert;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): static
    {
        $this->picture = $picture;

        return $this;
    }

    /**
     * @Groups({"show_dish", "list_dish"})
     */
    public function getPicturePath(): ?string
    {
        if($this->getPicture()) {
            return UploaderHelper::DISH.'/'.$this->getPicture();
        }

        return null;
    }

    /**
     * Get the value of nutritionalTable
     *
     * @return  NutritionalTable
     */ 
    public function getNutritionalTable()
    {
        return $this->nutritionalTable;
    }

    /**
     * Set the value of nutritionalTable
     *
     * @param  NutritionalTable  $nutritionalTable
     *
     * @return  self
     */ 
    public function setNutritionalTable(NutritionalTable $nutritionalTable)
    {
        $this->nutritionalTable = $nutritionalTable;

        return $this;
    }

    public function getEnergy()
    {
        return $this->getNutritionalTable()->getEnergy();
    }
 
    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel($level): static
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get the value of type
     */ 
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the value of type
     *
     * @return  self
     */ 
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the value of haveGluten
     */ 
    public function getHaveGluten()
    {
        return $this->haveGluten;
    }

    /**
     * Set the value of haveGluten
     *
     * @return  self
     */ 
    public function setHaveGluten($haveGluten)
    {
        $this->haveGluten = $haveGluten;

        return $this;
    }

    /**
     * Get the value of haveLactose
     */ 
    public function getHaveLactose()
    {
        return $this->haveLactose;
    }

    /**
     * Set the value of haveLactose
     *
     * @return  self
     */ 
    public function setHaveLactose($haveLactose)
    {
        $this->haveLactose = $haveLactose;

        return $this;
    }

    public function getFoodGroupIds() {
        $fgIds = [];
        foreach($this->getDishFoodGroups() as $dishFoodGroup) {
            $fgIds[] = $dishFoodGroup->getFoodGroup()->getId();
        }

        return $fgIds;
    }
}
