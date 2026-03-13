<?php

namespace App\Form\Type\Profile;

use App\Entity\Food;
use App\Entity\Hour;
use App\Entity\User;
use App\Entity\Gender;
use App\Entity\AgeRange;
use App\Entity\Diet\Diet;
use App\Entity\WorkingType;
use App\Entity\SportingTime;
use App\Service\EnergyHandler;
use App\Service\ProfileHandler;
use App\Repository\HourRepository;
use Doctrine\ORM\EntityRepository;
use App\Repository\GenderRepository;
use Symfony\Component\Form\FormEvent;
use App\Repository\AgeRangeRepository;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use App\Repository\WorkingTypeRepository;
use App\Repository\SportingTimeRepository;
use Symfony\Component\Security\Core\Security;
use App\Repository\PhysicalActivityRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

/**
 * Formulaire de gestion du profil utilisateur.
 *
 * Ce FormType est dynamique : il permet de construire différents éléments du profil
 * selon la valeur de l'option "element" (nom, email, image, âge, genre, poids, taille, énergie, régimes, etc.).
 * 
 * Le formulaire applique également des contraintes de validation conditionnelles et des écouteurs d'événements
 * pour recalculer l'énergie, gérer les activités physiques, et mettre à jour le profil utilisateur.
 */
class ProfileType extends AbstractType
{
    /**
     * Constructeur injectant les dépendances nécessaires pour construire le formulaire.
     *
     * @param GenderRepository $genderRepository
     * @param AgeRangeRepository $ageRangeRepository
     * @param HourRepository $hourRepository
     * @param WorkingTypeRepository $workingTypeRepository
     * @param SportingTimeRepository $sportingTimeRepository
     * @param Security $security
     */
    public function __construct(
        private GenderRepository $genderRepository, 
        private AgeRangeRepository $ageRangeRepository,
        private HourRepository $hourRepository,
        private WorkingTypeRepository $workingTypeRepository,
        private SportingTimeRepository $sportingTimeRepository,
        private Security $security, 
    )
    {}

    /**
     * Construit le formulaire selon l'élément spécifié dans l'option "element".
     *
     * Chaque "case" du switch correspond à un élément du profil : nom, email, image, genre, âge,
     * poids, taille, horaires, travail, sport, régimes, aliments interdits ou besoins énergétiques.
     *
     * Le formulaire ajoute également des écouteurs PRE_SUBMIT et POST_SUBMIT :
     *  - PRE_SUBMIT : ajuste les contraintes d'énergie si l'utilisateur saisit ses propres valeurs
     *  - POST_SUBMIT : calcule la valeur énergétique, met à jour l'activité physique et recalcule le profil
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Récupère l'utilisateur courant
        /** @var App\Entity\User $user */
        $user = $this->security->getUser();

        // Détermine le groupe de validation spécifique à l'élément
        $validationGroups = sprintf('profile_%s', $options['element']);

