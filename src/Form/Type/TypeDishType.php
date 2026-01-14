<?php

namespace App\Form\Type;

use App\Service\TypeDishHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class TypeDishType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => TypeDishHandler::getChoices(),
            'data' => TypeDishHandler::getChoices()['dish.type.entry'],
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}