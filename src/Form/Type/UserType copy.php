<?php

namespace App\Form\Type;

use App\Entity\User;
use App\Entity\Hours;
use App\Entity\Gender;
use App\Entity\TypeMeal;
use App\Entity\Diet\Diet;
use App\Form\Type\DietType;
use App\Entity\Diet\SubDiet;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Validator\Constraints\IsUnique;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use App\Validator\Constraints\IsWeightValid;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use PUGX\AutocompleterBundle\Form\Type\AutocompleteType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class UserType extends AbstractType
{
    private $em;
    private $session;
    private $security;

    public function __construct(EntityManagerInterface $em, RequestStack $requestStack, Security $security)
    {
        $this->em = $em;
        $this->session = $requestStack->getSession();
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        if($options['fields'] == 'all' || $options['fields'] == 'register_profil')
        {

            $builder->add('birthday', BirthdayType::class, array(
                        'label' => 'Date de naissance',
                       'widget' => 'single_text',
                        'data' => new \DateTime(),
                        'attr' => [
                            'class' => 'form-control py-3 px-4 border-gray-300 block mb-4 w-60'
                        ],
                        'invalid_message' => 'La date de naissance est invalide.',
                        'constraints' => [
                            new Assert\NotBlank(
                                [
                                    'message' => 'Veuillez indiquer votre date de naissance.'
                                ]
                            )
                        ],
                        'choice_translation_domain' => 'month'
                    )
                )
                ->add('height', IntegerType::class, array(
                        'label' => 'Votre taille',
                        'attr' => [
                            'class' => 'form-control py-3 px-4 border-gray-300 block mb-4 w-20'
                        ],
                        'constraints' => array(
                            new Assert\NotBlank(array(
                                    'message' => 'Cette valeur ne doit pas être vide.'
                                )
                            ),
                            new Assert\Range(array(
                                    'min' => 25,
                                    'max' => 300,
                                    'minMessage' => 'Veuillez saisir une taille supérieure à 90',
                                    'maxMessage' => 'La taille indiquée est trop grande',
                             'notInRangeMessage' => 'Cette valeur n\'est pas valide.'
                                )
                            ),
                            new Assert\Type(array(
                                    'type' => 'integer',
                                    'message' => 'Cette valeur n\'est pas valide.',
                                )
                            )
                        )
                    )
                )
                ->add('weight', TextType::class, array(
                        'label' => 'Votre poids',
                        'attr' => [
                            'class' => 'form-control py-3 px-4 border-gray-300 block mb-4 w-20'
                        ],
                        'constraints' => array(
                            new Assert\NotBlank(array(
                                    'message' => 'Cette valeur ne doit pas être vide.'
                                )
                            )
                        )
                    )
                )
                ->add('snacks', EntityType::class, [
                            'class' => TypeMeal::class,
                         'multiple' => true,
                         'expanded' => true,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('t')
                                  ->where('t.isSnack = ?1')
                                  ->setParameter('1', 1)
                                  ->orderBy('t.rankTypeMeal', 'ASC')
                        ;
                    },
                     'choice_label' => 'frontName'
                ])
                ->add('diets', EntityType::class, [
                       'class' => Diet::class,
                    'multiple' => true,
                    'expanded' => true,
                'choice_label' => 'name',
                 'choice_attr' => function() {
                        return ['class' => 'profil_diet'];
                    }
                ])
            ;

            $user = $this->security->getUser();

            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($user) {

                if(!$user)
                {
                    $dataGender = $this->em->getRepository(Gender::class)->findOneByCode('M');
                    $dataHours = $this->em->getRepository(Hours::class)->findOneByCode('H_NORMAL');
                }else{
                    $dataGender = $user->getGender();
                    $dataHours = $user->getHours();
                }

                $form = $event->getForm();

                $form->add('gender', EntityType::class,
                        [
                            'class' => Gender::class,
                            'label' => 'Vous êtes',
                    'choice_label'  => 'longName',
                             'data' => $dataGender,
                         'expanded' => true,
                      'placeholder' => null,
                      'constraints' => new Assert\NotNull(
                                array('message' => 'Veuillez indiquer votre civilité.')
                            ),
                      'choice_attr' => function($choiceValue, $key, $value) {
                                return ['class' => 'gender'];
                            }
                        ]
                    )
                    ->add('hours', EntityType::class,
                        [
                               'label' => 'Vos horaires de travail',
                        'choice_label' => 'name',
                               'class' => Hours::class,
                               'attr'  => array('style' => 'width:250px'),
                            'expanded' => true,
                                'data' => $dataHours
                        ]
                    );

            }); 

        }

        if($options['fields'] == 'all' || $options['fields'] == 'register_energy')
        {

            $builder->add('programProvideEnergy', CheckboxType::class, array(
                    'label' => 'Laissez le programme estimer votre besoin énergétique',
                )
            )
                ->add('energyEstimate', IntegerType::class, array(
                        'label' => 'Besoin energétique journalier',
                        'required' => false
                    )
                )
            ;

            $optionsUnitMeasureEnergyEstimate = array(
                'choices' => array('kCal' => 'kcal', 'kJ' => 'kj'),
                'expanded' => true
            );

            if('register_energy' == $options['fields'])
                $optionsUnitMeasureEnergyEstimate['data'] = 'kcal';

            $builder->add('unitMeasureEnergyEstimate', ChoiceType::class, $optionsUnitMeasureEnergyEstimate);

        }

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
              'data_class' => User::class,
            'edit_profile' => false,
      'allow_extra_fields' => true,
                  'fields' => 'all'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'profil';
    }


}
