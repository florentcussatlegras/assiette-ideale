<?php

namespace App\Form\Type;

use App\Entity\StepRecipe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * Formulaire pour gérer une étape de recette.
 * Permet de saisir la description de l'étape et son rang dans la séquence.
 */
class StepRecipeType extends AbstractType
{
    /**
     * Construction du formulaire.
     * Ajoute les champs nécessaires pour définir l'étape de la recette.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire Symfony
     * @param array $options Options supplémentaires passées au formulaire
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Champ pour le rang de l'étape dans la séquence
            ->add('rankStep', null, [
                'label_attr' => [
                    'class' => 'my-hidden' // Cache le label visuellement
                ],
                'required' => false, // Le champ n'est pas obligatoire
                'row_attr' => [
                    'class' => 'mb-0' // Classe CSS appliquée à la ligne contenant le champ
                ],
                'attr' => [
                    'class' => 'my-hidden' // Cache également le champ dans le DOM
                ]
            ])
            // Champ pour la description textuelle de l'étape
            ->add('description', TextareaType::class, [
                'required' => false, // La description n'est pas obligatoire
                'attr' => [
                    'class' => 'w-2/3 rounded-lg border border-gray-200 min-h-16', // Style du champ : largeur, bordure, hauteur minimale
                    'data-controller' => 'textarea-autogrow', // Contrôleur JS pour ajuster automatiquement la hauteur du textarea
                    'data-textarea-autogrow-resize-debounce-delay-value' => '500', // Délai de redimensionnement (en ms)
                ]
            ])
        ;
    }

    /**
     * Configuration des options du formulaire.
     * Lie le formulaire à l'entité StepRecipe et autorise des champs supplémentaires.
     *
     * @param OptionsResolver $resolver Le résolveur d'options Symfony
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => StepRecipe::class, // L'entité associée au formulaire
            'allow_extra_fields' => true, // Permet d'accepter des champs non définis explicitement
        ]);
    }
}