<?php

namespace App\Form\Type;

use App\Entity\Spice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class SpiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => Spice::class,
            'full_name' => 'zozo',
            'block_name' => 'form_spice_name',
            'block_prefix' => 'form_spice_prefix',
            'validation_groups' => ['registration1'],
            // 'empty_data' => new Spice('toto', 3),
            'csrf_protection' => false,
            'label' => 'TOTOTOTO',
            'compound' => true,
            'required' => true
        ]);
    }
}
