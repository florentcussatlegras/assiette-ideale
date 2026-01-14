<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class Order
{
   
    private $id;

    #[Assert\Unique(message: 'La valeur {{ value }} est présente plusieurs fois.')]
    #[Assert\Count(min:3, max: 6, minMessage: 
            'This collection should contain {{ limit }} elements or more. Currently contains {{ count }}', 
            groups: ['strict'])
    ]
    private $name;

    public function _construct(string $name = null)
    {
        $this->name = $name;
    }

    public function __toString()
    {
        return $this->name;
    }


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
