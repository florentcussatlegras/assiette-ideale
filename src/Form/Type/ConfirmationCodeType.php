<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

class ConfirmationCodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('code', TextType::class, [
            'label' => 'Code de confirmation',
            'mapped' => false,
            'attr' => [
                'placeholder' => '123456',
                'maxlength' => 6,
                'class' => 'form-control text-center fs-3',
                'autocomplete' => 'one-time-code'
            ],
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length([
                    'min' => 6,
                    'max' => 6,
                    'exactMessage' => 'Le code doit contenir 6 chiffres.'
                ]),
                new Assert\Regex([
                    'pattern' => '/^\d{6}$/',
                    'message' => 'Le code doit contenir uniquement des chiffres.'
                ])
            ]
        ]);
    }
}
