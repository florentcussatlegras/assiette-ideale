<?php

namespace App\Repository;

use App\Entity\Dish;
use App\Entity\DishFood;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\FoodGroupRepository;

/**
 * @method DishFood|null find($id, $lockMode = null, $lockVersion = null)
 * @method DishFood|null findOneBy(array $criteria, array $orderBy = null)
 * @method DishFood[]    findAll()
 * @method DishFood[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DishFoodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, FoodGroupRepository $foodGroupRepository)
    {
        parent::__construct($registry, DishFood::class);

        $this->foodGroupRepository = $foodGroupRepository;
    }

    public function findByDishAndGroupByFoodGroup(Dish $dish)
    {
        foreach($this->foodGroupRepository->findAll() as $foodGroup)
        {
            $results[$foodGroup->getAlias()] = 
                    $this->createQueryBuilder('d')
                        ->andWhere('d.dish = :dish')
                        ->setParameter('dish', $dish)
                        ->innerJoin('d.food', 'f')
                        ->andWhere('f.foodGroup = :foodGroup')
                        ->setParameter('foodGroup', $foodGroup)
                        ->getQuery()
                        ->getResult()
            ;
        }

        return $results;
    }
}