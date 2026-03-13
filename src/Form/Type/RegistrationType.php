<?php

namespace App\Form\Type;

use App\Entity\User;
use App\Validator\Constraints as AppAssert;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Formulaire de création de compte utilisateur (inscription).
 * Permet de saisir les informations nécessaires pour créer un nouvel utilisateur.
 */
class RegistrationType extends AbstractType
{
    /**
     * Construction du formulaire.
     * Ajoute les champs de base : nom, email, mot de passe et acceptation des conditions.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire Symfony
     * @param array $options Options passées au formulaire
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Champ pour le nom ou pseudo de l'utilisateur
            ->add('username', null, [
                'label' => 'Nom ou pseudo',
                'empty_data' => '',
                'attr' => [
                    'class' => 'rounded w-full',
                ],
            ])
            // Champ pour l'email de l'utilisateur
            ->add('email', EmailType::class, [
                'label' => 'Votre adresse email',
                'attr' => [
                    'class' => 'rounded w-full',
                ],
            ])
            // Champ pour le mot de passe
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Votre mot de passe',
                'mapped' => false,
                'attr' => [
                    'data-password-visibility-target' => 'input',
                    'spellcheck' => 'false',
                    'class' => 'rounded w-full',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir un mot de passe',
                        'groups' => ['registration'],
                    ]),
                    new Assert\Regex([
                        'pattern' => "/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/",
                        'message' => 'Le mot de passe doit contenir au moins un chiffre et une lettre majuscule et minuscule, et au moins 8 caractères ou plus',
                        'groups' => ['registration'],
                    ])
                ],
            ])
            // Champ pour accepter les conditions générales
            ->add('terms', CheckboxType::class, [
                'label' => 'J\'ai lu et j\'accepte les conditions générales d\'utilisation et la politique de protection des données personnelles',
                'mapped' => false,
                'label_attr' => [
                    'class' => 'font-normal'
                ],
                'constraints' => [
                    new Assert\IsTrue([
                        'message' => 'Pour continuer vous devez accepter nos conditions',
                        'groups' => ['registration']
                    ])
                ]
            ]);
    }

    /**
     * Configuration des options du formulaire.
     * Définit les options par défaut, notamment l'entité associée.
     *
     * @param OptionsResolver $resolver Le résolveur d'options Symfony
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['registration'],
        ]);
    }

    /**
     * Retourne le préfixe du bloc utilisé pour le formulaire.
     * Permet de personnaliser les noms des champs dans les templates Twig.
     *
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'user_registration';
    }
}