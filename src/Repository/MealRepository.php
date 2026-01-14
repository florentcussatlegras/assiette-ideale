<?php

namespace App\Repository;

use App\Entity\Meal;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Meal|null find($id, $lockMode = null, $lockVersion = null)
 * @method Meal|null findOneBy(array $criteria, array $orderBy = null)
 * @method Meal[]    findAll()
 * @method Meal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, Meal::class);

		$this->security = $security;
    }

	// public function myFindByEatedAt($date, $user)
	// {
	// 	$qb = $this->createQueryBuilder('m')
	// 			   ->where('m.eatedAt = :date')
	// 			   ->setParameter('date', $date)
	// 			   ->andWhere('m.user = :user')
	// 			   ->setParameter('user', $user)
	// 			   ->orderBy('m.rankView');

	// 	return $qb->getQuery()->getResult();
	// }

	public function getAllGroupByDate(UserInterface $user)
	{   
		$conn = $this->getEntityManager()->getConnection();
	    $sql = ' 
	        SELECT *
	          FROM meal
			  INNER JOIN user ON meal.user_id = user.id
	          WHERE user.id = :userId
	    ';

	    $stmt = $conn->prepare($sql);
	    $result = $stmt->executeQuery(['userId' => $user->getId()]);
	    
	    return $result->fetchAllAssociative();
	}
}