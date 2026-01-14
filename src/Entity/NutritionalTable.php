<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\NutritionalTableRepository;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * NutritionalTable
 * 
 * @ORM\Table(name="nutritional_table")
 * @ORM\Entity()
 */
class NutritionalTable
{
    const NUTRIENTS_TYPE_COLORS = [
        'protein' => '#c11200',
        'lipid' => '#dbd77d',
        'carbohydrate' => '#697882',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    #[Groups('group_chart')]
    private $protein;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    #[Groups('group_chart')]
    private $lipid;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $saturatedFattyAcid;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    #[Groups(['group_chart'])]
    private $carbohydrate;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $sugar;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $salt;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $fiber;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $energy;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $nutriscore;

    public function getId(): ?int
    {
        return $this->id;
    }



    /**
     * Get the value of protein
     */ 
    public function getProtein(): ?float
    {
        return $this->protein;
    }

    /**
     * Set the value of protein
     *
     * @return  self
     */ 
    public function setProtein(?float $protein = null)
    {
        $this->protein = $protein;

        return $this;
    }

    /**
     * Get the value of lipid
     */ 
    public function getLipid()
    {
        return $this->lipid;
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
     * Get the value of saturatedFattyAcid
     */ 
    public function getSaturatedFattyAcid()
    {
        return $this->saturatedFattyAcid;
    }

    /**
     * Set the value of saturatedFattyAcid
     *
     * @return  self
     */ 
    public function setSaturatedFattyAcid($saturatedFattyAcid)
    {
        $this->saturatedFattyAcid = $saturatedFattyAcid;

        return $this;
    }

    /**
     * Get the value of carbohydrate
     */ 
    public function getCarbohydrate()
    {
        return $this->carbohydrate;
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
     * Get the value of sugar
     */ 
    public function getSugar()
    {
        return $this->sugar;
    }

    /**
     * Set the value of sugar
     *
     * @return  self
     */ 
    public function setSugar($sugar)
    {
        $this->sugar = $sugar;

        return $this;
    }

    /**
     * Get the value of salt
     */ 
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set the value of salt
     *
     * @return  self
     */ 
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get the value of fiber
     */ 
    public function getFiber()
    {
        return $this->fiber;
    }

    /**
     * Set the value of fiber
     *
     * @return  self
     */ 
    public function setFiber($fiber)
    {
        $this->fiber = $fiber;

        return $this;
    }

    /**
     * Get the value of energy
     */ 
    public function getEnergy()
    {
        return $this->energy;
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
     * Get the value of nutriscore
     */ 
    public function getNutriscore()
    {
        return $this->nutriscore;
    }

    /**
     * Set the value of nutriscore
     *
     * @return  self
     */ 
    public function setNutriscore($nutriscore)
    {
        $this->nutriscore = $nutriscore;

        return $this;
    }
}
