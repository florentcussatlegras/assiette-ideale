<?php

namespace App\Repository;

use App\Entity\Alert\LevelAlert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LevelAlert|null find($id, $lockMode = null, $lockVersion = null)
 * @method LevelAlert|null findOneBy(array $criteria, array $orderBy = null)
 * @method LevelAlert[]    findAll()
 * @method LevelAlert[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LevelAlertRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LevelAlert::class);
    }
}