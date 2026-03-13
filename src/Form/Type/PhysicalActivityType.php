<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\WorkingType;
use App\Entity\SportingTime;
use App\Entity\PhysicalActivity;

/**
 * Formulaire pour gérer l'activité physique d'un utilisateur.
 * Comprend le temps passé à pratiquer du sport et le type de métier.
 */
class PhysicalActivityType extends AbstractType
{
    /**
     * Construction du formulaire.
     * Ajoute les champs pour le temps sportif et le type de métier.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire Symfony
     * @param array $options Options passées au formulaire
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ pour sélectionner la durée ou fréquence d'activité sportive
            ->add('sportingTime', EntityType::class, [
                'class' => SportingTime::class,       // Entité utilisée pour le choix
                'label' => 'Activités sportives',     // Label affiché
                'label_attr' => [
                    'class' => 'form-label'          // Classe CSS appliquée au label
                ],
                'expanded' => true                     // Affiche les choix sous forme de boutons radio
            ])
            // Champ pour sélectionner le type de métier
            ->add('workingType', EntityType::class, [
                'class' => WorkingType::class,        // Entité utilisée pour le choix
                'label' => 'Votre métier',            // Label affiché
                'label_attr' => [
                    'class' => 'form-label'          // Classe CSS appliquée au label
                ],
                'expanded' => true                     // Affiche les choix sous forme de boutons radio
            ])
        ;
    }

    /**
     * Configuration des options du formulaire.
     * Lie le formulaire à l'entité PhysicalActivity.
     *
     * @param OptionsResolver $resolver Le résolveur d'options Symfony
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PhysicalActivity::class // Formulaire lié à l'entité PhysicalActivity
        ]);
    }
}