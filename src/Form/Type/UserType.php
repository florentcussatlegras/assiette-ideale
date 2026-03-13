<?php

namespace App\Form\Type;

use App\Entity\User;
use App\Entity\Hour;
use App\Entity\Gender;
use App\Entity\TypeMeal;
use App\Entity\Diet\Diet;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Security;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * Formulaire pour gérer l'utilisateur.
 *
 * Ce FormType est utilisé pour l'inscription, la création ou la modification d'un profil utilisateur.
 * Il est dynamique et permet de construire différents champs selon l'option 'fields' :
 * - 'all' : tous les champs
 * - 'register_profil' : profil de base (taille, poids, civilité, horaires, snacks, régimes)
 * - 'register_energy' : champs liés à l'énergie (besoin énergétique, unité)
 */
class UserType extends AbstractType
{
    /**
     * EntityManager pour accéder aux entités et aux repositories.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Service Security pour récupérer l'utilisateur connecté.
     *
     * @var Security
     */
    private $security;

    /**
     * Constructeur du formulaire.
     *
     * @param EntityManagerInterface $em
     * @param RequestStack $requestStack
     * @param Security $security
     */
    public function __construct(EntityManagerInterface $em, RequestStack $requestStack, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    /**
     * Construction dynamique du formulaire selon l'option 'fields'.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // ------------------------
        // Champs profil de base : birthday, height, weight, snacks, diets, gender, hours
        // ------------------------
        if ($options['fields'] === 'all' || $options['fields'] === 'register_profil') {

            // Date de naissance
            $builder->add('birthday', BirthdayType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'data' => new \DateTime(),
                'attr' => ['class' => 'form-control py-3 px-4 border-gray-300 block mb-4 w-60'],
                'invalid_message' => 'La date de naissance est invalide.',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez indiquer votre date de naissance.'
                    ])
                ],
                'choice_translation_domain' => 'month',
            ]);

            // Taille de l'utilisateur
            $builder->add('height', IntegerType::class, [
                'label' => 'Votre taille',
                'attr' => ['class' => 'form-control py-3 px-4 border-gray-300 block mb-4 w-20'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Cette valeur ne doit pas être vide.']),
                    new Assert\Range([
                        'min' => 25,
                        'max' => 300,
                        'minMessage' => 'Veuillez saisir une taille supérieure à 90',
                        'maxMessage' => 'La taille indiquée est trop grande',
                        'notInRangeMessage' => 'Cette valeur n\'est pas valide.'
                    ]),
                    new Assert\Type(['type' => 'integer', 'message' => 'Cette valeur n\'est pas valide.']),
                ],
            ]);

            // Poids de l'utilisateur
            $builder->add('weight', TextType::class, [
                'label' => 'Votre poids',
                'attr' => ['class' => 'form-control py-3 px-4 border-gray-300 block mb-4 w-20'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Cette valeur ne doit pas être vide.'])
                ],
            ]);

            // Snacks (TypeMeal)
            $builder->add('snacks', EntityType::class, [
                'class' => TypeMeal::class,
                'multiple' => true,
                'expanded' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                              ->where('t.isSnack = ?1')
                              ->setParameter('1', 1)
                              ->orderBy('t.rankTypeMeal', 'ASC');
                },
                'choice_label' => 'frontName'
            ]);

            // Régimes alimentaires
            $builder->add('diets', EntityType::class, [
                'class' => Diet::class,
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
                'choice_attr' => function () {
                    return ['class' => 'profil_diet'];
                }
            ]);

            // ------------------------
            // Événement PRE_SET_DATA pour remplir genre et horaires
            // ------------------------
            $user = $this->security->getUser();
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($user) {

                if (!$user) {
                    $dataGender = $this->em->getRepository(Gender::class)->findOneByCode('M');
                    $dataHours = $this->em->getRepository(Hour::class)->findOneByCode('H_NORMAL');
                } else {
                    $dataGender = $user->getGender();
                    $dataHours = $user->getHours();
                }

                $form = $event->getForm();

                // Genre (civilité)
                $form->add('gender', EntityType::class, [
                    'class' => Gender::class,
                    'label' => 'Vous êtes',
                    'choice_label' => 'longName',
                    'data' => $dataGender,
                    'expanded' => true,
                    'placeholder' => null,
                    'constraints' => new Assert\NotNull([
                        'message' => 'Veuillez indiquer votre civilité.'
                    ]),
                    'choice_attr' => function ($choiceValue, $key, $value) {
                        return ['class' => 'gender'];
                    }
                ]);

                // Horaires de travail
                $form->add('hours', EntityType::class, [
                    'class' => Hour::class,
                    'label' => 'Vos horaires de travail',
                    'choice_label' => 'name',
                    'attr' => ['style' => 'width:250px'],
                    'expanded' => true,
                    'data' => $dataHours
                ]);
            });
        }

        // ------------------------
        // Champs liés à l'énergie : estimation du besoin énergétique
        // ------------------------
        if ($options['fields'] === 'all' || $options['fields'] === 'register_energy') {

            // Checkbox pour estimation automatique
            $builder->add('programProvideEnergy', CheckboxType::class, [
                'label' => 'Laissez le programme estimer votre besoin énergétique',
            ]);

            // Champ pour saisir la valeur énergétique
            $builder->add('energyEstimate', IntegerType::class, [
                'label' => 'Besoin energétique journalier',
                'required' => false
            ]);

            // Unité d'énergie (kCal ou kJ)
            $optionsUnitMeasureEnergyEstimate = [
                'choices' => ['kCal' => 'kcal', 'kJ' => 'kj'],
                'expanded' => true
            ];

            if ('register_energy' === $options['fields']) {
                $optionsUnitMeasureEnergyEstimate['data'] = 'kcal';
            }

            $builder->add('unitMeasureEnergyEstimate', ChoiceType::class, $optionsUnitMeasureEnergyEstimate);
        }
    }

    /**
     * Configuration des options par défaut du formulaire.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'edit_profile' => false,
            'allow_extra_fields' => true,
            'fields' => 'all'
        ]);
    }

    /**
     * Préfixe du formulaire pour les templates Twig.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'profil';
    }
}