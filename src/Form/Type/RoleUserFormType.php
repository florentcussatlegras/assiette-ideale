<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use App\Entity\User;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire pour attribuer des rôles à un utilisateur.
 * Permet de sélectionner plusieurs rôles à partir d'un choix prédéfini.
 */
class RoleUserFormType extends AbstractType
{
    /**
     * Construction du formulaire.
     * Ajoute les champs cachés et les rôles via un champ de type choix.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire Symfony
     * @param array $options Options passées au formulaire
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Champ caché pour l'identifiant de l'utilisateur
            ->add('id', HiddenType::class)
            // Champ permettant de sélectionner plusieurs rôles
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Administrateur' => 'ROLE_ADMIN', // Rôle d’administrateur
                    'Peut imiter un utilisateur' => 'ROLE_ALLOWED_TO_SWITCH', // Rôle permettant de switcher vers un autre utilisateur
                    'Peut administrer les rôles' => 'ROLE_ADMIN_ROLE', // Rôle pour administrer les droits
                ],
                'multiple' => true, // Permet de sélectionner plusieurs rôles en même temps
            ]);
    }

    /**
     * Configuration des options du formulaire.
     * Lie le formulaire à l'entité User.
     *
     * @param OptionsResolver $resolver Le résolveur d'options Symfony
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class // Les données du formulaire seront liées à l’entité User
        ]);
    }
}