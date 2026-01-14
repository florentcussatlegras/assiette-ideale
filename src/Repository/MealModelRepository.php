<?php

namespace App\Repository;

use App\Entity\MealModel;
use App\Repository\TypeMealRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method MealModel|null find($id, $lockMode = null, $lockVersion = null)
 * @method MealModel|null findOneBy(array $criteria, array $orderBy = null)
 * @method MealModel[]    findAll()
 * @method MealModel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MealModelRepository extends ServiceEntityRepository
{
	private $user;
	private $typeMealRepository;

    public function __construct(ManagerRegistry $registry, TypeMealRepository $typeMealRepository, Security $security)
    {
        parent::__construct($registry, MealModel::class);

		$this->user = $security->getUser();
		$this->typeMealRepository = $typeMealRepository;
    }

	public function myFindByUser()
	{
		$qb = $this->createQueryBuilder('m')
		           ->andWhere('m.user = :user')
		           ->setParameter('user', $this->user);

		return $qb->getQuery()->getResult();
	}

	public function myFindByUserAndType($typeMeal)
	{
		$qb = $this->createQueryBuilder('m')
				   ->where('m.type = :type')
		           ->andWhere('m.user = :user')
		           ->setParameter('type', $typeMeal)
		           ->setParameter('user', $this->user);

		return $qb->getQuery()->getResult();
	}

	public function myFindByUserGroupByType()
	{
		// $qb = $this->createQueryBuilder('m')
		// 			->select('m.name')
		// 			->join('m.type', 'type')
		// 			->select('type.backName')
		// 			->groupBy('type')
		// 			->getQuery();
		$results = [];

		foreach($this->typeMealRepository->findAll() as $typeMeal) {
			if(!empty($mealModels = $this->myFindByUserAndType($typeMeal))) {
				$results[$typeMeal->getFrontName()] = $mealModels;
			}
		}

		return $results;
	}
}