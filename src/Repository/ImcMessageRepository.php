<?php

namespace App\Repository;

use App\Entity\ImcMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ImcMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImcMessage::class);
    }

    public function findByAlertCode(string $code): ?ImcMessage
    {
        return $this->findOneBy(['alertCode' => $code]);
    }
}
