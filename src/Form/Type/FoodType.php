<?php

namespace App\Form\Type;

use App\Entity\Food;
use App\Entity\UnitMeasure;
use App\Entity\NutritionalTable;
use App\Entity\FoodGroup\FoodGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class FoodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('isSubFoodGroup', CheckboxType::class, [
                'required' => false
            ])
            // ->add('equivalenceReferenceFoodGroup')
            ->add('pictureFile', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
            ])
            ->add('info', TextareaType::class, [
                'required' => false
            ])
            ->add('medianWeight', NumberType::class, [
                'required' => false
            ])
            ->add('showMedianWeight', NumberType::class, [
                'required' => false
            ])
            ->add('haveGluten', CheckboxType::class, [
                'required' => false
            ])
            ->add('haveLactose', CheckboxType::class, [
                'required' => false
            ])
            ->add('notConsumableRaw')
            ->add('canBeAPart', CheckboxType::class, [
                'required' => false
            ])

            // ->add('energy', NumberType::class)
            // ->add('lipid', NumberType::class)
            // ->add('protein', NumberType::class)
            // ->add('carbohydrate', NumberType::class)

            ->add('nutritionalTable', NutritionalTableType::class, [
                'data' => $options['data'] ? $options['data']->getNutritionalTable() : null
            ])

            ->add('subFoodGroup')
            ->add('foodGroup', EntityType::class, [
                'class' => FoodGroup::class
            ])
            ->add('unitMeasures', EntityType::class, [
                'class' => UnitMeasure::class,
                'choice_label' => 'alias',
                'multiple' => true
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'bg-sky-400 px-4 text-white round-md'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Food::class,
        ]);
    }
}
