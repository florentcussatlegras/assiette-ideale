<?php

namespace App\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButtonTypeInterface;


class DishFoodGroupSubmitType implements SubmitButtonTypeInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('food_group');
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['food_group'] = $options['food_group'];
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
    }


    public function getParent(): string
    {
        return SubmitType::class;
    }

    public function getBlockPrefix(): string
    {
        return '_dish_food_group_submit';
    }
}
