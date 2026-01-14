<?php

namespace App\Repository;

use App\Entity\Spice;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Spice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Spice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Spice[]    findAll()
 * @method Spice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SpiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Spice::class);
    }
}