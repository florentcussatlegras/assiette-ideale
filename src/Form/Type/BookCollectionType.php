<?php

namespace App\Form\Type;

use App\Entity\DummiesForTest\Book;
use App\Entity\DummiesForTest\BookCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class BookCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('books', CollectionType::class, [
                'entry_type' => BookType::class,
                'allow_add' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BookCollection::class,
        ]);
    }
}
