<?php

namespace App\Repository;

use App\Entity\PhysicalActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PhysicalActivity|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhysicalActivity|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhysicalActivity[]    findAll()
 * @method PhysicalActivity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhysicalActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhysicalActivity::class);
    }
}