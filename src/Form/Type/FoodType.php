<?php

namespace App\Form\Type;

use App\Entity\Food;
use App\Entity\UnitMeasure;
use App\Entity\FoodGroup\FoodGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * Formulaire pour créer ou éditer un aliment (Food).
 */
class FoodType extends AbstractType
{
    /**
     * Construction du formulaire.
     * Ajoute les champs pour saisir les informations d'un aliment, 
     * gérer l'image, le groupe alimentaire et la table nutritionnelle.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire Symfony
     * @param array $options Options passées au formulaire
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Nom de l'aliment
            ->add('name')

            // Checkbox pour savoir si l'aliment est un sous-groupe
            ->add('isSubFoodGroup', CheckboxType::class, [
                'required' => false
            ])

            // Image associée à l'aliment (non mappée directement à l'entité)
            ->add('pictureFile', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
            ])

            // Champ de texte pour des informations complémentaires
            ->add('info', TextareaType::class, [
                'required' => false
            ])

            // Poids médian de l'aliment
            ->add('medianWeight', NumberType::class, [
                'required' => false
            ])

            // Poids affiché (pour l'affichage dans certaines interfaces)
            ->add('showMedianWeight', NumberType::class, [
                'required' => false
            ])

            // Checkbox : contient du gluten
            ->add('haveGluten', CheckboxType::class, [
                'required' => false
            ])

            // Checkbox : contient du lactose
            ->add('haveLactose', CheckboxType::class, [
                'required' => false
            ])

            // Champ pour indiquer si l'aliment n'est pas consommable cru
            ->add('notConsumableRaw')

            // Checkbox : peut faire partie d'un plat
            ->add('canBeAPart', CheckboxType::class, [
                'required' => false
            ])

            // Sous-formulaire pour la table nutritionnelle associée
            ->add('nutritionalTable', NutritionalTableType::class, [
                'data' => $options['data'] ? $options['data']->getNutritionalTable() : null
            ])

            // Sous-groupe auquel appartient cet aliment
            ->add('subFoodGroup')

            // Groupe alimentaire (FoodGroup) associé à l'aliment
            ->add('foodGroup', EntityType::class, [
                'class' => FoodGroup::class
            ])

            // Unités de mesure possibles pour l'aliment (ex: g, ml)
            ->add('unitMeasures', EntityType::class, [
                'class' => UnitMeasure::class,
                'choice_label' => 'alias', // affichage basé sur l'alias
                'multiple' => true // possibilité de choisir plusieurs unités
            ])

            // Bouton de soumission du formulaire
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'bg-sky-400 px-4 text-white round-md'
                ]
            ])
        ;
    }

    /**
     * Configuration des options du formulaire.
     *
     * @param OptionsResolver $resolver Le résolveur d'options Symfony
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Food::class, // le formulaire mappe l'entité Food
        ]);
    }
}