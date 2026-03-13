<?php

namespace App\Form\Type;

use App\Entity\NutritionalTable;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

/**
 * Formulaire pour gérer les valeurs nutritionnelles d'un aliment ou d'une recette.
 * Permet de saisir protéines, lipides, glucides, fibres, sel, sucre, énergie et Nutriscore.
 */
class NutritionalTableType extends AbstractType
{
    /**
     * Construction du formulaire.
     * Ajoute les champs nutritionnels avec des valeurs par défaut depuis l'entité si disponibles.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire Symfony
     * @param array $options Options passées au formulaire, incluant l'entité NutritionalTable en 'data'
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $nutritionalTable = $options['data']; // instance de NutritionalTable ou null

        $builder
            // Protéines
            ->add('protein', NumberType::class, [
                'label' => 'Protéines',
                'required' => false,
                'data' => null !== $nutritionalTable ? $nutritionalTable->getProtein() : null
            ])
            // Lipides
            ->add('lipid', NumberType::class, [
                'label' => 'Lipides',
                'required' => false,
                'data' => null !== $nutritionalTable ? $nutritionalTable->getLipid() : null
            ])
            // Acides gras saturés
            ->add('saturatedFattyAcid', NumberType::class, [
                'label' => 'Acides gras saturés',
                'required' => false,
                'data' => null !== $nutritionalTable ? $nutritionalTable->getSaturatedFattyAcid() : null
            ])
            // Glucides
            ->add('carbohydrate', NumberType::class, [
                'label' => 'Glucides',
                'required' => false,
                'data' => null !== $nutritionalTable ? $nutritionalTable->getCarbohydrate() : null
            ])
            // Sucres
            ->add('sugar', NumberType::class, [
                'label' => 'Sucres',
                'required' => false,
                'data' => null !== $nutritionalTable ? $nutritionalTable->getSugar() : null
            ])
            // Sel
            ->add('salt', NumberType::class, [
                'label' => 'Sel',
                'required' => false,
                'data' => null !== $nutritionalTable ? $nutritionalTable->getSalt() : null
            ])
            // Fibres
            ->add('fiber', NumberType::class, [
                'label' => 'Fibres',
                'required' => false,
                'data' => null !== $nutritionalTable ? $nutritionalTable->getFiber() : null
            ])
            // Energie (kcal/kJ)
            ->add('energy', NumberType::class, [
                'label' => 'Energie',
                'required' => false,
                'data' => null !== $nutritionalTable ? $nutritionalTable->getEnergy() : null
            ])
            // Nutriscore (A à D)
            ->add('nutriscore', ChoiceType::class, [
                'label' => 'Nutriscore',
                'choices' => [
                    'A' => 'A', 
                    'B' => 'B', 
                    'C' => 'C', 
                    'D' => 'D'
                ],
                'data' => null !== $nutritionalTable ? $nutritionalTable->getNutriscore() : null
            ]);
    }

    /**
     * Configuration des options du formulaire.
     * Lie le formulaire à l'entité NutritionalTable et définit une valeur par défaut si aucune donnée n’est fournie.
     *
     * @param OptionsResolver $resolver Le résolveur d'options Symfony
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NutritionalTable::class, // le formulaire est lié à l'entité NutritionalTable
            'data' => null // valeur par défaut si aucune donnée n’est fournie
        ]);
    }
}