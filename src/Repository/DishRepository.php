<?php

namespace App\Repository;

use App\Entity\Dish;
use Doctrine\ORM\QueryBuilder;
use App\Repository\FoodGroupRepository;
use App\Repository\DishFoodRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Dish|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dish|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dish[]    findAll()
 * @method Dish[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DishRepository extends ServiceEntityRepository
{
	private const DAYS_BEFORE_REJECTED_REMOVAL = 7;
	private $foodGroupRepository;
	private $dishFoodRepository;

	public function __construct(ManagerRegistry $registry, FoodGroupRepository $foodGroupRepository, DishFoodRepository $dishFoodRepository)
    {
        parent::__construct($registry, Dish::class);

		$this->foodGroupRepository = $foodGroupRepository;
		$this->dishFoodRepository = $dishFoodRepository;
    }

	public function countOldRejected($daysBeforeRejected = self::DAYS_BEFORE_REJECTED_REMOVAL, $dishname = false): int
	{
		return $this->getOldRejectedQueryBuilder($daysBeforeRejected, $dishname)->select('COUNT(d.id)')->getQuery()->getSingleScalarResult();
	}

	public function deleteOldRejected($daysBeforeRejected = self::DAYS_BEFORE_REJECTED_REMOVAL, $dishname = false): int
	{
		return $this->getOldRejectedQueryBuilder($daysBeforeRejected, $dishname)->delete()->getQuery()->execute();
	}

	public function getDisplayDataOldRejected($daysBeforeRejected = self::DAYS_BEFORE_REJECTED_REMOVAL, $dishname = false): array
	{
		return $this->getOldRejectedQueryBuilder($daysBeforeRejected, $dishname)->select('d.id, d.name, d.createdAt')->getQuery()->getResult();
	}
	
	public function getOldRejectedQueryBuilder($daysBeforeRejected, $dishname = false): QueryBuilder
	{
		$queryBuilder = $this->createQueryBuilder('d')
				->andWhere('d.createdAt <= :date')
				->setParameter('date', new \DateTime("-$daysBeforeRejected days"))
		;

		if($dishname) {
			$queryBuilder->andWhere('d.name = :name')
						->setParameter('name', $dishname)
			;
		}

		return $queryBuilder;
	}

	/**
     * Search by foodgroup and/or keyword and/or type
     *
     * @return Dish[]
     */
    public function myFindByKeywordAndFGAndType(?string $keyword = null, array|string|null $fglist = [], ?string $type = null)
    {
        $qb = $this->createQueryBuilder('dish');
		
		if(!empty($fglist) && !is_array($fglist)) {
			$fglist = explode(',', $fglist);
		}

		$qb = $this->createQueryBuilder('d');

		if(!empty($keyword))
		{
			$qb->where('d.name LIKE :keyword');
			$qb->setParameter('keyword', "%{$keyword}%");
		}
	
		if(!empty($fglist)){
			$qb->leftJoin('d.dishFoodGroups', 'dfg');	
			$qb->leftJoin('dfg.foodGroup', 'fg');
			$qb->andWhere('fg.id in (:fglist)');
			$qb->setParameter('fglist', $fglist);
		}

		if($type && 'type.dish.all' != $type)  {
			$qb->andwhere('d.type = :type')
				->setParameter('type', $type);
		}

		$qb->orderBy('d.name', 'ASC');

        return $qb
            ->getQuery()
            ->execute();
    }

	/**
     * Search by foodgroup and/or keyword and/or type
     *
     * @return Dish[]
     */
    public function myFindByKeywordAndFGAndTypeAndLactoseAndGluten(
			?string $keyword = null, 
			array|string|null $fglist = [], 
			$freeLactose = false, 
			$freeGluten = false, 
			?string $type = 'type.dish.all',
			$limit = null,
			$offset = 0
	)
    {
		$qb = $this->createQueryBuilder('d')
       				 ->select('DISTINCT d')
					 ->leftJoin('d.dishFoodGroups', 'dfg')
					 ->leftJoin('dfg.foodGroup', 'fg');

		/*
		* 🔹 FOOD GROUP FILTER
		*/
		if (!empty($fglist) && $fglist !== 'none') {
			if (!is_array($fglist)) {
				$fglist = array_map('intval', explode(',', $fglist));
			}

			$qb->andWhere('fg.id IN (:fglist)')
				->setParameter('fglist', $fglist);
		}

		if (!empty($keyword)) {
			$qb->andWhere('LOWER(d.name) LIKE :keyword')
			->setParameter('keyword', '%'.strtolower(trim($keyword)).'%');
		}

		if (!empty($fglist) && $fglist !== 'none') {

			if (!is_array($fglist)) {
				$fglist = array_map('intval', explode(',', $fglist));
			}

			$qb->andWhere('fg.id IN (:fglist)')
			->setParameter('fglist', $fglist);
		}

		if ((bool)$freeGluten === true) {
			$qb->andWhere('d.haveGluten = false OR d.haveGluten IS NULL');
		}

		if ((bool)$freeLactose === true) {
			$qb->andWhere('d.haveLactose = false OR d.haveLactose IS NULL');
		}

		if ($type && $type !== 'type.dish.all') {
			$qb->andWhere('d.type = :type')
			->setParameter('type', $type);
		}

		$qb->orderBy('d.name', 'ASC');

		if($limit) {
			$qb->setFirstResult($offset)
       		->setMaxResults($limit + 1);
		}

		return $qb->getQuery()->getResult();
	}

	public function myFindByKeywordAndFG(?string $keyword, array|string|null $fglist = [], $sortAlpha = 'ASC')
	{
		$qb = $this->createQueryBuilder('d');

		if(!empty($fglist) && !is_array($fglist)) {
			$fglist = explode(',', $fglist);
		}
		
		if(!empty($keyword))
		{
			$qb->where('d.name LIKE :keyword');
			$qb->setParameter('keyword', "%{$keyword}%");
		}

		if(!empty($fglist)){
			$qb->leftJoin('d.dishFoodGroups', 'dfg');	
			$qb->leftJoin('dfg.foodGroup', 'fg');
			$qb->andWhere('fg.id in (:fglist)');
			$qb->setParameter('fglist', $fglist);
		}

		$qb->orderBy('d.name', $sortAlpha);
		
		return $qb->getQuery()->getResult();
	}

	public function myFindByKeywordAndFGP(?string $keyword, array|string|null $fgplist = [], $sortAlpha = 'ASC', $offset = 0, $limit = 8)
	{
		$qb = $this->createQueryBuilder('d');

		if(!empty($fgplist) && !is_array($fgplist)) {
			$fgplist = explode(',', $fgplist);
		}
		
		if(!empty($keyword))
		{
			$qb->where('d.name LIKE :keyword');
			$qb->setParameter('keyword', "%{$keyword}%");
		}

		if(!empty($fgplist)){
			$qb->leftJoin('d.dishFoodGroupParents', 'dfgp');	
			$qb->leftJoin('dfgp.foodGroupParent', 'fgp');
			$qb->andWhere('fgp.id in (:fgplist)');
			$qb->setParameter('fgplist', $fgplist);
		}

		$qb->orderBy('d.name', 'ASC');
		
		$qb->setFirstResult($offset);
		//return $query->getResult();
		$qb->setMaxResults($limit);
		
		return $qb->getQuery()->getResult();
		//return $query;

		//return new Paginator($query, true);
	}

	public function myFindByGroupAndQuantity($fgCode, $quantity)
	{
		$qb = $this->createQueryBuilder('d')
		           ->innerJoin('d.dishFoodGoup', 'dfg')
		           ->where('dfg.code = :fgCode')
		           ->andWhere('dfg.quantityForOne = :quantity')
		           ->setParameter('fgCode', $fgCode)
		           ->setParameter('quantity', $quantity);

		return $qb->getQuery()->getResult();
	}

	public function myFindByGroupAndQuantityRange($fgCode, $qtyMin, $qtyMax)
	{
		$qb = $this->createQueryBuilder('d')
		           ->innerJoin('d.dishFoodGroups', 'dfg')
		           ->innerJoin('dfg.foodGroup', 'fg')
		           ->where('fg.code = :fgCode')
		           ->andWhere('dfg.quantityForOne BETWEEN :qtyMin AND :qtyMax')
		           ->setParameter('fgCode', $fgCode)
		           ->setParameter('qtyMin', $qtyMin)
		           ->setParameter('qtyMax', $qtyMax);

		return $qb->getQuery()->getResult();
	}
}
