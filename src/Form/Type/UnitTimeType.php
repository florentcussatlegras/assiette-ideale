<?php

namespace App\Form\Type;

use App\Entity\UnitTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Repository\UnitTimeRepository;

/**
 * Formulaire pour sélectionner une unité de temps.
 * 
 * Utilise l'entité UnitTime et permet de choisir une unité (minutes, heures, etc.)
 * Les options par défaut incluent une classe CSS et une valeur initiale.
 */
class UnitTimeType extends AbstractType
{
    /**
     * Repository pour récupérer les unités de temps disponibles.
     *
     * @var UnitTimeRepository
     */
    private $unitTimeRepository;

    /**
     * Constructeur du formulaire.
     *
     * @param UnitTimeRepository $unitTimeRepository Repository pour accéder aux unités de temps
     */
    public function __construct(UnitTimeRepository $unitTimeRepository)
    {
        $this->unitTimeRepository = $unitTimeRepository;
    }

    /**
     * Construction du formulaire.
     *
     * Ici, aucun champ supplémentaire n'est ajouté, 
     * la configuration est gérée via configureOptions et le parent EntityType.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Aucun champ spécifique à ajouter pour le moment
    }

    /**
     * Configuration des options par défaut du formulaire.
     *
     * Définit l'entité utilisée, la valeur initiale, le label et les attributs CSS.
     *
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Classe CSS appliquée au champ
            'attr' => [
                'class' => 'form-select form-select-md w-24'
            ],
            // Entité associée
            'class' => UnitTime::class,
            // Fonction pour afficher le label des choix
            'choice_label' => function ($unitTime) {
                return $unitTime->getAlias();
            },
            // Valeur initiale par défaut
            'data' => $this->unitTimeRepository->findOneBy(['alias' => 'min']),
        ]);
    }

    /**
     * Retourne le type parent du formulaire.
     *
     * Ici, le champ hérite d'EntityType pour permettre la sélection depuis une entité Doctrine.
     *
     * @return string
     */
    public function getParent(): string
    {
        return EntityType::class;
    }
}