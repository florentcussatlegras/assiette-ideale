<?php

namespace App\Entity\FoodGroup\Model;

use Doctrine\ORM\Mapping as ORM;
use Cocur\Slugify\Slugify;

/**
 * ModelGroup
 *
 * @ORM\MappedSuperclass()
 * @ORM\HasLifecycleCallbacks()
 */
abstract class ModelFoodGroup
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
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="semi_short_name", type="string", length=255)
     */
    private $semiShortName;

    /**
     * @var string
     *
     * @ORM\Column(name="short_name", type="string", length=255)
     */
    private $shortName;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255, nullable=true)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="alias", type="string", length=255)
     */
    private $alias;

    /**
     * @var string
     *
     * @ORM\Column(name="slug_alias", type="string", length=255, nullable=true)
     */
    private $slugAlias;

    /**
     * @var string
     *
     * @ORM\Column(name="ranking", type="integer")
     */
    private $ranking;

    public function getClassName()
    {
        return get_class($this);
    }

    public function getClass()
    {
       $arrayClass = explode("\\", get_class($this));

       return $arrayClass[count($arrayClass)-1];
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
     * @return FoodGroup
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
     * Set shortName
     *
     * @param string $shortName
     *
     * @return $this
     */
    public function setShortName($shortName)
    {
        $this->shortName = $shortName;
    
        return $this;
    }

    /**
     * Get shortName
     *
     * @return string
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * Set semiShortName
     *
     * @param string $semiShortName
     *
     * @return $this
     */
    public function setSemiShortName(string $semiShortName): self
    {
        $this->semiShortName = $semiShortName;
        
        return $this;
    }

    /**
     * Get semiShortName
     *
     * @return string
     */
    public function getSemiShortName(): ?string
    {
        return $this->semiShortName;
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

     /**
     * Set slugCode
     *
     * @param string $slugCode
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     *
     */
    public function setSlugAliasValue()
    {
        $slugify = new Slugify();

        $this->slugAlias = $slugify->slugify($this->alias);

        return $this;
    }

     /**
     * Set ranking
     *
     * @param integer $ranking
     *
     * @return FoodGroup
     */
    public function setRanking($ranking)
    {
        $this->ranking = $ranking;

        return $this;
    }

    /**
     * Get ranking
     *
     * @return integer
     */
    public function getRanking()
    {
        return $this->ranking;
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

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function getSlugAlias(): ?string
    {
        return $this->slugAlias;
    }

    public function setSlugAlias(?string $slugAlias): self
    {
        $this->slugAlias = $slugAlias;

        return $this;
    }
}
