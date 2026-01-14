<?php

namespace App\Repository;

use App\Entity\Food;
use App\Entity\FoodGroup\FoodGroup;
use App\Repository\FoodGroupRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Food|null find($id, $lockMode = null, $lockVersion = null)
 * @method Food|null findOneBy(array $criteria, array $orderBy = null)
 * @method Food[]    findAll()
 * @method Food[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoodRepository extends ServiceEntityRepository
{
	private $foodGroupRepository;

	public function __construct(ManagerRegistry $registry, FoodGroupRepository $foodGroupRepository)
    {
        parent::__construct($registry, Food::class);

		$this->foodGroupRepository = $foodGroupRepository;
    }

	/**
     * Search by foodgroup and/or keyword
     *
     * @return Food[]
     */
    public function myFindByKeywordAndFG(?string $keyword, array|string|null $fglist = [])
    {
        $qb = $this->createQueryBuilder('f');
		
		if(!is_array($fglist)) {
			$fglist = explode(',', $fglist);
		}

		if (!empty($keyword)) {
			$qb->andWhere('f.name LIKE :keyword')
				->setParameter('keyword', '%'.$keyword.'%');
		}

		if(!empty($fglist)) {
			$qb->join('f.foodGroup', 'fg')
				->andWhere('fg.id IN (:fglist)')
				->setParameter('fglist', $fglist);
		}

		$qb->orderBy('f.name', 'ASC');

		// $qb->setFirstResult($offset);
		// $qb->setMaxResults($limit);

        return $qb->getQuery()->execute();
    }

	/**
     * Search by foodgroup and/or keyword
     *
     * @return Food[]
     */
    public function myFindByKeywordAndFGAndLactoseAndGluten(?string $keyword, array|string|null $fglist = [], $freeLactose = false, $freeGluten = false)
    {
        $qb = $this->createQueryBuilder('f');
		
		if(!is_array($fglist)) {
			$fglist = explode(',', $fglist);
		}

		if (!empty($keyword)) {
			$qb->andWhere('f.name LIKE :keyword')
				->setParameter('keyword', '%'.$keyword.'%');
		}

		if(true === (bool)$freeGluten) {
			$qb->andWhere('f.haveGluten = ?0')
				->setParameter('0', 0);
		}
		// dump($freeLactose);
		if(true === (bool)$freeLactose) {
			$qb->andWhere('f.haveLactose = ?0')
				->setParameter('0', 0);
		}
		// dd($fglist);
		if(!empty($fglist)) {
			$qb->join('f.foodGroup', 'fg')
				->andWhere('fg.id IN (:fglist)')
				->setParameter('fglist', $fglist);
		}

		$qb->orderBy('f.name', 'ASC');

		// $qb->setFirstResult($offset);
		// $qb->setMaxResults($limit);

        return $qb->getQuery()->execute();
    }

	public function myFindByFgAlias($fgAlias)
	{
		$qb = $this->createQueryBuilder('f')
				   ->join('f.foodGroup', 'fg')
				   ->where('fg.alias = :fgAlias')
				   ->setParameter('fgAlias', $fgAlias);

		return $qb->getQuery()->getResult();
	}

	public function myFindByFgAliasExcludeForbidden($fgAlias, $forbiddenFoods)
	{	
		$qb = $this->createQueryBuilder('f')
				   ->where('f.notConsumableRaw = ?1')
				   ->setParameter('1', 0)
				   ->join('f.foodGroup', 'fg')
				   ->andWhere('fg.alias = :fgAlias')
				   ->setParameter('fgAlias', $fgAlias)
				   ->andWhere('f.isSubFoodGroup = ?0')
				   ->setParameter('0', 0)
				   ->andWhere('f.id NOT IN (:forbiddenFoods)')
				   ->innerJoin('f.subFoodGroup', 'fsfg')
				   ->andWhere('fsfg.id NOT IN (:forbiddenFoods)')
				   ->setParameter('forbiddenFoods', $forbiddenFoods);

		return $qb->getQuery()->getResult();
	}

	public function myFindByFgpCode($fgpCode)
	{
		$qb = $this->createQueryBuilder('f')
				   ->join('f.foodGroup', 'fg')
				   ->innerjoin('fg.parent', 'fgp')
				   ->where('fgp.code = :fgpCode')
				   ->setParameter('fgpCode', $fgpCode);

		return $qb->getQuery()->getResult();
	}

	public function myFindByFgpCodeExcludeForbidden($fgpCode, $forbiddenFoods)
	{
		$qb = $this->createQueryBuilder('f')
		           ->where('f.notConsumableRaw = ?1')
				   ->andWhere('f.isSubFoodGroup = ?0')
				   ->join('f.foodGroup', 'fg')
				   ->join('fg.parent', 'fgp')
				   ->andWhere('fgp.code = :fgpCode')
				   ->andWhere('f.id NOT IN (:forbiddenFoods)')
				   ->innerJoin('f.subFoodGroup', 'fsfg')
				   ->andWhere('fsfg.id NOT IN (:forbiddenFoods)')
				   ->setParameter('1', 0)
				   ->setParameter('0', 0)
				   ->setParameter('fgpCode', $fgpCode)
				   ->setParameter('forbiddenFoods', $forbiddenFoods);

		return $qb->getQuery()->getResult();	
	}

	public function myFindAllExcludeForbidden($forbiddenFoods)
	{
		$qb = $this->createQueryBuilder('f')
				   ->where('f.notConsumableRaw = ?1')	
				   ->andWhere('f.isSubFoodGroup = ?0')
				   ->join('f.foodGroup', 'fg')
				   ->join('fg.parent', 'fgp')
				   ->andWhere('f.id NOT IN (:forbiddenFoods)')
				   ->innerJoin('f.subFoodGroup', 'fsfg')
				   ->andWhere('fsfg.id NOT IN (:forbiddenFoods)')
				   ->setParameter('1', 0)
				   ->setParameter('0', 0)
				   ->setParameter('forbiddenFoods', $forbiddenFoods);

		return $qb->getQuery()->getResult();	
	}

	// public function myFindByFgpCodeExcludeForbidden($fgpCode, $forbiddenFoods)
	// {	
	// 	$qb = $this->createQueryBuilder('f')
	// 			   ->where('f.id NOT IN (:forbiddenFoods)')
	// 			   ->innerJoin('f.foodGroup', 'fg')
	// 			   ->innerJoin('fg.parent', 'fgp')
	// 			   ->andWhere('fgp.code = :fgpCode')
	// 			   // ->innerJoin('f.foodParent', 'fp')
	// 			   // ->andWhere('fp.id NOT IN (:forbiddenFoods)')
	// 			   ->setParameter('fgpCode', $fgpCode)
	// 			   ->setParameter('forbiddenFoods', $forbiddenFoods);

	// 	return $qb->getQuery()->getResult();
	// }

	public function myFindByKeyword($keyword)
	{
	    $qb = $this->createQueryBuilder('f');
	    $qb->where(
	   		$qb->expr()->like('f.name', ':keyword')
	    )
	    ->setParameter('keyword', '%'.$keyword.'%');

		return $qb->getQuery()->getResult();
	}

	public function myFindByKeywordAndFGP($keyword = null, $fgplist = [], $typeSelectFgp = 'or', $sortAlpha = 'ASC', $offset = 0, $limit = 8)
	{
	    $qb = $this->createQueryBuilder('f');

	    if(!empty($keyword))
	    {
		    $qb->where('f.name LIKE :keyword');
			$qb->setParameter('keyword', "%{$keyword}%");
		}

		if(!empty($fgplist)){
		    $qb->leftJoin('f.foodGroup', 'fg');
		    $qb->leftJoin('fg.parent', 'fgp');
		    $qb->andWhere('fgp.id in (:fgplist)');
		    $qb->setParameter('fgplist', $fgplist);
		}

		$qb->andWhere('f.isSubFoodGroup = ?0');
		$qb->setParameter('0', 0);

		$qb->orderBy('f.name', 'ASC');

		$qb->setFirstResult($offset);
		$qb->setMaxResults($limit);

		return $qb->getQuery()->getResult();

		//$query = $qb->getQuery();

		//$qb->setFirstResult($first);
		//$qb->setMaxResults($limit);

		//return $qb->getQuery()->getResult();

		//return new Paginator($query, true);
	}

	public function myFindByKeywordAndFGPExcludeForbidden($keyword = null, $fgplist = [], $typeSelectFgp = 'or', $sortAlpha = 'ASC', $forbiddenFoods = [], $offset = 0, $limit = 8)
	{
	    $qb = $this->createQueryBuilder('f')
	               ->where('f.notConsumableRaw = ?1')
				   ->setParameter('1', 0);
	    // dump($forbiddenFoods);
	    // dd($fgplist);

		if(!empty($fgplist) && !is_array($fgplist)) {
			$fgplist = explode(',', $fgplist);
		}

	    if(!empty($keyword))
	    {
		    $qb->andWhere('f.name LIKE :keyword');
			$qb->setParameter('keyword', "%{$keyword}%");
		}

		if(!empty($fgplist)){
		    $qb->leftJoin('f.foodGroup', 'fg');
		    $qb->leftJoin('fg.parent', 'fgp');
		    $qb->andWhere('fgp.id in (:fgplist)');
		    $qb->setParameter('fgplist', $fgplist);
		}

		$qb->andWhere('f.isSubFoodGroup = ?0');
	    $qb->setParameter('0', 0);

		/*if(!empty($forbiddenFoods))
		{
		    $qb->andWhere('f.id not in (:forbiddenFoods)');
		    $qb->leftJoin('f.subFoodGroup', 'fsfg');
		    $qb->andWhere('fsfg.id not in (:forbiddenFoods)');
		    $qb->setParameter('forbiddenFoods', $forbiddenFoods);
		}*/
		// if(null !== $sortAlpha)
		// 	$qb->orderBy('f.name', $sortAlpha);	
		// else

		$qb->orderBy('f.name', 'ASC');

		$qb->setFirstResult($offset);

		$qb->setMaxResults($limit);

		return $qb->getQuery()->getResult();

		// $query->setFirstResult($first);
		// $query->setMaxResults($limit);

		// return new Paginator($query, true);
	}

	public function myFindByKeywordAndFGExcludeForbidden($keyword = null, $fglist = [], $sortAlpha = 'ASC', $offset = 0, $limit = 10)
	{
	    $qb = $this->createQueryBuilder('f')
	               ->where('f.notConsumableRaw = ?1')
				   ->setParameter('1', 0);
	    // dump($forbiddenFoods);
	    // dd($fgplist);

		if(!empty($fglist) && !is_array($fglist)) {
			$fglist = explode(',', $fglist);
		}

	    if(!empty($keyword))
	    {
		    $qb->andWhere('f.name LIKE :keyword');
			$qb->setParameter('keyword', "%{$keyword}%");
		}

		if(!empty($fglist)){
		    $qb->leftJoin('f.foodGroup', 'fg');
		    $qb->andWhere('fg.id in (:fglist)');
		    $qb->setParameter('fglist', $fglist);
		}

		$qb->andWhere('f.isSubFoodGroup = ?0');
	    $qb->setParameter('0', 0);

		// if(!empty($forbiddenFoods))
		// {
		//     $qb->andWhere('f.id not in (:forbiddenFoods)');
		//     $qb->leftJoin('f.subFoodGroup', 'fsfg');
		//     $qb->andWhere('fsfg.id not in (:forbiddenFoods)');
		//     $qb->setParameter('forbiddenFoods', $forbiddenFoods);
		// }

		if(null !== $sortAlpha)
			$qb->orderBy('f.name', $sortAlpha);	

		$qb->orderBy('f.name', 'ASC');
		// $qb->setFirstResult($offset);
		// $qb->setMaxResults($limit);

		return $qb->getQuery()->getResult();

		// $query->setFirstResult($first);
		// $query->setMaxResults($limit);

		// return new Paginator($query, true);
	}

	public function findAllIdByFoodGroup($foodGroupCode)
	{
        return $this->createQueryBuilder('f')
				->join('f.foodGroup', 'fg')
				->andWhere('fg.code = :foodGroupCode')
				->setParameter('fgCode', $foodGroupCode)
				->select('id')
				->getQuery()
				->getResult()
		;
	}

	public function myFindByKeywordAndFgAlias($keyword, $foodgroupAlias)
	{
		$qb = $this->createQueryBuilder('f');

	    if(!empty($keyword))
	    {
		    $qb->where('f.name LIKE :keyword');
			$qb->setParameter('keyword', "%{$keyword}%");
		}

		$qb->leftJoin('f.foodGroup', 'fg');
		$qb->andWhere('fg.alias = :foodgroupAlias');
		$qb->setParameter('foodgroupAlias', $foodgroupAlias);

		$qb->orderBy('f.name', 'ASC');

		return $qb->getQuery()->getResult();
	}
}