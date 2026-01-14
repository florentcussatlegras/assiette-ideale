<?php

namespace App\Service;

use Doctrine\Persistence\ManagerRegistry;
// use Florent\QuantityConverterBundle\Model\Provider\AbstractUnitMeasureProvider;
use Symfony\Component\Serializer\SerializerInterface;

// class CustomUnitMeasureProvider extends AbstractUnitMeasureProvider
class CustomUnitMeasureProvider
{
    private $registry;
    private $serializer;
    protected $classOrAlias;

    public function __construct(ManagerRegistry $registry, SerializerInterface $serializer, string $classOrAlias)
    {
        $this->registry = $registry;
        $this->serializer = $serializer;
        $this->classOrAlias = $classOrAlias;
    }

    public function getList(): array
    {
        $unitMeasuresJson = file_get_contents(__DIR__.'/../../public/json/unit-measures.json');

        return $this->serializer->deserialize($unitMeasuresJson, $this->getClass().'[]', 'json');
    }
}