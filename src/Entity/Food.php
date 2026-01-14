<?php

namespace App\Entity;

use Cocur\Slugify\Slugify;
use App\Service\UploaderHelper;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\FoodGroup\FoodGroup;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\Diet\Diet;

/**
 * Food
 *
 * @ORM\Table(name="food")
 * @ORM\Entity(repositoryClass="App\Repository\FoodRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Food
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
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank(message="Veuillez saisir un nom")
     * @Groups({"searchable"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255, nullable=true)
     */
    private $slug;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updatedAt", type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Food", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="sub_food_group_id", nullable=true)
     */
    private $subFoodGroup;

    /**
     * @ORM\Column(name="is_sub_food_group", type="boolean")
     * @var boolean
     */
    private $isSubFoodGroup;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FoodGroup\FoodGroup", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="food_group_id", nullable=false, onDelete="CASCADE")
     */
    private $foodGroup;

    /**
     * @var integer
     *
     * @Assert\Type(type="float", message="{{ value }} n'est pas un nombre valide")
     *
     * @ORM\Column(name="equivalenceReferenceFoodGroup", type="float", nullable=true)
     */
    private $equivalenceReferenceFoodGroup;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $picture;

    /**
     * @ORM\Column(name="info", type="string", length=255, nullable=true)
     */
    private $info;

    /**
     * @var integer
     *
     * @Assert\Type(type="float", message="{{ value }} n'est pas un nombre valide")
     *
     * @ORM\Column(name="medianWeight", type="float", nullable=true)
     */
    private $medianWeight;

    /**
     * @var boolean
     *
     * @ORM\Column(name="show_median_weight", type="boolean", nullable=true)
     */
    private $showMedianWeight;

    /**
     * @var string
     * 
     * @Assert\Count(min = "1", minMessage="Vous devez indiquer au moins {{ limit }} unité de mesure")
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\UnitMeasure", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $unitMeasures;

    /**
     * @var boolean
     *
     * @ORM\Column(name="have_gluten", type="boolean")
     */
    private $haveGluten;

    /**
     * @var boolean
     *
     * @ORM\Column(name="have_lactose", type="boolean")
     */
    private $haveLactose;

    /**
     * @var boolean
     *
     * @ORM\Column(name="not_consumable_raw", type="boolean")
     */
    private $notConsumableRaw;

    /**
     * @var boolean
     *
     * @ORM\Column(name="can_be_a_part", type="boolean")
     */
    private $canBeAPart;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $energy;

     /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $lipid;

     /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $carbohydrate;

     /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $protein;

    /**
     * @var NutritionalTable
     *
     * @ORM\OneToOne(targetEntity="App\Entity\NutritionalTable", cascade={"persist"})
     */
    private $nutritionalTable;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Diet\Diet", mappedBy="forbiddenFoods")
     */
    private $forbiddenDiets;

    public function __construct()
    {
        $this->unitMeasures = new ArrayCollection();
        $this->forbiddenDiets = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedValue()
    {
        $this->createdAt = new \DateTime();
    }

    /** 
    * @ORM\PreUpdate 
    */
    public function setUpdatedAtValue()
    {
        $this->updatedAt = new \DateTime();
    }

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
     * Set name
     *
     * @param string $name
     *
     * @return Food
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

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Food
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
     * @return Food
     */
    public function setUpdatedAt($updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Set equivalenceReferenceFoodGroup
     *
     * @param integer $equivalenceReferenceFoodGroup
     *
     * @return Food
     */
    public function setEquivalenceReferenceFoodGroup($equivalenceReferenceFoodGroup)
    {
        $this->equivalenceReferenceFoodGroup = $equivalenceReferenceFoodGroup;

        return $this;
    }

    /**
     * Get equivalenceReferenceFoodGroup
     *
     * @return integer
     */
    public function getEquivalenceReferenceFoodGroup()
    {
        return $this->equivalenceReferenceFoodGroup;
    }

    public function setPicture($picture): self
    {
        $this->picture = $picture;

        return $this;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    /**
     * Set medianWeight
     *
     * @param integer $medianWeight
     *
     * @return Food
     */
    public function setMedianWeight($medianWeight): self
    {
        $this->medianWeight = $medianWeight;

        return $this;
    }

    /**
     * Get medianWeight
     *
     * @return integer
     */
    public function getMedianWeight(): int
    {
        return $this->medianWeight;
    }

    /**
     * Set showMedianWeight
     *
     * @param boolean $showMedianWeight
     *
     * @return Food
     */
    public function setShowMedianWeight($showMedianWeight): self
    {
        $this->showMedianWeight = $showMedianWeight;

        return $this;
    }

    /**
     * Get showMedianWeight
     *
     * @return boolean
     */
    public function getShowMedianWeight(): bool
    {
        return $this->showMedianWeight;
    }

    /**
     * Set info
     *
     * @param string $info
     *
     * @return Food
     */
    public function setInfo($info): self
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Get info
     *
     * @return string
     */
    public function getInfo(): ?string
    {
        return $this->info;
    }

    /**
     * Set foodGroup
     *
     * @param \App\Entity\FoodGroup\FoodGroup $foodGroup
     *
     * @return Food
     */
    public function setFoodGroup(\App\Entity\FoodGroup\FoodGroup $foodGroup = null)
    {
        $this->foodGroup = $foodGroup;

        return $this;
    }

    /**
     * Get foodGroup
     *
     * @return \App\Entity\FoodGroup\FoodGroup
     */
    public function getFoodGroup(): ?FoodGroup
    {
        return $this->foodGroup;
    }

    /**
     * Set slugNameValue
     *
     * @param string $slugNameValue
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     *
     */
    public function setSlugValue()
    {
        $slugify = new Slugify();

        $this->slug = $slugify->slugify($this->name);

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getIsSubFoodGroup(): ?bool
    {
        return $this->isSubFoodGroup;
    }

    public function setIsSubFoodGroup(bool $isSubFoodGroup): self
    {
        $this->isSubFoodGroup = $isSubFoodGroup;

        return $this;
    }

    public function getSubFoodGroup(): ?self
    {
        return $this->subFoodGroup;
    }

    public function setSubFoodGroup(?self $subFoodGroup): self
    {
        $this->subFoodGroup = $subFoodGroup;

        return $this;
    }

    // /**
    //  * @ORM\PrePersist
    //  * @ORM\PreUpdate
    //  */
    // public function setSubFoodGroupDefault()
    // {  
    //     if(null === $this->subFoodGroup && !$this->isSubFoodGroup)
    //     {
    //         $this->subFoodGroup = $this;
    //     }

    //     return true;
    // }

    public function getHaveGluten(): ?bool
    {
        return $this->haveGluten;
    }

    public function setHaveGluten(bool $haveGluten): self
    {
        $this->haveGluten = $haveGluten;

        return $this;
    }

    /**
     * Get the value of haveLactose
     *
     * @return  boolean
     */ 
    public function getHaveLactose(): ?bool
    {
        return $this->haveLactose;
    }

    /**
     * Set the value of haveLactose
     *
     * @param  boolean  $haveLactose
     *
     * @return  self
     */ 
    public function setHaveLactose(bool $haveLactose): self
    {
        $this->haveLactose = $haveLactose;

        return $this;
    }

    public function getNotConsumableRaw(): ?bool
    {
        return $this->notConsumableRaw;
    }

    public function setNotConsumableRaw(?bool $notConsumableRaw): self
    {
        $this->notConsumableRaw = $notConsumableRaw;

        return $this;
    }

    public function getPicturePath()
    {
        if($this->getPicture()) {
            return UploaderHelper::FOOD.'/'.$this->getPicture();
        }

        return null;
    }

    /**
     * @Groups({"searchable"})
     */
    public function getAbsolutePicturePath()
    {
        return 'https://localhost:8000/uploads/' . $this->getPicturePath();
    }

    /**
     * @Groups({"searchable"})
     */
    public function getSubFoodGroupName()
    {
        if(!$this->subFoodGroup) {
            return null;
        }

        return $this->subFoodGroup->getName();
    }

    /**
     * @Groups({"searchable"})
     */
    public function getFoodGroupName()
    {
        return $this->foodGroup->getName();
    }

    public function getCanBeAPart(): ?bool
    {
        return $this->canBeAPart;
    }

    public function setCanBeAPart(bool $canBeAPart): self
    {
        $this->canBeAPart = $canBeAPart;

        return $this;
    }

    /**
     * @return Collection|UnitMeasure[]
     */
    public function getUnitMeasures(): Collection
    {
        return $this->unitMeasures;
    }

    public function addUnitMeasure(UnitMeasure $unitMeasure): self
    {
        if (!$this->unitMeasures->contains($unitMeasure)) {
            $this->unitMeasures[] = $unitMeasure;
        }

        return $this;
    }

    public function removeUnitMeasure(UnitMeasure $unitMeasure): self
    {
        $this->unitMeasures->removeElement($unitMeasure);

        return $this;
    }

    /**
     * Get the value of energy
     */ 
    public function getEnergy()
    {
        // return $this->energy;
        if($this->nutritionalTable) {
            return $this->nutritionalTable->getEnergy();
        }

        return null;
    }

    /**
     * Set the value of energy
     *
     * @return  self
     */ 
    public function setEnergy($energy)
    {
        $this->energy = $energy;

        return $this;
    }

    /**
     * Get the value of lipid
     */ 
    public function getLipid()
    {
        // return $this->lipid;
        if($this->nutritionalTable) {
            return $this->nutritionalTable->getLipid();
        }

        return null;
    }

    /**
     * Set the value of lipid
     *
     * @return  self
     */ 
    public function setLipid($lipid)
    {
        $this->lipid = $lipid;

        return $this;
    }

    /**
     * Get the value of carbohydrate
     */ 
    public function getCarbohydrate()
    {
        // return $this->carbohydrate;
        if($this->nutritionalTable) {
            return $this->nutritionalTable->getCarbohydrate();
        }

        return null;
    }

    /**
     * Set the value of carbohydrate
     *
     * @return  self
     */ 
    public function setCarbohydrate($carbohydrate)
    {
        $this->carbohydrate = $carbohydrate;

        return $this;
    }

    /**
     * Get the value of protein
     */ 
    public function getProtein()
    {
        if($this->nutritionalTable) {
            return $this->nutritionalTable->getProtein();
        }

        return null;
    }

    /**
     * Set the value of protein
     *
     * @return  self
     */ 
    public function setProtein($protein)
    {
        $this->protein = $protein;

        return $this;
    }

    /**
     * Get the value of protein
     */ 
    public function getSodium()
    {
        if($this->nutritionalTable) {
            return $this->nutritionalTable->getSalt();
        }

        return null;
    }

    public function isIsSubFoodGroup(): ?bool
    {
        return $this->isSubFoodGroup;
    }

    public function isShowMedianWeight(): ?bool
    {
        return $this->showMedianWeight;
    }

    public function isHaveGluten(): ?bool
    {
        return $this->haveGluten;
    }

    public function isHaveLactose(): ?bool
    {
        return $this->haveLactose;
    }

    public function isNotConsumableRaw(): ?bool
    {
        return $this->notConsumableRaw;
    }

    public function isCanBeAPart(): ?bool
    {
        return $this->canBeAPart;
    }

    public function getNutritionalTable(): ?NutritionalTable
    {
        return $this->nutritionalTable;
    }

    public function setNutritionalTable(?NutritionalTable $nutritionalTable): static
    {
        $this->nutritionalTable = $nutritionalTable;

        return $this;
    }

    /**
     * @return Collection|Diet[]
     */
    public function getForbiddenDiets(): Collection
    {
        return $this->forbiddenDiets;
    }
    public function addForbiddenDiet(Diet $diet): self
    {
        if (!$this->forbiddenDiets->contains($diet)) {
            $this->forbiddenDiets[] = $diet;
            $diet->addForbiddenFood($this);
        }
        return $this;
    }
    public function removeForbiddenDiet(Diet $diet): self
    {
        if ($this->forbiddenDiets->removeElement($diet)) {
            $diet->removeForbiddenFood($this);
        }
        return $this;
    }
}
