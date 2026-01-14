<?php

namespace App\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;

class PropertyChangerExtension extends AbstractExtension
{
    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('change', [$this, 'getOtherProperty']),
        ];
    }

    public function getOtherProperty($value, $class, $startingProperty, $finalProperty)
    {
        $object = $this->manager->getRepository($class)->findOneBy([$startingProperty => $value]);

        if($object) {
            $getter = sprintf('get%s', ucfirst($finalProperty));
    
            $reflectionClass = new \ReflectionClass($class);
            $reflectionClass->getMethod($getter);
    
            return call_user_func([$object, $getter], []);
        }

        return null;
    }
}