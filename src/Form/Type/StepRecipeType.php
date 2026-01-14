<?php

namespace App\Form\Type;

use App\Entity\StepRecipe;
use Symfony\Component\Form\AbstractType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class StepRecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('rankStep', null, [
                'label_attr' => [
                    'class' => 'my-hidden'
                ],
                'required' => false,
                'row_attr' => [
                    'class' => 'mb-0'
                ],
                'attr' => [
                    'class' => 'my-hidden'
                ]
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-2/3 border border-gray min-h-16',
                    'data-controller' => 'textarea-autogrow',
                    'data-textarea-autogrow-resize-debounce-delay-value' => '500',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => StepRecipe::class,
            'allow_extra_fields' => true,
        ]);
    }
}