<?php

namespace App\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButtonTypeInterface;

/**
 * Type de bouton submit spécifique pour gérer un groupe d'aliments dans un formulaire de plat.
 *
 * Permet d'associer un attribut 'food_group' au bouton pour identifier le groupe d'aliments
 * auquel il se rapporte.
 */
class DishFoodGroupSubmitType implements SubmitButtonTypeInterface
{
    /**
     * Construit le formulaire.
     *
     * Ici, aucune configuration spécifique n'est nécessaire pour le builder.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Aucun champ supplémentaire n'est ajouté
    }
    
    /**
     * Configure les options du type de bouton.
     *
     * Définit l'option 'food_group' qui permettra d'identifier le groupe d'aliments lié.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('food_group');
    }

    /**
     * Personnalise la vue du formulaire.
     *
     * Passe la valeur 'food_group' aux variables de la vue afin de l'utiliser dans le template.
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['food_group'] = $options['food_group'];
    }

    /**
     * Permet de finaliser la vue avant le rendu.
     *
     * Ici, aucune action spécifique.
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        // Rien à faire ici
    }

    /**
     * Indique le type parent.
     *
     * Ce bouton hérite du SubmitType standard de Symfony.
     *
     * @return string
     */
    public function getParent(): string
    {
        return SubmitType::class;
    }

    /**
     * Préfixe du bloc utilisé dans les templates Twig pour ce bouton.
     *
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return '_dish_food_group_submit';
    }
}