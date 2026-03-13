<?php

namespace App\Form\Type;

use App\Entity\Spice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Formulaire pour gérer l'entité Spice (épices).
 * 
 * Ce formulaire contient un seul champ :
 *  - name : le nom de l'épice, optionnel côté formulaire.
 * 
 * Options configurées dans configureOptions :
 *  - data_class : lie le formulaire à l'entité Spice.
 *  - block_name et block_prefix : personnalisent les blocs Twig pour ce formulaire.
 *  - validation_groups : groupe de validation 'registration1' utilisé pour ce formulaire.
 *  - csrf_protection : désactivé (false), utile pour API ou modals JS.
 *  - compound : true car le formulaire peut contenir plusieurs champs (ici 1 seul).
 *  - required : true au niveau du formulaire global.
 */
class SpiceType extends AbstractType
{
    /**
     * Construction du formulaire.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire
     * @param array $options Options passées au formulaire
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ pour saisir le nom de l'épice, non obligatoire côté formulaire
            ->add('name', TextType::class, [
                'required' => false,
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
            'data_class' => Spice::class,
            'block_name' => 'form_spice_name',
            'block_prefix' => 'form_spice_prefix',
            'validation_groups' => ['registration1'],
            'csrf_protection' => false,
            'compound' => true,
            'required' => true
        ]);
    }
}