<?php

namespace App\Form\Type;

use App\Service\ContactUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;

class SubjectMessageType extends AbstractType
{
    private $contactUtils;

    public function __construct(ContactUtils $contactUtils)
    {
        $this->contactUtils = $contactUtils;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'Sujet',
            'choices' => $this->contactUtils->getListSubject(),
            'choice_label' => function($choice, $key, $value) {
                return $choice;
            },
            'constraints' => [
                new Assert\NotBlank
            ] 
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}