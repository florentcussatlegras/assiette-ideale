<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\WorkingType;
use App\Entity\SportingTime;
use App\Entity\PhysicalActivity;

class PhysicalActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sportingTime', EntityType::class, [
                'class' => SportingTime::class,
                'label' => 'Activités sportives',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'expanded' => true
            ])
            ->add('workingType', EntityType::class, [
                'class' => WorkingType::class,
                'label' => 'Votre métier',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'expanded' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PhysicalActivity::class
        ]);
    }
}
