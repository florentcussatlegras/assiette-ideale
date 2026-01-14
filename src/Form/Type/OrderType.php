<?php

// src/Form/Type/OrderType.php
namespace App\Form\Type;

use App\Entity\Order;
use App\Form\Type\PostalAddressType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ...
            ->add('name', TextType::class, [
                'required' => false
            ])
            // ->add('postalAddress', PostalAddressType::class)
            ->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event){
                dump('PRE_SET_DATA');
                dump($event->getForm()->modelData);
                dump($event->getForm()->normData);
                dump($event->getForm()->viewData);
            })
            ->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event){
                dump('POST_SET_DATA');
                dump($event->getForm()->modelData);
                dump($event->getForm()->normData);
                dump($event->getForm()->viewData);
            })
            ->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event){
                dump('PRE_SUBMIT');
                dump($event->getData());
            })
            ->addEventListener(FormEvents::SUBMIT, function(FormEvent $event){
                dump('SUBMIT');
                dump($event->getData());
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event){
                dump('POST_SUBMIT');
                dump($event->getData());
                exit;
            })
            
        ;
    }

    public function configureOptions(OptionsResolver $options)
    {
        $options->setDefaults([
            'data_class' => Order::class
        ]);
    }
}