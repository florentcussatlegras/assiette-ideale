<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ResetPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Merci d\'indiquer votre mot de passe',
                        ]),
                    ],
                    'label' => 'Nouveau mot de passe',
                ],
                'second_options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                    'label' => 'Confirmez votre nouveau mot de passe',
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas',
                'mapped' => false,
                'constraints' => [
                    new Regex([
                        'pattern' => "/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/",
                        'message' => 'Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters'
                    ])
                ]
            ]);
    }
}
