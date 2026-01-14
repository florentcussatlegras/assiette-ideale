<?php

// src/Form/Type/PostalAddressType.php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PostalAddressType extends AbstractType
{
    // ...

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('addressLine1', TextType::class, [
                'help' => 'Street address, P.O. box, company name'
            ])
            ->add('addressLine2', TextType::class, [
                'help' => 'Apartment, suite, unit, building, floor',
            ])
            ->add('city', TextType::class)
            ->add('state', TextType::class, [
                'label' => 'State',
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'ZIP Code',
            ])
        ;
    }
}