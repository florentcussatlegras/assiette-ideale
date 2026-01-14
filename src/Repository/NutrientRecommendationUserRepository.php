<?php

namespace App\Repository;

use App\Entity\NutrientRecommendationUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NutrientRecommendationsUser>
 *
 * @method NutrientRecommendationUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method NutrientRecommendationUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method NutrientRecommendationUser[]    findAll()
 * @method NutrientRecommendationUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NutrientRecommendationUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NutrientRecommendationUser::class);
    }

    public function save(NutrientRecommendationUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(NutrientRecommendationUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return NutrientRecommendationUser[] Returns an array of NutrientRecommendationUser objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('n.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?NutrientRecommendationUser
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
