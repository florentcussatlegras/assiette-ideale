<?php

namespace App\Form\Type;


use App\Form\Model\ChangePassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('oldPassword', PasswordType::class, [
                'label' => 'Votre mot de passe actuel',
            ])
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
                    'attr' => ['autocomplete' => 'new-password',
                    ],
                    'label' => 'Confirmez votre nouveau mot de passe',
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas',
                // Instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => "/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/",
                        'message' => 'Le mot de passe doit contenir au moins un chiffre, une lettre majuscule et une lettre minuscule, et comporter au minimum 8 caractères'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChangePassword::class
        ]);
    }
}
