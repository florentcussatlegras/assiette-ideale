<?php

namespace App\Form\Type;

use App\Entity\Dish;
use App\Entity\User;
use App\Entity\Spice;
use App\Service\RecipeLevel;
use Doctrine\ORM\EntityRepository;
use App\Repository\SpiceRepository;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use App\Repository\UnitTimeRepository;
use Symfony\Component\Form\FormEvents;
use App\Form\Type\NutritionalTableType;
use App\Form\Type\QuantityFoodFormType;
use App\Repository\FoodGroupRepository;
use Symfony\Component\Form\AbstractType;
use App\Form\Type\DishFoodGroupSubmitType;
use App\Validator\Constraints as MyAssert;
use Symfony\UX\Dropzone\Form\DropzoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use App\Form\Type\TypeDishType;

class DishType extends AbstractType
{
    private $unitTimeRepository;
    private $spiceRepository;
    private $foodGroupRepository;
    private $session;
    private $requestStack;

    public function __construct(UnitTimeRepository $unitTimeRepository, 
                FoodGroupRepository $foodGroupRepository, RequestStack $requestStack, SpiceRepository $spiceRepository)
    {
        $this->unitTimeRepository = $unitTimeRepository;
        $this->requestStack = $requestStack;
        $this->foodGroupRepository = $foodGroupRepository;
        $this->spiceRepository = $spiceRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $session = $this->requestStack->getSession();

        $dish = $options['data'];

        //$isEdit = $dish && $dish->getId();
        if ($session->has('recipe_dish'))
        {
            // dump($session->get('recipe_dish'));
            $level = $session->get('recipe_dish')->getLevel();
            $type = $session->get('recipe_dish')->getType();
        }else{
            $level = !$dish->getLevel() ? 'recipe.level.easy' : $dish->getLevel();
            $type = !$dish->getType() ? 'dish.type.entry' : $dish->getLevel();
        }
        // dd($type);

        $builder
            ->add('name', TextType::class, [
                'data' => $session->has('recipe_dish') ? $session->get('recipe_dish')->getName() : $dish->getName(),
                'attr' => [
                    'class' => 'w-full rounded-lg',
                    'placeholder' => 'Blanquette de veau, Tarte aux figues...'
                ]
            ])
            ->add('type', TypeDishType::class, [
                'expanded' => true,
                'block_prefix' => 'dish_horizontal_choices',
                'choice_translation_domain' => 'dishes',
                'data' => $type,
            ])
            ->add('level', ChoiceType::class, [
                'label' => 'Niveau de difficulté',
                'label_attr' => [
                    'class' => 'font-normal'
                ],
                'choices' => RecipeLevel::getLevels(),
                'expanded' => true,
                'choice_translation_domain' => 'recipe',
                'data' => $level,
                'block_prefix' => 'dish_horizontal_choices'
            ])
            ->add('lengthPersonForRecipe', IntegerType::class, [
                'data' => $session->has('recipe_dish') ? $session->get('recipe_dish')->getLengthPersonForRecipe() : $dish->getLengthPersonForRecipe(),
                'attr' => [
                    'class' => 'w-24 rounded-lg',
                    'placeholder' => 1
                ]
            ])
            ->add('dishFoodList', null, [
                'mapped' => false,
                'constraints' => [
                    new MyAssert\ContainsFood(['groups' => ['AddOrEdit']])
                ]
            ])
            ->add('stepRecipes', CollectionType::class, [
                'entry_type' => StepRecipeType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'data' => $session->has('recipe_dish') ? $session->get('recipe_dish')->getStepRecipes()->toArray() : $dish->getStepRecipes()
            ])
            ->add('preparationTime', null, [
                'data' => $session->has('recipe_dish') ? $session->get('recipe_dish')->getPreparationTime() : $dish->getPreparationTime(),
                'attr' => [
                    'class' => 'w-24 rounded-lg'
                ]
            ])
            ->add('preparationTimeUnitTime', UnitTimeType::class, [
                'data' => $session->has('recipe_dish') ? $this->unitTimeRepository->findOneBy(['id' => $session->get('recipe_dish')->getPreparationTimeUnitTime()->getId()]) : $dish->getPreparationTimeunitTime(),
                'attr' => [
                    'class' => 'rounded-lg'
                ]
            ])
            ->add('cookingTime', null, [
                'data' => $session->has('recipe_dish') ? $session->get('recipe_dish')->getCookingTime() : $dish->getCookingTime(),
                'attr' => [
                    'class' => 'w-24 rounded-lg'
                ]
            ])
            ->add('cookingTimeUnitTime', UnitTimeType::class, [
                'data' => $session->has('recipe_dish') ? $this->unitTimeRepository->findOneBy(['id' => $session->get('recipe_dish')->getCookingTimeUnitTime()->getId()]) : $dish->getCookingTimeunitTime(),
                'attr' => [
                    'class' => 'rounded-lg'
                ]
            ])
            ->add('nutritionalTable', NutritionalTableType::class, [
                'data' => $session->has('recipe_dish') ? $session->get('recipe_dish')->getNutritionalTable() 
                                                            : $dish->getNutritionalTable()
            ])
            ->add('spices', EntityType::class, [
                'class' => Spice::class,
                'choices' => $this->spiceRepository->findBy([], ['name' => 'ASC']),
                'attr' => [
                    'class' => 'flex gap-2'
                ],
                'multiple' => true,
                'expanded' => true,
                ])
            ->add('picRankForDelete', HiddenType::class, [
                'mapped' => false
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function($user) {
                    return $user->getUsername() . ' - ' . $user->getId();
                },
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                            ->orderBy('u.username', 'ASC');
                }
            ])
            ->add('saveAndAdd', SubmitType::class, [
                'label_html' => true,
                'validation_groups' => 'AddOrEdit'
            ])
            ->add('quantityFood', QuantityFoodFormType::class, [
                'mapped' => false,
                'validation_groups' => false
            ])
            ->add('saveQuantityFood', SubmitType::class, [
                'label_html' => true,
                'label' => 'Ajouter',
                'attr' => [
                    'class' => 'px-4 py-2 rounded bg-dark-blue text-white font-bold'
                ],
                'validation_groups' => false
            ])
        ;

