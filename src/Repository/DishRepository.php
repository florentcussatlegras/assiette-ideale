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

		// if(!empty($foodGroups)) {
		// 	// On récupère les groupes non selectionnés (TOUS - CEUX SELECTIONNES)
		// 	// et on exclue les plats qui contiennent ces groupes
		// 	$foodGroupsToRemove = array_diff($this->foodGroupRepository->myFindAllIds(), $foodGroups);
		// 	// dd(array_values($foodGroupsToRemove));
			
		// 	$qb->join('dish.dishFoodGroups', 'dfg');	
		// 	$qb->join('dfg.foodGroup', 'fg');
		// 	// $qb->andWhere('fg.id in (:foodGroups)');
		// 	// $qb->andWhere('fg.id in (:foodGroupsToRemove)');
		// 	$qb->andWhere($qb->expr()->notin('fg.id', ':foodGroupsToRemove'));
		// 	// $qb->setParameter('foodGroups', $foodGroups);
		// 	$qb->setParameter('foodGroupsToRemove', $foodGroupsToRemove);

		// }

		// dd($term);
		

        // if($term) {
        //     $qb->andWhere('dish.name LIKE :term')
        //         ->setParameter('term', '%'.$term.'%');
        // }

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
    public function myFindByKeywordAndFGAndTypeAndLactoseAndGluten(?string $keyword = null, array|string|null $fglist = [], $freeLactose = false, $freeGluten = false, ?string $type = 'type.dish.all')
    {
		$results = $dishesValidateFg = $dishesValidateGluten = $dishesValidateLactose = $dishesValidateType = $dishesValidateKeyword = [];
		$countAllFgp = count($this->foodGroupRepository->findAll());
		
		if(!is_array($fglist)) {
			$fglist = explode(',', $fglist);
			foreach($fglist as $key => $fg) {
				$fglist[$key] = (int)$fg;
			}
		}
	
		// dump($type);
		// dump($fglist);
		
		foreach($this->findAll() as $dish) {

			// dump($dish->getType());
			// dump($dish->getFoodGroupIds());
			// exit;
			// if($dish->getId() == 36) {
			
				// dd($dish->getFoodGroupIds());

				if(!empty($fglist) && count($fglist) < $countAllFgp) {
					// $foodGroupIdsToRemove = array_diff($this->foodGroupRepository->myFindAllIds(), $arrayFgList);

					$validateFg = false;
					// foreach($foodGroupIdsToRemove as $fgIdtoRemove) {
					// 	if(in_array((int)$fgIdtoRemove, $dish->getFoodGroupIds())) {
					// 		$validateFg = false;
					// 		break;
					// 	}
					// }
					// foreach($fglist as $fgId) {
					// 	if(!in_array((int)$fgId, $dish->getFoodGroupIds())) {
					// 		$validateFg = false;
					// 		break;
					// 	}
					// }
					// dd($dish->getFoodGroupIds(), $fglist);
					foreach($dish->getFoodGroupIds() as $fgId) {
						if(in_array($fgId, $fglist)) {
							$validateFg = true;
							break;
						}
					}
					// dd($validateFg);
					if($validateFg) {
						$dishesValidateFg[] = $dish->getId(); 
					}
				}else{
					$dishesValidateFg[] = $dish->getId(); 
				}

			// }
			
			// exit;

			if(true === (bool)$freeGluten) {
				if(false === $dish->getHaveGluten()) {
					$dishesValidateGluten[] = $dish->getId(); 
				}
			}else{
				$dishesValidateGluten[] = $dish->getId();
			}
	
			if(true === (bool)$freeLactose) {
				if(false === $dish->getHaveLactose()) {
					$dishesValidateLactose[] = $dish->getId(); 
				}
			}else{
				$dishesValidateLactose[] = $dish->getId();
			}
	
			if(!empty($keyword))
			{
				// $qb->andWhere('dish.name LIKE :keyword')
				//    ->setParameter('keyword', "%{$keyword}%");
				if(str_contains(strtolower($dish->getName()), strtolower(trim($keyword)))) {
					$dishesValidateKeyword[] = $dish->getId();
				}
			}else{
				$dishesValidateKeyword[] = $dish->getId();
			}
	
			if($type && 'type.dish.all' != $type)  {
				if($type === $dish->getType()) {
					$dishesValidateType[] = $dish->getId();
				}
			}else{
				$dishesValidateType[] = $dish->getId();
			}

		}

		$results = array_unique(array_intersect($dishesValidateFg, $dishesValidateGluten, $dishesValidateLactose, $dishesValidateType, $dishesValidateKeyword));

		$resultObjects = array_map(function($id) {
			return $this->findOneById($id);
		}, $results);

		usort($resultObjects, (function($dish1, $dish2){
			if($dish1->getName() < $dish2->getName()) {
				return -1;
			}

			return 1;
		}));

		return $resultObjects;
	}
	
	/**
     * Search by foodgroup and/or keyword and/or type
     *
     * @return Dish[]
     */
    public function myFindByKeywordAndFGAndTypeAndLactoseAndGluten2(?string $keyword = null, array|string|null $fglist = [], ?string $type = null, $freeLactose = false, $freeGluten = false)
    {
		$qb = $this->createQueryBuilder('dish')
				   ->orderBy('dish.name', 'ASC');

		if(!empty($fglist) && !is_array($fglist)) {
			// $fglist = explode(',', $fglist);

			

			// foreach($fgList as $critFg) {
			// 	$qb->andWhere('dish.foodGroupIds', )
			// 	   ->setParameter('foodGroups', )	
			// }
		
			// 	// On récupère les groupes non selectionnés (TOUS - CEUX SELECTIONNES)
			// 	// et on exclue les plats qui contiennent ces groupes
			// $foodGroupsToRemove = array_diff($this->foodGroupRepository->myFindAllIds(), $fglist);
			// $qb->innerJoin('dish.dishFoodGroups', 'dfg');	
			// $qb->innerJoin('dfg.foodGroup', 'fg');
			// // $qb->andWhere('fg.id in (:foodGroups)');
			// $qb->andWhere('fg.id not in (:foodGroupsToRemove)');
			// $qb->andWhere($qb->expr()->notin('dfg.id', ':foodGroupsToRemove'));
			// // $qb->setParameter('foodGroups', $foodGroups);
			// $qb->setParameter('foodGroupsToRemove', $foodGroupsToRemove);
		}

		if(true === (bool)$freeGluten) {
			$qb->andWhere('dish.haveGluten = ?0')
				->setParameter('0', 0);
		}

		if(true === (bool)$freeLactose) {
			$qb->andWhere('dish.haveLactose = ?0')
				->setParameter('0', 0);
		}

		if(!empty($keyword))
		{
			$qb->andWhere('dish.name LIKE :keyword')
			   ->setParameter('keyword', "%{$keyword}%");
		}
	
		// if(!empty($fglist)){
		// 	$qb->leftJoin('d.dishFoodGroups', 'dfg');	
		// 	$qb->leftJoin('dfg.foodGroup', 'fg');
		// 	$qb->andWhere('fg.id in (:fglist)');
		// 	$qb->setParameter('fglist', $fglist);
		// }

		if($type && 'type.dish.all' != $type)  {
			$qb->andwhere('dish.type = :type')
				->setParameter('type', $type);
		}

        return $qb->getQuery()->execute();
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
		// $qb->setParameter('sortAlpha', $sortAlpha);
		
		// $qb->setFirstResult($offset);
		// $qb->setMaxResults($limit);
		
		return $qb->getQuery()->getResult();
		//return $query;

		//return new Paginator($query, true);
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
