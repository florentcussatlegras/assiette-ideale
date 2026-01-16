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
            ->add('unitMeasure', EntityType::class, [
                'class' => UnitMeasure::class,
                'choice_label' => 'alias',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('idFood');
        $resolver->setAllowedTypes('idFood', ['null', 'integer', 'string']);
    }
}