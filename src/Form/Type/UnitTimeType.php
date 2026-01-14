<?php

namespace App\Form\Type;

use App\Entity\UnitTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Repository\UnitTimeRepository;

class UnitTimeType extends AbstractType
{
    private $unitTimeRepository;

    public function __construct(UnitTimeRepository $unitTimeRepository)
    {
        $this->unitTimeRepository = $unitTimeRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
    }

    public function configureOptions(Optionsresolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'form-select form-select-md w-24'
            ],
            'class' => UnitTime::class,
            'choice_label' => function ($unitTime) {
                return $unitTime->getAlias();
            },
            'data' => $this->unitTimeRepository->findOneBy(['alias' => 'min']),
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}