<?php

namespace App\Repository;

use App\Entity\StepRecipe;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method StepRecipe|null find($id, $lockMode = null, $lockVersion = null)
 * @method StepRecipe|null findOneBy(array $criteria, array $orderBy = null)
 * @method StepRecipe[]    findAll()
 * @method StepRecipe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StepRecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StepRecipe::class);
    }
}