        $imageConstraints = [
            new Assert\All([
                new Assert\Image([
                    'maxSize' => '5M'      
                ])
            ])
        ];

        $builder->get('stepRecipes')->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event){
            $form = $event->getForm();
            $steps = $event->getData();
            if(!empty($steps)) {
                $rank = 1;
                foreach($steps as $key => $step) {
                    $steps[$key]['rankStep'] = $rank;
                    $rank++;
                }
            }
            $event->setData($steps);
        });


        // if(
        //     ($session->has('recipe_pictures') && empty($session->get('recipe_pictures'))) 
        //         && 
        //     empty($dish->getPictures()->toArray())
        // ) {
            // $imageConstraints[] = new Assert\NotBlank([
            //     'message' => 'Veuillez sélectionner une image.'
            // ]);
        //}

        // $builder->add('picturesFile', FileType::class, [
        //         'label' => false,
        //         'mapped' => false,
        //         'required' => false,
        //         'multiple' => true,
        //         'constraints' => $imageConstraints
        //     ]
        // );

        $builder->add('pictureFile', DropzoneType::class, [
            'attr' => [
                'placeholder' => 'Drag and drop a file or click to browse',
                'data-controller' => 'mydropzone',
                'class' => 'bg-white'
            ],
            'label' => false,
            'mapped' => false,
            'required' => false,
            'multiple' => false,
            // 'constraints' => $imageConstraints
            'constraints' => new Assert\File([
                    'maxSize' => '5M',
                    'mimeTypes' => ['jpeg', 'jpg', 'gif'],
                    'mimeTypesMessage' => 'Merci de choisir une image valide',
                ])
            ],
        );

        foreach($this->foodGroupRepository->findAll() as $foodGroup) {
            $builder->add(sprintf('redirectTo%s', $foodGroup->getAlias()), DishFoodGroupSubmitType::class, [
                'label' => $foodGroup->getName(),
                'food_group' => $foodGroup,
                'validation_groups' => false
            ]);
        }












        // $builder->addEventListener(
        //         FormEvents::PRE_SUBMIT, 
        //         function(FormEvent $event) use($session){
        //             if(!$session->has('recipe_foods') || empty($session->get('recipe_foods'))) {
        //                 $form = $event->getForm();
        //                 $form->addError(new FormError('Veuillez choisir au minimum un aliment.'));
        //             }
        //         }
        // );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Dish::class,
            'allow_extra_fields' => true,
            'csrf_token_id' => 'new_dish_recipe_31'
        ]);
    }
}

