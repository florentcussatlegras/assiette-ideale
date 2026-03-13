<?php  

namespace App\Form\Type;

use App\Entity\UnitMeasure;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Formulaire pour saisir la quantité d'un aliment dans une recette.
 * Contient l'identifiant de l'aliment, la quantité et l'unité de mesure.
 */
class QuantityFoodFormType extends AbstractType
{
    /**
     * Construction du formulaire.
     * Ajoute les champs caché (foodId), texte (quantity) et choix d'unité de mesure.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire Symfony
     * @param array $options Options passées au formulaire, incluant 'idFood' pour pré-remplir l'identifiant
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Champ caché pour stocker l'identifiant de l'aliment
            ->add('foodId', HiddenType::class, [
                'data' => isset($options['idFood']) ? $options['idFood'] : null
            ])
            // Champ texte pour saisir la quantité de l'aliment
            ->add('quantity', TextType::class, [
                'attr' => [
                    'placeholder' => 'Quantité'  // Placeholder affiché dans le champ
                ]
            ])
            // Champ pour sélectionner l'unité de mesure
            ->add('unitMeasure', EntityType::class, [
                'class' => UnitMeasure::class,   // Entité utilisée pour le choix
                'choice_label' => 'alias',       // Affiche l'alias de l'unité (ex: g, ml)
            ])
        ;
    }

    /**
     * Configuration des options du formulaire.
     * Définit l'option personnalisée 'idFood' pour préremplir le champ hidden.
     *
     * @param OptionsResolver $resolver Le résolveur d'options Symfony
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        // Définit l'option personnalisée 'idFood' pour pouvoir préremplir le champ hidden
        $resolver->setDefined('idFood');
        // Autorise différents types pour cette option (null, integer ou string)
        $resolver->setAllowedTypes('idFood', ['null', 'integer', 'string']);
    }
}