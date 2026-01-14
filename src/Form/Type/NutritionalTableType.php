<?php

namespace App\Form\Type;

use App\Entity\NutritionalTable;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class NutritionalTableType extends AbstractType
{
    public function __construct(
        private RequestStack $requestStack
    )
    {}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $session = $this->requestStack->getSession();

        $nutritionalTable = $options['data'];

        $builder->add('protein', NumberType::class, [
                    'label' => 'Protéines',
                    'required' => false,
                    'data' => null !== $nutritionalTable ? $nutritionalTable->getProtein() : null
                ])
                ->add('lipid', NumberType::class, [
                    'label' => 'Lipides',
                    'required' => false,
                    'data' => null !== $nutritionalTable ? $nutritionalTable->getLipid() : null
                ])
                ->add('saturatedFattyAcid', NumberType::class, [
                    'label' => 'Acides gras saturés',
                    'required' => false,
                    'data' => null !== $nutritionalTable ? $nutritionalTable->getSaturatedFattyAcid() : null
                ])
                ->add('carbohydrate', NumberType::class, [
                    'label' => 'Glucides',
                    'required' => false,
                    'data' => null !== $nutritionalTable ? $nutritionalTable->getCarbohydrate() : null
                ])
                ->add('sugar', NumberType::class, [
                    'label' => 'Sucres',
                    'required' => false,
                    'data' => null !== $nutritionalTable ? $nutritionalTable->getSugar() : null
                ])
                ->add('salt', NumberType::class, [
                    'label' => 'Sel',
                    'required' => false,
                    'data' => null !== $nutritionalTable ? $nutritionalTable->getSalt() : null
                ])
                ->add('fiber', NumberType::class, [
                    'label' => 'Fibres',
                    'required' => false,
                    'data' => null !== $nutritionalTable ? $nutritionalTable->getFiber() : null
                ])
                ->add('energy', NumberType::class, [
                    'label' => 'Energie',
                    'required' => false,
                    'data' => null !== $nutritionalTable ? $nutritionalTable->getEnergy() : null
                ])
                ->add('nutriscore', ChoiceType::class, [
                    'label' => 'Nutriscore',
                    'choices' => [
                        'A' => 'A', 
                        'B' => 'B', 
                        'C' => 'C', 
                        'D' => 'D'
                    ],
                    'data' => null !== $nutritionalTable ? $nutritionalTable->getNutriscore() : null
                ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NutritionalTable::class,
            'data' => $nutritionalTable ?? null
        ]);
    }
}