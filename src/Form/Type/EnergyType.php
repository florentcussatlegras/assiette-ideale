<?php

namespace App\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use App\Validator\Constraints\IsEnergyValid;
use App\Validator\Constraints as ProfileAssert;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class EnergyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
       // dd($options['validation_groups']);
      //  dd($options['validation_groups'], $options['unit_measure_selected']);
        $builder->add('energy', IntegerType::class, [
                        'label' => false,
                        'label_attr' => [
                            'class' => 'w-full mb-2'
                        ],
                        'attr' => [
                            'class' => 'form-control w-28'
                        ],
                        'constraints' => [
                            new Assert\Sequentially([
                                new Assert\NotNull([
                                    'message' => 'Veuillez saisir une énergie',
                                    'groups' => $options['validation_groups']
                                ]),
                                new ProfileAssert\IsEnergyValid([
                                    'unitMeasure' => $options['unit_measure_selected'],
                                    'groups' => $options['validation_groups']
                                ])
                            ])
                        ]
                    ]
            )
                ->add('unitMeasureEnergy', ChoiceType::class, [
                    'label' => false,
                    'choices' => ['kCal' => 'kcal', 'kJ' => 'kj'],
                    'constraints' => new Assert\Choice([
                        'choices' => ['kj', 'kcal'],
                        'message' => 'Veuillez saisir une unité d\'énergie'
                    ]),
                    'data' => 'kcal',
                    'expanded' => true,
                    'block_prefix' => 'unit_energy'
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'unit_measure_selected' => 'kcal'
        ]);
    }
}