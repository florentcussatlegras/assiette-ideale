<?php

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class TestFormSubmitEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username')
                ->add('showEmail', CheckboxType::class,[
                    'mapped' => false
                ] );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event): void {

            $user = $event->getData();
            $form = $event->getForm();

            if (isset($user['showEmail']) && $user['showEmail']) {
                $form->add('email', EmailType::class);
            }else{
                unset($user['email']);
                $event->setData($user);
            }

            dump($event->getForm());

        });

        $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $event): void {
            
            $user = $event->getData();
            $form = $event->getForm();
            
            dump($event->getForm());

        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event): void {
            
            $user = $event->getData();
            $form = $event->getForm();
            
            dd($event->getForm());

        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }
}