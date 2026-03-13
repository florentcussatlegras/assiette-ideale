<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use App\Validator\Constraints as ProfileAssert;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

/**
 * Formulaire pour saisir l'énergie d'un utilisateur.
 *
 * Permet de saisir la valeur énergétique et l'unité (kcal ou kJ), 
 * avec validation pour vérifier que la valeur est correcte selon l'unité choisie.
 */
class EnergyType extends AbstractType
{
    /**
     * Construction du formulaire.
     *
     * Ajoute les champs pour saisir l'énergie et sélectionner l'unité.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire Symfony
     * @param array $options Options passées au formulaire
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Champ principal : énergie
        $builder->add('energy', IntegerType::class, [
            'label' => false, // pas de label affiché
            'label_attr' => [
                'class' => 'w-full mb-2'
            ],
            'attr' => [
                'class' => 'form-control w-28' // style et largeur
            ],
            'constraints' => [
                new Assert\Sequentially([ // Validation séquentielle
                    new Assert\NotNull([ // Doit être rempli
                        'message' => 'Veuillez saisir une énergie',
                        'groups' => $options['validation_groups']
                    ]),
                    new ProfileAssert\IsEnergyValid([ // Validation personnalisée pour l'énergie
                        'unitMeasure' => $options['unit_measure_selected'], // unité choisie
                        'groups' => $options['validation_groups']
                    ])
                ])
            ]
        ])
        // Champ de choix pour l'unité d'énergie
        ->add('unitMeasureEnergy', ChoiceType::class, [
            'label' => false, // pas de label affiché
            'choices' => ['kCal' => 'kcal', 'kJ' => 'kj'], // options visibles pour l'utilisateur
            'constraints' => new Assert\Choice([ // Vérifie que la valeur choisie est valide
                'choices' => ['kj', 'kcal'],
                'message' => 'Veuillez saisir une unité d\'énergie'
            ]),
            'data' => 'kcal', // valeur par défaut
            'expanded' => true, // rendu sous forme de boutons radio
            'block_prefix' => 'unit_energy'
        ]);
    }

    /**
     * Configuration des options du formulaire.
     *
     * @param OptionsResolver $resolver Le résolveur d'options Symfony
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        // Option personnalisée pour connaître l'unité sélectionnée
        $resolver->setDefaults([
            'unit_measure_selected' => 'kcal'
        ]);
    }
}