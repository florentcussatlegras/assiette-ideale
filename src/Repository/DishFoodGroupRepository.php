<?php

namespace App\Repository;

use App\Entity\FoodGroup\FoodGroupParent;
use App\Entity\Dish;
use App\Entity\DishFoodGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DishFoodGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method DishFoodGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method DishFoodGroup[]    findAll()
 * @method DishFoodGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DishFoodGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DishFoodGroup::class);
    }

    public function findByDishAndFoodGroupParent(Dish $dish, FoodGroupParent $fgp)
	{
		$qb = $this->createQueryBuilder('df')
	           ->where('df.dish = :dish')
	           ->innerJoin('df.foodGroup', 'fg')
	           ->andWhere('fg.parent = :fgp')
	           ->setParameter('dish', $dish)
	           ->setParameter('fgp', $fgp);

		return $qb->getQuery()->getResult();
	}
}