<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;

class BirthdayTransformer implements DataTransformerInterface
{
    public function transform($dateTime)
    {
        if(empty($dateTime)) {
            return '';
        }

        if(!$dateTime instanceof \DateTimeInterface) {
            throw new InvalidTypeException('La date n\'est pas un objet \DateTime!');
        }

        return $dateTime->format("Y-m-d");
    }

    public function reverseTransform($dateString)
    {
        try{
            $dateTime = new \DateTime($dateString);
        }catch(\Exception $e){
            $exception = new TransformationFailedException();
            $exception->setInvalidMessage('Le format de la date saisie est invalide.');
            
            throw $exception;
        }

        return $dateTime;
    }
}