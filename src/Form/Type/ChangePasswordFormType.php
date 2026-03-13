<?php

namespace App\Form\Type;

use App\Form\Model\ChangePassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire pour changer le mot de passe d'un utilisateur.
 *
 * Ce formulaire comprend :
 *  - Le mot de passe actuel de l'utilisateur (`oldPassword`)
 *  - La saisie du nouveau mot de passe avec confirmation (`plainPassword`)
 *
 * Le nouveau mot de passe doit respecter des contraintes de sécurité :
 *  - Minimum 8 caractères
 *  - Au moins une lettre majuscule
 *  - Au moins une lettre minuscule
 *  - Au moins un chiffre
 *
 * Le champ `plainPassword` n'est pas directement mappé à l'entité, 
 * il sera récupéré et encodé dans le contrôleur.
 */
class ChangePasswordFormType extends AbstractType
{
    /**
     * Construit le formulaire de changement de mot de passe
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ du mot de passe actuel
            ->add('oldPassword', PasswordType::class, [
                'label' => 'Votre mot de passe actuel',
            ])
            
            // Champ du nouveau mot de passe avec confirmation
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Merci d\'indiquer votre mot de passe',
                        ]),
                    ],
                    'label' => 'Nouveau mot de passe',
                ],
                'second_options' => [
                    'attr' => ['autocomplete' => 'new-password'],
                    'label' => 'Confirmez votre nouveau mot de passe',
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas',
                
                // Le champ n'est pas mappé directement à l'objet
                'mapped' => false,
                
                // Contraintes supplémentaires de sécurité pour le mot de passe
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => "/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/",
                        'message' => 'Le mot de passe doit contenir au moins un chiffre, une lettre majuscule et une lettre minuscule, et comporter au minimum 8 caractères'
                    ])
                ]
            ])
        ;
    }

    /**
     * Configure les options du formulaire
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // L'objet lié au formulaire
            'data_class' => ChangePassword::class
        ]);
    }
}