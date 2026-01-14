<?php

namespace App\Repository;

use App\Entity\FoodGroup\FoodGroupParent;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\FoodGroupParentRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method FoodGroupParent|null find($id, $lockMode = null, $lockVersion = null)
 * @method FoodGroupParent|null findOneBy(array $criteria, array $orderBy = null)
 * @method FoodGroupParent[]    findAll()
 * @method FoodGroupParent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoodGroupParentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoodGroupParent::class);
    }

	public function myFindAllOrderByRank()
	{
		$qb = $this->createQueryBuilder('f')
				   ->add('orderBy', 'f.rank');

		return $qb->getQuery()->getResult();
	}

	public function myFindPrincipalOrderByRank()
	{
		$qb = $this->createQueryBuilder('f')
				   ->where('f.isPrincipal = ?1')
				   ->setParameter('1', 1)
				   ->add('orderBy', 'f.rank');

		return $qb->getQuery()->getResult();
	}

	public function myFindNotPrincipalOrderByRank()
	{
		$qb = $this->createQueryBuilder('f')
				   ->where('f.isPrincipal = ?1')
				   ->setParameter('1', 0)
				   ->add('orderBy', 'f.rank');

		return $qb->getQuery()->getResult();
	}

	public function myFindIsUsedForCombinationGroup()
	{
		$qb = $this->createQueryBuilder('f')
				   ->where('f.isUsedForCombination = ?1')
				   ->setParameter('1', 1);

		return $qb->getQuery()->getResult();
	}

	public function myFindIsUsedForBreakfast()
	{
		$qb = $this->createQueryBuilder('f')
				   ->where('f.isUsedForBreakfast = ?1')
				   ->setParameter('1', 1);

		return $qb->getQuery()->getResult();
	}

	public function getIds()
	{
		$conn = $this->getEntityManager()->getConnection();
		$sql = '
			SELECT id
				FROM food_group_parent
		';

		$stmt = $conn->prepare($sql);
		$result = $stmt->executeQuery();

		return $result->fetchFirstColumn();
	}

	public function getIdsPrincipal()
	{
		$conn = $this->getEntityManager()->getConnection();
		$sql = '
			SELECT id
				FROM food_group_parent
				WHERE is_principal = :principal
		';

		$stmt = $conn->prepare($sql);
	    $result = $stmt->executeQuery(['principal' => 1]);
	    
	    return $result->fetchFirstColumn();
	}

	public function getAlias()
	{
		$conn = $this->getEntityManager()->getConnection();
		$sql = '
			SELECT alias
				FROM food_group_parent
		';

		$stmt = $conn->prepare($sql);
		$result = $stmt->executeQuery();

		return $result->fetchFirstColumn();
	}

	public function getAliasPrincipal()
	{   
		$conn = $this->getEntityManager()->getConnection();
	    $sql = ' 
	        SELECT alias
	          FROM food_group_parent
	          WHERE is_principal = :principal
	    ';

	    $stmt = $conn->prepare($sql);
	    $result = $stmt->executeQuery(['principal' => 1]);
	    
	    return $result->fetchAllAssociative();
	}
}
