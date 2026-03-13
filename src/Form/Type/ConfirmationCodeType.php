<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire pour saisir un code de confirmation.
 *
 * Utilisé typiquement pour la vérification d'email ou de numéro de téléphone.
 *
 * Champs :
 *  - code : code à 6 chiffres, non mappé à une entité, avec contraintes de validation :
 *      - Obligatoire (NotBlank)
 *      - Doit contenir exactement 6 caractères (Length)
 *      - Doit être composé uniquement de chiffres (Regex)
 */
class ConfirmationCodeType extends AbstractType
{
    /**
     * Construit le formulaire avec le champ 'code'.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('code', TextType::class, [
            'label' => 'Code de confirmation',
            'mapped' => false, // Non mappé à une entité
            'attr' => [
                'placeholder' => '123456',
                'maxlength' => 6,
                'class' => 'form-control text-center fs-3',
                'autocomplete' => 'one-time-code' // Permet le remplissage automatique sur mobile
            ],
            'constraints' => [
                new Assert\NotBlank(), // Champ obligatoire
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