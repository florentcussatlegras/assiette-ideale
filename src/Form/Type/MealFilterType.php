<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de filtrage pour les repas (Meal).
 * Utilise des champs cachés pour les calories minimum et maximum,
 * modifiés par un curseur et soumis via GET (dans l'URL).
 */
class MealFilterType extends AbstractType
{
    /**
     * Construction du formulaire.
     * Ajoute les champs cachés pour le filtrage des calories.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire Symfony
     * @param array $options Options passées au formulaire
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Champ caché pour la valeur minimale de calories
            ->add('caloriesMin', HiddenType::class, [
                'required' => false, // facultatif
            ])
            
            // Champ caché pour la valeur maximale de calories
            ->add('caloriesMax', HiddenType::class, [
                'required' => false, // facultatif
            ]);
    }

    /**
     * Configuration des options du formulaire.
     * Définit la méthode GET et désactive la protection CSRF.
     *
     * @param OptionsResolver $resolver Le résolveur d'options Symfony
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'method' => 'GET',          // envoi des données en query string
            'csrf_protection' => false, // pas de protection CSRF car c'est un filtre
        ]);
    }
}