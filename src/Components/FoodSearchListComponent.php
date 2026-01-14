<?php
namespace App\Components;

use App\Entity\Food;
use App\Repository\FoodRepository;
use App\Service\PackageRepository;
use Algolia\SearchBundle\SearchService;
use App\Repository\FoodGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UnitMeasureRepository;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('food_search_list')]
class FoodSearchListComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?string $query = null;

    // #[LiveProp(writable: true)]
    // public ?array $fgs;

    #[LiveProp]
    public ?string $foodGroupAlias;

    #[LiveProp]
    public ?string $foodGroupSlug;

    public function __construct(private SearchService $searchService, 
                            private EntityManagerInterface $em,
                            private FoodRepository $foodRepository,
                            private UnitMeasureRepository $unitMeasureRepository,
                            private FoodGroupRepository $foodGroupRepository)
    {}

    public function getFoods(): ?array
    {
        if($this->foodGroupAlias) {
            $foodGroup = $this->foodGroupRepository->findByAlias($this->foodGroupAlias);
            if(empty($this->query)) {
                return $this->foodRepository->findBy(['foodGroup' => $foodGroup]);
            }

            return $this->foodRepository->myFindByKeywordAndFg($this->query, $this->foodGroupAlias);
        }

        if(empty($this->query)) {
            return $this->foodRepository->findAll();
        }

        return $this->foodRepository->myFindByKeyword($this->query);

        // return $this->searchService->search($this->em, Food::class, $this->query);
    }

    public function getUnitMeasures(): array
    {
        return $this->unitMeasureRepository->findAll();
    }

    public function getFoodGroups(): array
    {
        return $this->foodGroupRepository->findAll();
    }
}