<?php

namespace App\Form\Type;

use App\Service\ContactUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Type de formulaire pour le champ "Sujet" d'un message de contact.
 * Fournit une liste déroulante des sujets disponibles.
 */
class SubjectMessageType extends AbstractType
{
    /**
     * Service pour récupérer la liste des sujets disponibles.
     *
     * @var ContactUtils
     */
    private $contactUtils;

    /**
     * Injection du service ContactUtils via le constructeur.
     *
     * @param ContactUtils $contactUtils Service pour récupérer les sujets
     */
    public function __construct(ContactUtils $contactUtils)
    {
        $this->contactUtils = $contactUtils;
    }

    /**
     * Configuration des options du formulaire.
     * Définit les valeurs par défaut, les choix disponibles et les contraintes de validation.
     *
     * @param OptionsResolver $resolver Le résolveur d'options Symfony
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Label du champ dans le formulaire
            'label' => 'Sujet',

            // Liste des choix disponibles pour le champ, récupérée via le service ContactUtils
            'choices' => $this->contactUtils->getListSubject(),

            // Détermine l'affichage du label pour chaque option
            'choice_label' => function ($choice, $key, $value) {
                return $choice; // Affiche directement le texte du choix
            },

            // Contraintes de validation appliquées au champ
            'constraints' => [
                new Assert\NotBlank() // Le champ ne peut pas être vide
            ]
        ]);
    }

    /**
     * Définit le type parent de ce formulaire.
     * Ici, nous héritons de ChoiceType pour créer une liste déroulante.
     *
     * @return string Classe du type parent
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}