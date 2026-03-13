<?php

namespace App\Form\Type;

use App\Service\TypeDishHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Formulaire pour sélectionner le type de plat.
 * Utilise un ensemble de choix prédéfinis via un service.
 */
class TypeDishType extends AbstractType
{
    /**
     * Configure les options du formulaire.
     *
     * @param OptionsResolver $resolver Le résolveur d'options Symfony
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => TypeDishHandler::getChoices(), // Récupère les choix via le service
            'data' => TypeDishHandler::getChoices()['dish.type.entry'], // Valeur par défaut (entrée)
        ]);
    }

    /**
     * Retourne le type parent du champ.
     * Ici, il s'agit d'un ChoiceType (liste déroulante).
     *
     * @return string Classe du type parent
     */
    public function getParent(): string
    {
        return ChoiceType::class; // Hérite du champ de choix standard de Symfony
    }
}