        // Switch dynamique selon l'élément à afficher dans le formulaire
        switch ($options['element']) {

            // ------------------------
            // Champs par défaut : nom, email, image
            // ------------------------
            default:
                $builder
                    ->add('username', TextType::class, [
                        'label' => 'Votre nom',
                        'validation_groups' => [$validationGroups],
                    ])
                    ->add('email', EmailType::class, [
                        'label' => 'Votre adresse email',
                        'validation_groups' => [$validationGroups],
                    ])
                    ->add('pictureFile', FileType::class, [
                        'label' => 'Votre image',
                        'required' => false,
                        'mapped' => false,
                        'constraints' => [
                            new Assert\Image([
                                'minHeight' => 5,
                                'minHeightMessage' => 'La hauteur de l\'image est trop petite. Le minimum souhaité est {{ min_height }}px.',
                                'maxSize' => '5M',
                                'maxSizeMessage' => 'Le fichier est trop volumineux. La taille maximum autorisée est de {{ limit }}{{ suffix }}'
                            ])
                        ],
                        'block_prefix' => 'user_profile_image',
                        'image_property' => 'picturePath',
                        'validation_groups' => [$validationGroups],
                    ])
                ;
                break;

            // ------------------------
            // Élément "genre"
            // ------------------------
            case ProfileHandler::GENDER:
                // Champ select pour choisir le genre de l'utilisateur
                $builder->add('gender', EntityType::class, [
                    'label' => 'profile.gender.label2',
                    'class' => Gender::class,
                    'attr' => ['class' => 'custom-select-profiles'],
                    'translation_domain' => 'profile',
                    'choice_translation_domain' => 'profile', 
                    'choice_label' => 'longName',
                    'query_builder' => function(EntityRepository $er){
                        return $er->createQueryBuilder('g')
                            ->orderBy('g.name', 'DESC');
                    },
                    'data' => null !== $user->getGender() ? $user->getGender() : $this->genderRepository->findOneByAlias(Gender::MALE)
                ]);
                break;

            // ------------------------
            // Élément "tranche d'âge"
            // ------------------------
            case ProfileHandler::AGE_RANGE:
                // Champ select pour choisir la tranche d'âge
                $builder->add('ageRange', EntityType::class, [
                    'label' => 'profile.age.label2',
                    'class' => AgeRange::class,
                    'attr' => ['class' => 'custom-select-profiles'],
                    'translation_domain' => 'profile',
                    'choice_translation_domain' => 'profile',
                    'choice_label' => 'description',
                    'data' => null !== $user->getAgeRange() ? $user->getAgeRange() : $this->ageRangeRepository->findOneByCode(AgeRange::LESS_EIGHTEEN)
                ]);
                break;

            // ------------------------
            // Élément "taille"
            // ------------------------
            case ProfileHandler::HEIGHT:
                $builder->add('height', IntegerType::class, [
                    'label' => 'profile.height.label2',
                    'translation_domain' => 'profile',
                    'attr' => ['class' => 'w-1/3 ml-2'],
                    'block_prefix' => 'profile_weight_height'
                ]);
                break;

            // ------------------------
            // Élément "poids"
            // ------------------------
            case ProfileHandler::WEIGHT:
                $builder->add('weight', IntegerType::class, [
                    'label' => 'profile.weight.label2',
                    'translation_domain' => 'profile',
                    'attr' => ['class' => 'w-1/3 ml-2'],
                    'block_prefix' => 'profile_weight_height'
                ]);
                break;

            // ------------------------
            // Élément "horaires"
            // ------------------------
            case ProfileHandler::HOURS:
                $builder->add('hour', EntityType::class, [
                    'class' => Hour::class,
                    'label' => 'profile.hour.label',
                    'attr' => ['class' => 'custom-select-profiles'],
                    'translation_domain' => 'profile',
                    'choice_translation_domain' => 'profile',
                    'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('h')
                                ->orderBy('h.title', 'ASC');
                    },
                    'data' => null !== $user->getHour() ? $user->getHour() : $this->hourRepository->findOneByAlias(Hour::NORMAL)
                ]);
                break;

            // ------------------------
            // Élément "type de travail"
            // ------------------------
            case ProfileHandler::WORK:
                $builder->add('workingType', EntityType::class, [
                    'class' => WorkingType::class,
                    'label' => 'profile.working_type.label',
                    'translation_domain' => 'profile',
                    'choice_translation_domain' => 'profile',
                    'attr' => ['class' => 'custom-select-profiles'],
                    'data' => null !== $user->getWorkingType() ? $user->getWorkingType() : $this->workingTypeRepository->findOneByIsHard(WorkingType::SOFT)
                ]);
                break;

