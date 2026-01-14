<?php

namespace App\Repository;

use App\Entity\UnitMeasure;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method UnitMeasure|null find($id, $lockMode = null, $lockVersion = null)
 * @method UnitMeasure|null findOneBy(array $criteria, array $orderBy = null)
 * @method UnitMeasure[]    findAll()
 * @method UnitMeasure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UnitMeasureRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnitMeasure::class);
    }

	public function myFindAliasByForAPart(int $forAPart): array
	{
		// automatically knows to select Products
        // the "p" is an alias you'll use in the rest of the query
        $qb = $this->createQueryBuilder('u')
			->select('u.alias')
            ->where('u.forAPart = :forAPart')
            ->setParameter('forAPart', $forAPart)
            ->orderBy('u.alias', 'ASC');

        $query = $qb->getQuery();

        return $query->execute([], AbstractQuery::HYDRATE_SCALAR_COLUMN);

        // to get just one result:
        // $product = $query->setMaxResults(1)->getOneOrNullResult();
	}

	public function myFindAllIsNotMeasurableForOnePerson()
	{
		$qb = $this->_em->createQuery(
							'SELECT u.id
							FROM App\\Entity\\UnitMeasure u
							WHERE u.isMeasurableForOnePerson = :isMeasurableForOnePerson'
						)->setParameter('isMeasurableForOnePerson', false);

		return $qb->getScalarResult();
	}
}
