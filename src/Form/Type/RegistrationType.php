<?php

namespace App\Form\Type;

use App\Entity\User;
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

class RegistrationType extends AbstractType
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
                        'class' => 'rounded w-full',
                    ],
                ]
            )
            ->add('email', EmailType::class, [
                    'label' => 'Votre adresse email',
                    'attr' => [
                        'class' => 'rounded w-full',
                    ],
                ]
            )
            /* ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'help' => 'Le mot de passe doit contenir au minimum 6 caractères composés de lettres et de chiffres',
                'invalid_message' => 'Les mots de passe ne correspondent pas',
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmez votre mot de passe'],
                'constraints' => [
                    new AppAssert\PasswordRequirements([
                        'groups' => ['registration']
                    ])
                ]
            ]) */
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Votre mot de passe',
                'mapped' => false,
                'attr' => [
                    'data-password-visibility-target' => 'input',
                    'spellcheck' => 'false',
                    'class' => 'rounded w-full',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir un mot de passe',
                        'groups' => ['registration'],
                    ]),
                    new Assert\Regex([
                        'pattern' => "/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/",
                        'message' => 'Le mot de passe doit contenir au moins un chiffre et une lettre majuscule et minuscule, et au moins 8 caractères ou plus',
                        'groups' => ['registration'],
                    ])
                ],
            ])
            ->add('terms', CheckboxType::class, [
                    'label' => 'J\'ai lu et j\'accepte les conditions générales d\'utilisation et la politique de protection des données personnelles',
                    'mapped' => false,
                    'label_attr' => [
                        'class' => 'font-normal'
                    ],
                    'constraints' => [
                        new Assert\IsTrue([
                                'message' => 'Pour continuer vous devez accepter nos conditions',
                                'groups' => ['registration']
                            ]
                        )
                    ]
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                 'data_class' => User::class,
                 'validation_groups' => ['registration']
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'user_registration';
    }

}
