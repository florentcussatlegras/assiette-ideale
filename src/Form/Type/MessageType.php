<?php

namespace App\Form\Type;

use App\Entity\Message;
use App\Form\Type\SubjectMessageType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Security;

class MessageType extends AbstractType
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'data' => $this->security->getUser() ? $this->security->getUser()->getEmail() : null,
                'attr' => [
                    'class' => 'rounded-lg w-full'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuille saisir une adresse email']),
                    new Assert\Email(['message' => 'Veuille saisir une adresse email valide'])
                ]
            ])
            ->add('subject', SubjectMessageType::class, [
                'attr' => [
                    'class' => 'rounded-lg w-full'
                ],
            ])
            ->add('body', TextareaType::class, [
                'label' => 'Message',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir un message'
                    ]),
                    new Assert\Length([
                            'min' => 10, 
                            'max' => 500, 
                            'minMessage' => 'Votre message doit contenir au moins {{ limit }} caractères',
                            'maxMessage' => 'Votre message doit contenir un maximum de {{ limit }} caractères'
                    ]),
                ],
                'data' => 'Bonjour,',
                'attr' => [
                    'class' => "h-32 bg-white border border-gray-200 w-full py-1 px-4 rounded-lg",
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Message::class
        ]);
    }
}