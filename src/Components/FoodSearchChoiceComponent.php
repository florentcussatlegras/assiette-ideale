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

#[AsLiveComponent('food_search_choice')]
class FoodSearchChoiceComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?string $query = null;

    public $idDish;

    public function __construct(private SearchService $searchService, 
                            private EntityManagerInterface $em,
                            private FoodRepository $foodRepository,
                            private UnitMeasureRepository $unitMeasureRepository,
                            private FoodGroupRepository $foodGroupRepository)
    {}

    public function getFoods(): ?array
    {
        if(empty($this->query)) {
            return null;
        }

        return $this->searchService->search($this->em, Food::class, $this->query);
    }

    public function getUnitMeasures(): array
    {
        return $this->unitMeasureRepository->findAll();
    }

    public function getFoodGroups(): array
    {
        return $this->foodGroupRepository->findAll();
    }

    public function getIdDish()
    {
        return $this->idDish;
    }
}