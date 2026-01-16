<?php

namespace App\Repository;

use App\Entity\FoodGroup\FoodGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FoodGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method FoodGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method FoodGroup[]    findAll()
 * @method FoodGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoodGroupRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoodGroup::class);
    }

	public function findAll(): array
	{
		$qb = $this->createQueryBuilder('f')
				   ->orderBy('f.order', 'ASC');

		return $qb->getQuery()->getResult();
	}

	public function myFindIsUsedForCombinationGroup()
	{
		$qb = $this->createQueryBuilder('f')
				   ->where('f.isUsedForCombination = ?1')
				   ->setParameter('1', 1);

		return $qb->getQuery()->getResult();
	}

	public function myFindByFoodGroupParent($foodGroupParent)
	{
		$qb = $this->createQueryBuilder('f')
					->where('f.parent = :foodGroupParent')
					->setParameter('foodGroupParent', $foodGroupParent);

		return $qb->getQuery()->getResult();
	}

	public function myFindAllIds()
	{
		$conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT id FROM food_group';
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchFirstColumn();
	}
}
