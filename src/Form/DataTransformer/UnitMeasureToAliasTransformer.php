<?php

namespace App\Form\DataTransformer;

use App\Entity\UnitMeasure;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class UnitMeasureToAliasTransformer implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function transform(mixed $unitMeasure): mixed
    {
        if(null === $unitMeasure) 
        {
            return '';
        }

        if(!$unitMeasure instanceof UnitMeasure)
        {
            throw new \Exception('Object must be instance of UnitMeasure');
        }

        return $unitMeasure->getAlias();
    }

    public function reverseTransform(mixed $alias): mixed
    {
        if(!$alias)
        {
            return null;
        }
        
        $unitMeasure = $this->entityManager->getRepository(UnitMeasure::class)->findOneBy(['alias' => $alias]);

        if(null === $unitMeasure)
        {
            $messageErrorPrivate = sprintf(
                'Aucune unité de mesure ne possède l\'alias %s',
                $alias
            );
            $messageErrorPublic = '{{ value }} n\'est pas un alias valide';

            $failure = new TransformationFailedException($messageErrorPrivate);
            $failure->setInvalidMessage($messageErrorPublic, [
                '{{ value }}' => $alias
            ]);

            throw $failure;
        }

        return $unitMeasure;
    }
}