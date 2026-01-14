<?php

namespace App\Form\Type;

use App\Entity\Member;
use App\Validator\Constraints as AppAssert;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Intl\Countries;

class MemberType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', null, [
                    'label' => 'Nom ou pseudo',
                    'empty_data' => '',
                    'attr' => [
                        'class' => 'rounded-lg w-full',
                    ],
                ]
            )
            ->add('email', EmailType::class, [
                    'label' => 'Votre adresse email',
                    'attr' => [
                        'class' => 'rounded-lg w-full',
                    ],
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                 'data_class' => Member::class,
        ]);
    }

}
