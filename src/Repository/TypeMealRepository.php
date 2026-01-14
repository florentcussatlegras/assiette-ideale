<?php

namespace App\Repository;

use App\Entity\TypeMeal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TypeMeal|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeMeal|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeMeal[]    findAll()
 * @method TypeMeal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeMealRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeMeal::class);
    }
}