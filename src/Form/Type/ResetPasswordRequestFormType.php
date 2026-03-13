<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire pour demander la réinitialisation du mot de passe.
 *
 * Contient un seul champ 'email' pour saisir l'adresse de l'utilisateur
 * afin d'envoyer un lien de réinitialisation.
 *
 * Contraintes appliquées :
 *  - NotBlank : le champ ne peut pas être vide
 *  - Type EmailType : s'assure que la valeur saisie est une adresse email
 */
class ResetPasswordRequestFormType extends AbstractType
{
    /**
     * Construction du formulaire.
     * Ajoute le champ email avec ses contraintes.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire Symfony
     * @param array $options Options passées au formulaire
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['autocomplete' => 'email'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your email',
                    ]),
                ],
            ]);
    }

    /**
     * Configuration des options du formulaire.
     *
     * @param OptionsResolver $resolver Le résolveur d'options Symfony
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}