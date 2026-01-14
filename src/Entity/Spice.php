<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Spice
 *
 * @ORM\Table(name="spice")
 * @ORM\Entity(repositoryClass="App\Repository\SpiceRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Spice
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
     * @Assert\NotBlank(message="Veuillez saisir un nom")
     * @Assert\Regex("/[0-9]+/", message="Doit contenir un chiffre", groups={"group_1"})
     * @Assert\Length(min=8, minMessage="{{ limit }} characters at least", groups={"group_2"})
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    public $firstname;

    public function __construct(string $name = null, int $id = null)
    {
        $this->name = $name;
        $this->id = $id;
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
     * @return Spice
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
}
