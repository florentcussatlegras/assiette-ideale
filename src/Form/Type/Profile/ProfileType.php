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
use App\Service\NutrientHandler;
use App\Service\FoodGroupHandler;
use App\Repository\HourRepository;
use Doctrine\ORM\EntityRepository;
use App\Repository\GenderRepository;
use Symfony\Component\Form\FormEvent;
use App\Repository\AgeRangeRepository;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use App\Repository\WorkingTypeRepository;
use App\Repository\SportingTimeRepository;
use App\Validator\Constraints as AppAssert;
use Symfony\Component\Security\Core\Security;
use App\Repository\PhysicalActivityRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class ProfileType extends AbstractType
{

    public function __construct(
            private GenderRepository $genderRepository, 
            private AgeRangeRepository $ageRangeRepository,
            private HourRepository $hourRepository,
            private WorkingTypeRepository $workingTypeRepository,
            private SportingTimeRepository $sportingTimeRepository,
            private PhysicalActivityRepository $physicalActivityRepository, 
            private RequestStack $requestStack, 
            private Security $security, 
            private EnergyHandler $energyHandler,
            private NutrientHandler $nutrientHandler,
            private FoodGroupHandler $foodGroupHandler,
    )
    {}

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var App\Entity\User $user */
        $user = $this->security->getUser();

        $validationGroups = sprintf('profile_%s', $options['element']);

        switch ($options['element']) {

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

            case ProfileHandler::GENDER:

                $builder->add('gender', EntityType::class, [
                    'label' => 'profile.gender.label2',
                    'class' => Gender::class,
                    'attr' => [
                        'class' => 'custom-select-profiles'
                    ],
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

            case ProfileHandler::AGE_RANGE:

                $builder->add('ageRange', EntityType::class, [
                    'label' => 'profile.age.label2',
                    'class' => AgeRange::class,
                    'attr' => [
                        'class' => 'custom-select-profiles'
                    ],
                    'translation_domain' => 'profile',
                    'choice_translation_domain' => 'profile',
                    'choice_label' => 'description',
                    'data' => null !== $user->getAgeRange() ? $user->getAgeRange() : $this->ageRangeRepository->findOneByCode(AgeRange::LESS_EIGHTEEN)
                ]);

                break;

            case ProfileHandler::HEIGHT:

                $builder->add('height', IntegerType::class, [
                    'label' => 'profile.height.label2',
                    'translation_domain' => 'profile',
                    'attr' => [
                        'class' => 'w-1/3 ml-2'
                    ],
                    'block_prefix' => 'profile_weight_height'
                ]);

                break;

            case ProfileHandler::WEIGHT:

                $builder->add('weight', IntegerType::class, [
                    'label' => 'profile.weight.label2',
                    'translation_domain' => 'profile',
                    'attr' => [
                        'class' => 'w-1/3 ml-2'
                    ],
                    'block_prefix' => 'profile_weight_height'
                ]);

                break;

            case ProfileHandler::HOURS:
       
                $builder->add('hour', EntityType::class, [
                    'class' => Hour::class,
                    'label' => 'profile.hour.label',
                    'attr' => [
                        'class' => 'custom-select-profiles'
                    ],
                    'translation_domain' => 'profile',
                    'choice_translation_domain' => 'profile',
                    'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('h')
                                ->orderBy('h.title', 'ASC')
                        ;
                    },
                    'data' => null !== $user->getHour() ? $user->getHour() : $this->hourRepository->findOneByAlias(Hour::NORMAL)
                ]);

                break;

            case ProfileHandler::WORK:

                $builder->add('workingType', EntityType::class, [
                    'class' => WorkingType::class,
                    'label' => 'profile.working_type.label',
                    'translation_domain' => 'profile',
                    'choice_translation_domain' => 'profile',
                    'attr' => [
                        'class' => 'custom-select-profiles'
                    ],
                    'data' => null !== $user->getWorkingType() ? $user->getWorkingType() : $this->workingTypeRepository->findOneByIsHard(WorkingType::SOFT)
                ]);

                break;

            case ProfileHandler::SPORT:

                $builder->add('sportingTime', EntityType::class, [
                    'class' => SportingTime::class,
                    'label' => 'profile.physical_activities.label',
                    'translation_domain' => 'profile',
                    'choice_translation_domain' => 'profile',
                    'attr' => [
                        'class' => 'custom-select-profiles'
                    ],
                    'data' => null !== $user->getSportingTime() ? $user->getSportingTime() : $this->sportingTimeRepository->findOneByDuration(SportingTime::NO_SPORT)
                ]);

                break;
            
            case ProfileHandler::DIETS: 

                $builder->add('diets', EntityType::class, [
                    'label' => 'profile.diets.label2',
                    'translation_domain' => 'profile',
                    'choice_translation_domain' => 'profile',
                    'attr' => [
                        'class' => 'custom-select-profiles'
                    ],
                    'class' => Diet::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'required' => false
                ]);

                break;

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

            case ProfileHandler::ENERGY:

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
                            ]
                        )
                        ->add('energy', IntegerType::class, [
                                'label' => false,
                                'required' => false,
                                'row_attr' => [
                                    'class' => 'w-full mb-2 energy flex flex-col'
                                ],
                            ]
                        )
                        ->add('unitMeasureEnergy', ChoiceType::class, [
                            'label' => false,
                            'mapped' => false,
                            'row_attr' => [
                                'class' => 'w-1/2 energy'
                            ],
                            'choices' => [
                                EnergyHandler::KCAL => EnergyHandler::KCAL, 
                                EnergyHandler::KJ => EnergyHandler::KJ,
                            ],
                            'constraints' => new Assert\Choice([
                                'choices' => [EnergyHandler::KCAL, EnergyHandler::KJ],
                                'message' => 'Veuillez saisir une unité d\'énergie'
                            ]),
                            'expanded' => true,
                            'data' => EnergyHandler::KCAL,
                            'block_prefix' => 'unit_energy'
                        ]
                    )
                ;

            break;

        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) use($options){

            if (ProfileHandler::ENERGY == $options["element"]) {

                $form = $event->getForm();
                $data = $event->getData();

                if(!$data['automaticCalculateEnergy'])
                {
                    // If user wich programm calculate energy, here we add constraint on energy value
                    // which limits depend of unit measure choice
                    $form->remove('energy');
                    $form->add(
                        'energy',
                        IntegerType::class,
                        [
                            'label' => false,
                            'required' => false,
                            'row_attr' => [
                                'class' => 'w-full mb-2 energy flex flex-col'
                            ],
                            'constraints' => [
                                new AppAssert\IsEnergyValid([
                                    'unitMeasure' => $data['unitMeasureEnergy'],
                                    'groups' => ['profile_energy']
                                ])
                            ]
                        ]
                    );
                    // on replace le champs des unités de mesure
                    // pour qu'il ne passe pas avant le champs de l'energie
                    $form->remove('unitMeasureEnergy');
                    $form->add(
                        'unitMeasureEnergy',
                        ChoiceType::class,
                        [
                            'label' => false,
                            'mapped' => false,
                            'row_attr' => [
                                'class' => 'flex items-center w-1/2 energy'
                            ],
                            'choices' => [
                                EnergyHandler::KCAL => EnergyHandler::KCAL,
                                EnergyHandler::KJ => EnergyHandler::KJ,
                            ],
                            'constraints' => new Assert\Choice([
                                'choices' => [EnergyHandler::KCAL, EnergyHandler::KJ],
                                'message' => 'Veuillez saisir une unité d\'énergie'
                            ]),
                            'expanded' => true,
                            'data' => EnergyHandler::KCAL,
                            'block_prefix' => 'unit_energy'
                        ]
                    );
                }
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) use($options){
            $user = $event->getData();
            $form = $event->getForm();
            if($form->has('workingType') || $form->has('sportingTime')) {
                if(null !== $physicalActivity = $this->physicalActivityRepository->findOneBy([
                    'workingType'  => $user->getWorkingType(),
                    'sportingTime' => $user->getSportingTime()
                ])) {
                    $user->setPhysicalActivity($physicalActivity->getValue());
                }
            }

            if(in_array($options["element"], EnergyHandler::PROFILE_LIST_NEEDED) || ProfileHandler::ENERGY == $options["element"]) {
               
                if(count($this->energyHandler->profileMissingForEnergy()) == 0) {

                    // on (re)calcule l'energie
                    $energyEstimate = $this->energyHandler->evaluateEnergy();
                    if($user->getAutomaticCalculateEnergy()) {
                        $user->setEnergy($energyEstimate);
                    }elseif(ProfileHandler::ENERGY == $options["element"] && EnergyHandler::KJ == $form->get('unitMeasureEnergy')->getData()) {
                        $user->setEnergy($form->get('energy')->getData() * EnergyHandler::MULTIPLICATOR_CONVERT_KJ_IN_KCAL);
                    }
                    $user->setEnergyCalculate($energyEstimate);
                    $user->setValueImc();
                    $user->setValueIdealWeight();
                    $user->setValueIdealImc();

                    // on (re)calcule les recommendations nutritionnels
                    $nutrientRecommendations = $this->nutrientHandler->getRecommendations();
                    $accessor = PropertyAccess::createPropertyAccessor();
                    foreach($nutrientRecommendations as $nutrientAlias => $value) {
                        $accessor->setValue($user, $nutrientAlias, $value);
                    }

                    // on (re)calcule les recommendations par groupe d'aliment
                    $user->setRecommendedQuantities($this->foodGroupHandler->getRecommendations());

                }

                // if($user->getAutomaticCalculateEnergy()) {
                //     try{
                //         $energyEstimate = $this->energyHandler->evaluateEnergy();
                //         $user->setEnergy($energyEstimate);
                //     }catch(MissingElementForEnergyEstimationException $e){
                //         // $this->request->getSession()->getFlashBag()->add('warning', $e->getMessage());
                //     }
                // }elseif(ProfileHandler::ENERGY == $options["element"] && EnergyHandler::KJ == $form->get('unitMeasureEnergy')->getData()) {
                //     $user->setEnergy($form->get('energy')->getData() * EnergyHandler::MULTIPLICATOR_CONVERT_KJ_IN_KCAL);
                // }
                // $user->setEnergyCalculate($energyEstimate);
            }
            $event->setData($user);
        });
    }

    /**
     * {@inheritdoc}
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

    public function getBlockPrefix(): string
    {
        return 'user_profile';
    }
}
