<?php

namespace App\Form\Type;

use App\Entity\Dish;
use App\Entity\User;
use App\Entity\Spice;
use App\Service\RecipeLevel;
use Doctrine\ORM\EntityRepository;
use App\Repository\SpiceRepository;
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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use App\Form\Type\TypeDishType;

/**
 * Formulaire pour créer ou éditer un plat (Dish)
 *
 * Gère tous les champs nécessaires à la création d'une recette, y compris :
 * - nom, type, niveau
 * - temps de préparation et cuisson
 * - étapes de recette
 * - aliments et quantités
 * - table nutritionnelle
 * - épices
 * - image
 * 
 * Le formulaire est prérempli depuis la session si une recette est en cours d'édition.
 */
class DishType extends AbstractType
{
    /**
     * DishType constructor.
     *
     * @param UnitTimeRepository $unitTimeRepository
     * @param FoodGroupRepository $foodGroupRepository
     * @param RequestStack $requestStack
     * @param SpiceRepository $spiceRepository
     */
    public function __construct(
            private UnitTimeRepository $unitTimeRepository, 
            private FoodGroupRepository $foodGroupRepository, 
            private RequestStack $requestStack, 
            private SpiceRepository $spiceRepository
        )
    {}

    /**
     * Construction du formulaire.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $session = $this->requestStack->getSession();
        $dish = $options['data'];

        // Si une recette est en cours d'édition en session, on utilise ses valeurs
        if ($session->has('recipe_dish')) {
            $level = $session->get('recipe_dish')->getLevel();
            $type = $session->get('recipe_dish')->getType();
        } else {
            // Sinon on utilise les valeurs de l'entité Dish
            $level = !$dish->getLevel() ? 'recipe.level.easy' : $dish->getLevel();
            $type = !$dish->getType() ? 'dish.type.entry' : $dish->getLevel();
        }

        // Nom du plat
        $builder->add('name', TextType::class, [
            'data' => $session->has('recipe_dish') ? $session->get('recipe_dish')->getName() : $dish->getName(),
            'attr' => [
                'class' => 'w-full rounded-lg',
                'placeholder' => 'Blanquette de veau, Tarte aux figues...'
            ]
        ]);

        // Type de plat (entrée, plat, dessert...) avec TypeDishType
        $builder->add('type', TypeDishType::class, [
            'expanded' => true,
            'block_prefix' => 'dish_horizontal_choices',
            'choice_translation_domain' => 'dishes',
            'data' => $type,
        ]);

        // Niveau de difficulté
        $builder->add('level', ChoiceType::class, [
            'label' => 'Niveau de difficulté',
            'label_attr' => ['class' => 'font-normal'],
            'choices' => RecipeLevel::getLevels(),
            'expanded' => true,
            'choice_translation_domain' => 'recipe',
            'data' => $level,
            'block_prefix' => 'dish_horizontal_choices'
        ]);

        // Nombre de personnes pour la recette
        $builder->add('lengthPersonForRecipe', IntegerType::class, [
            'data' => $session->has('recipe_dish') ? $session->get('recipe_dish')->getLengthPersonForRecipe() : $dish->getLengthPersonForRecipe(),
            'attr' => ['class' => 'w-24 rounded-lg', 'placeholder' => 1]
        ]);

        // Validation: la recette doit contenir au moins un aliment
        $builder->add('dishFoodList', null, [
            'mapped' => false,
            'constraints' => [
                new MyAssert\ContainsFood(['groups' => ['AddOrEdit']])
            ]
        ]);

        // Étapes de la recette (CollectionType)
        $builder->add('stepRecipes', CollectionType::class, [
            'entry_type' => StepRecipeType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'data' => $session->has('recipe_dish') ? $session->get('recipe_dish')->getStepRecipes()->toArray() : $dish->getStepRecipes()
        ]);

        // Temps de préparation
        $builder->add('preparationTime', null, [
            'data' => $session->has('recipe_dish') ? $session->get('recipe_dish')->getPreparationTime() : $dish->getPreparationTime(),
            'attr' => ['class' => 'w-24 rounded-lg']
        ]);

        // Unité de temps pour la préparation
        $builder->add('preparationTimeUnitTime', UnitTimeType::class, [
            'data' => $session->has('recipe_dish') ? $this->unitTimeRepository->findOneBy(['id' => $session->get('recipe_dish')->getPreparationTimeUnitTime()->getId()]) : $dish->getPreparationTimeunitTime(),
            'attr' => ['class' => 'rounded-lg']
        ]);

        // Temps de cuisson
        $builder->add('cookingTime', null, [
            'data' => $session->has('recipe_dish') ? $session->get('recipe_dish')->getCookingTime() : $dish->getCookingTime(),
            'attr' => ['class' => 'w-24 rounded-lg']
        ]);

        // Unité de temps pour la cuisson
        $builder->add('cookingTimeUnitTime', UnitTimeType::class, [
            'data' => $session->has('recipe_dish') ? $this->unitTimeRepository->findOneBy(['id' => $session->get('recipe_dish')->getCookingTimeUnitTime()->getId()]) : $dish->getCookingTimeunitTime(),
            'attr' => ['class' => 'rounded-lg']
        ]);

        // Table nutritionnelle
        $builder->add('nutritionalTable', NutritionalTableType::class, [
            'data' => $session->has('recipe_dish') ? $session->get('recipe_dish')->getNutritionalTable() : $dish->getNutritionalTable()
        ]);

        // Épices
        $builder->add('spices', EntityType::class, [
            'class' => Spice::class,
            'choices' => $this->spiceRepository->findBy([], ['name' => 'ASC']),
            'attr' => ['class' => 'flex gap-2'],
            'multiple' => true,
            'expanded' => true,
        ]);

        // Champ caché pour suppression
        $builder->add('picRankForDelete', HiddenType::class, ['mapped' => false]);

        // Choix de l'utilisateur (pour les tests / assignation de créateur)
        $builder->add('user', EntityType::class, [
            'class' => User::class,
            'choice_label' => fn($user) => $user->getUsername() . ' - ' . $user->getId(),
            'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('u')->orderBy('u.username', 'ASC')
        ]);

        // Bouton de sauvegarde
        $builder->add('saveAndAdd', SubmitType::class, [
            'label_html' => true,
            'validation_groups' => 'AddOrEdit'
        ]);

        // Quantités d'aliments (formulaire secondaire)
        $builder->add('quantityFood', QuantityFoodFormType::class, [
            'mapped' => false,
            'validation_groups' => false
        ]);

        // Bouton pour ajouter les quantités
        $builder->add('saveQuantityFood', SubmitType::class, [
            'label_html' => true,
            'label' => 'Ajouter',
            'attr' => ['class' => 'px-4 py-2 rounded bg-dark-blue text-white font-bold'],
            'validation_groups' => false
        ]);

        // Gestion des rangs des étapes avant soumission
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

        // Upload d'image via Dropzone
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
            'constraints' => new Assert\File([
                'maxSize' => '5M',
                'mimeTypes' => ['jpeg', 'jpg', 'gif'],
                'mimeTypesMessage' => 'Merci de choisir une image valide',
            ])
        ]);

        // Ajout de boutons submit par groupe alimentaire
        foreach($this->foodGroupRepository->findAll() as $foodGroup) {
            $builder->add(sprintf('redirectTo%s', $foodGroup->getAlias()), DishFoodGroupSubmitType::class, [
                'label' => $foodGroup->getName(),
                'food_group' => $foodGroup,
                'validation_groups' => false
            ]);
        }
    }

    /**
     * Configuration des options du formulaire.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Dish::class,
            'allow_extra_fields' => true,
            'csrf_token_id' => 'new_dish_recipe_31'
        ]);
    }
}