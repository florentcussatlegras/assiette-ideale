<?php  

namespace App\Form\Type;

use App\Entity\UnitMeasure;
use App\Service\QuantityHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class QuantityFoodFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('foodId', HiddenType::class, [
                'data' => isset($options['idFood']) ? $options['idFood'] : null
            ])
            ->add('quantity', TextType::class, [
                'attr' => [
                    'placeholder' => 'Quantité'
                ]
            ])
            // ->add('unitMeasure', ChoiceType::class, [
            //     'choices' => $this->quantityHandler->getUnitMeasureList(),
            //     'choice_label' => 'alias',
            //     'attr' => [
            //         'class' => 'form-select form-select-lg rounded-none mt-1'
            //     ],
            // ])
            ->add('unitMeasure', EntityType::class, [
                'class' => UnitMeasure::class,
                'choice_label' => 'alias',
                // 'query_builder' => function(EntityRepository $er){
                //     return $er->createQueryBuilder('u')
                //                 ->orderBy()
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('idFood');
        $resolver->setAllowedTypes('idFood', ['null', 'integer', 'string']);
    }

    // public function configureOptions(OptionsResolver $resolver)
    // {
    //     $resolver->setDefaults([
    //         'csrf_protection' => true,
    //         'csrf_field_name' => '_token',
    //         'csrf_token_id' => 'select_foods'
    //     ]);
    // }
}