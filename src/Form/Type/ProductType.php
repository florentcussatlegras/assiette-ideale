<?php

namespace App\Form\Type;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name')
                ->add('brochure', FileType::class, [
                    'label' => 'Brochure (PDF)',
                    'mapped' => false,
                    'required' => false,
                    'constraints' => [
                        new Assert\File([
                            'maxSize' => '1024k',
                            'maxSizeMessage' => '{{ size }}{{ suffix }} must be less than {{ limit }}{{ suffix }}',
                            'mimeTypes' => [
                                'application/pdf',
                                'application/x-pdf'
                            ],
                            'mimeTypesMessage' => 'Please upload a valid PDF document'
                        ])
                    ]
                ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class
        ]);
    }
}