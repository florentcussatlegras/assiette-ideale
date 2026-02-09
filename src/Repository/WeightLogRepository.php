<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use App\Entity\WeightLog;

/**
 * @method WeightLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method WeightLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method WeightLog[]    findAll()
 * @method WeightLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WeightLogRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeightLog::class);
    }

    public function findLastForUser(User $user): ?WeightLog
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUserBetweenDates(User $user, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.user = :user')
            ->andWhere('w.createdAt BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('w.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserOrdered(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
