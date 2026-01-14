<?php
namespace App\Components;

use App\Entity\Dish;
use App\Repository\DishRepository;
use App\Service\PackageRepository;
use Algolia\SearchBundle\SearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('dish_search_list')]
class DishSearchListComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?string $query = null;

    #[LiveProp(writable: true)]
    public int $count = 0;

    // #[LiveProp(writable: true)]
    // public ?string $type = null;

    public function __construct(private SearchService $searchService, 
                            private EntityManagerInterface $em,
                            private DishRepository $dishRepository)
    {
    }

    public function getDishs(): ?array
    {
        if(empty($this->query)) {
            return $this->dishRepository->findAll();
        }

        return $this->searchService->search($this->em, Dish::class, $this->query);
    }

    // public function getRandomNumber(): int
    // {
    //     return rand(0, $this->max);
    // }

    // #[LiveAction]
    // public function foo()
    // {
    //     $this->max = 1000;
    // }



    // #[LiveAction]
    // public function selectType(#[LiveArg] string $type = null): ?array
    // {
    //     if(null !== $type) {
    //         return $this->dishRepository->findByType($type);
    //     }
    // }
}