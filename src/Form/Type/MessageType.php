<?php

namespace App\Form\Type;

use App\Entity\Message;
use App\Form\Type\SubjectMessageType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Security;

/**
 * Formulaire pour l'envoi d'un message/contact.
 * Pré-remplit l'email si l'utilisateur est connecté.
 */
class MessageType extends AbstractType
{
    private $security;

    /**
     * Constructeur pour injecter le service Security.
     *
     * @param Security $security Service Symfony pour récupérer l'utilisateur courant
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Construction du formulaire.
     * Ajoute les champs email, sujet et message avec leurs contraintes et valeurs par défaut.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire Symfony
     * @param array $options Options passées au formulaire
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Champ email
            ->add('email', EmailType::class, [
                'data' => $this->security->getUser() ? $this->security->getUser()->getEmail() : null, // pré-rempli si connecté
                'attr' => [
                    'class' => 'rounded-lg w-full'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuille saisir une adresse email']), // validation obligatoire
                    new Assert\Email(['message' => 'Veuille saisir une adresse email valide']) // validation format email
                ]
            ])
            
            // Champ sujet, utilisant un form type personnalisé SubjectMessageType
            ->add('subject', SubjectMessageType::class, [
                'attr' => [
                    'class' => 'rounded-lg w-full'
                ],
            ])
            
            // Champ message
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
                'data' => 'Bonjour,', // valeur par défaut du message
                'attr' => [
                    'class' => "h-32 bg-white border border-gray-200 w-full py-1 px-4 rounded-lg",
                ]
            ])
        ;
    }

    /**
     * Configuration des options du formulaire.
     * Associe le formulaire à l'entité Message.
     *
     * @param OptionsResolver $resolver Le résolveur d'options Symfony
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Message::class // associe le formulaire à l'entité Message
        ]);
    }
}