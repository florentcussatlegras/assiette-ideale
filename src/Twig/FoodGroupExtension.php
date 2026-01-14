<?php

namespace App\Twig;

use Twig\TwigFilter;
use App\Entity\FoodGroup\FoodGroup;
use Twig\Extension\AbstractExtension;
use App\Repository\FoodGroupRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FoodGroupExtension extends AbstractExtension
{
    protected $foodGroupRepository;

    public function __construct(FoodGroupRepository $foodGroupRepository)
    {
        $this->foodGroupRepository = $foodGroupRepository;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('foodgroupname', [$this, 'getName']),
            new TwigFilter('foodgroupnameFromId', [$this, 'getNameFromId']),
        ];
    }

    public function getName(string $code): ?string
    {
        if(!empty($code) && null !== $foodGroup = $this->foodGroupRepository->findOneBy(['code' => $code])){
            return $foodGroup->getName();
        }

        return null;
    }

    public function getNameFromId(int $id): ?string
    {
        dd('filtre pour trouver le nom à partir de id');
        if(!empty($id) && null !== $foodGroup = $this->foodGroupRepository->findOneBy(['id' => $id])){
            return $foodGroup->getName();
        }

        return null;
    }
}