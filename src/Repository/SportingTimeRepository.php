<?php

namespace App\Repository;

use App\Entity\SportingTime;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method SportingTime|null find($id, $lockMode = null, $lockVersion = null)
 * @method SportingTime|null findOneBy(array $criteria, array $orderBy = null)
 * @method SportingTime[]    findAll()
 * @method SpiSportingTimece[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SportingTimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SportingTime::class);
    }
}