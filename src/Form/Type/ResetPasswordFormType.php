<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Formulaire pour la réinitialisation du mot de passe d'un utilisateur.
 * 
 * Contient un champ 'plainPassword' de type RepeatedType pour s'assurer
 * que l'utilisateur saisit deux fois le même mot de passe.
 * Applique des contraintes de validation sur le mot de passe.
 */
class ResetPasswordFormType extends AbstractType
{
    /**
     * Construction du formulaire.
     * Ajoute un champ pour saisir le nouveau mot de passe et sa confirmation.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire Symfony
     * @param array $options Options passées au formulaire
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                // Utilisation de PasswordType pour masquer la saisie
                'type' => PasswordType::class,
                // Configuration du premier champ (nouveau mot de passe)
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
                // Configuration du second champ (confirmation du mot de passe)
                'second_options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                    'label' => 'Confirmez votre nouveau mot de passe',
                ],
                // Message affiché si les deux champs ne correspondent pas
                'invalid_message' => 'Les mots de passe ne correspondent pas',
                'mapped' => false, // le champ n'est pas mappé directement sur l'entité
                // Contraintes supplémentaires appliquées au mot de passe
                'constraints' => [
                    new Regex([
                        'pattern' => "/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/",
                        'message' => 'Le mot de passe doit contenir au moins un chiffre, une lettre majuscule et une lettre minuscule, et comporter au minimum 8 caractères'
                    ])
                ]
            ]);
    }
}