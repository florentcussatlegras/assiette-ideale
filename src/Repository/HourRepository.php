<?php

namespace App\Repository;

use App\Entity\Hour;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Hour|null find($id, $lockMode = null, $lockVersion = null)
 * @method Hour|null findOneBy(array $criteria, array $orderBy = null)
 * @method Hour[]    findAll()
 * @method Hour[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HourRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hour::class);
    }
}