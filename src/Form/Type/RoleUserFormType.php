<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use App\Entity\User;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleUserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', HiddenType::class)
                ->add('roles', ChoiceType::class, [
                    'choices' => [
                        'Administrateur' => 'ROLE_ADMIN',
                        'Peut imiter un utilisateur' => 'ROLE_ALLOWED_TO_SWITCH',
                        'Peut administrer les roles' => 'ROLE_ADMIN_ROLE'
                    ],
                    'multiple' => true
                ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class 
        ]);
    }
}