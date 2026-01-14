<?php

namespace App\Form\Type;

use App\Entity\DummiesForTest\Author;
use App\Form\Type\FavoriteColorType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Form\DataTransformer\BirthdayTransformer;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use App\Form\Type\AddresseType;
use Symfony\Component\Validator\Constraints\GroupSequence;

class AuthorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('emailAdress')
            ->add('firstName')
            ->add('age')
            ->add('accessCode')
            // ->add('favoriteColors', CollectionType::class, [
            //     'entry_type' => FavoriteColorType::class,
            //     'allow_add' => true
            // ])
            // ->add('favoriteColors', FavoriteColorType::class)
            ->add('birthday', TextType::class)
            ->add('createdAt', TextType::class)
            ->add('favoriteTowns', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true
            ])
            ->add('genre')
            ->add('language', TextType::class)
            ->add('country', TextType::class)
            ->add('locale', TextType::class)
            ->add('address', AddressType::class)
            ;
        

        $builder->get('birthday')->addViewTransformer(new BirthdayTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Author::class,
            'validation_groups' => ['foo']
        ]);
    }
}