            // ------------------------
            // Élément "activité sportive"
            // ------------------------
            case ProfileHandler::SPORT:
                $builder->add('sportingTime', EntityType::class, [
                    'class' => SportingTime::class,
                    'label' => 'profile.physical_activities.label',
                    'translation_domain' => 'profile',
                    'choice_translation_domain' => 'profile',
                    'attr' => ['class' => 'custom-select-profiles'],
                    'data' => null !== $user->getSportingTime() ? $user->getSportingTime() : $this->sportingTimeRepository->findOneByDuration(SportingTime::NO_SPORT)
                ]);
                break;

            // ------------------------
            // Élément "régimes"
            // ------------------------
            case ProfileHandler::DIETS: 
                $builder->add('diets', EntityType::class, [
                    'label' => 'profile.diets.label2',
                    'translation_domain' => 'profile',
                    'choice_translation_domain' => 'profile',
                    'attr' => ['class' => 'custom-select-profiles'],
                    'class' => Diet::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'required' => false
                ]);
                break;

            // ------------------------
            // Élément "aliments interdits"
            // ------------------------
            case ProfileHandler::FORBIDDEN_FOODS:
                $builder->add('forbiddenFoods', EntityType::class, [
                    'label' => 'profile.forbidden_foods.label2',
                    'translation_domain' => 'profile',
                    'class' => Food::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'required' => false,
                    'autocomplete' => true
                ]);
                break;

            // ------------------------
            // Élément "énergie"
            // ------------------------
            case ProfileHandler::ENERGY:
                // Gestion automatique ou manuelle du calcul énergétique
                $builder->add('automaticCalculateEnergy', ChoiceType::class, [
                    'label' => 'profile.energy_needs.label2',
                    'choices' => [
                        'profile.energy_needs.value.automatic' => true,
                        'profile.energy_needs.value.personal' => false,
                    ],
                    'choice_attr' => [
                        'profile.energy_needs.value.automatic' => ['id' => 'energy_calculator_auto'],
                        'profile.energy_needs.value.personal' => ['id' => 'energy_calculator_perso'],
                    ],
                    'translation_domain' => 'profile',
                    'choice_translation_domain' => 'profile',
                    'expanded' => true,
                    'data' => null !== $user->getAutomaticCalculateEnergy() ? $user->getAutomaticCalculateEnergy() : true
                ])
                // Champ énergie et unité de mesure
                ->add('energy', IntegerType::class, [
                    'label' => false,
                    'required' => false,
                    'row_attr' => ['class' => 'w-full mb-2 energy flex flex-col'],
                ])
                ->add('unitMeasureEnergy', ChoiceType::class, [
                    'label' => false,
                    'mapped' => false,
                    'row_attr' => ['class' => 'w-1/2 energy'],
                    'choices' => [EnergyHandler::KCAL => EnergyHandler::KCAL, EnergyHandler::KJ => EnergyHandler::KJ],
                    'constraints' => new Assert\Choice([
                        'choices' => [EnergyHandler::KCAL, EnergyHandler::KJ],
                        'message' => 'Veuillez saisir une unité d\'énergie'
                    ]),
                    'expanded' => true,
                    'data' => EnergyHandler::KCAL,
                    'block_prefix' => 'unit_energy'
                ]);
                break;
        }

        // ------------------------
        // Événement PRE_SUBMIT : adapte la validation en fonction du type d'énergie choisi
        // ------------------------
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) use($options){
            // ...
        });

        // ------------------------
        // Événement POST_SUBMIT : recalcul l'énergie et l'activité physique après soumission
        // ------------------------
        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) use($options){
            // ...
        });
    }

    /**
     * Configure les options par défaut du formulaire
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
              'data_class' => User::class,
              'element' => null,
              'allow_extra_field' => true,
              'csrf_protection' => true,
              'csrf_field_name' => '_token_profile',
              'csrf_token_id' => 'profile',
        ]);

        $resolver->setAllowedTypes('element', ['string', 'null']);
    }

    /**
     * Préfixe des blocs Twig pour ce formulaire
     *
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'user_profile';
    }
}