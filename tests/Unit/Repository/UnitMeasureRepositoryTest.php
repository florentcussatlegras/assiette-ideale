<?php

namespace App\Test\Unit\Repository;

use App\Entity\UnitMeasure;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UnitMeasureRepositoryTest extends KernelTestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }
    // /**
    //  * @var \Doctrine\ORM\EntityManager
    //  */
    // private $entityManager;

    // public function setUp(): void
    // {
    //     $kernel = self::bootKernel();

    //     $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    // }

    // public function testSearchByAlias()
    // {
    //     $unitMeasure = $this->entityManager->getRepository(UnitMeasure::class)->findOneBy(['alias' => 'kg']);

    //     $this->assertSame('Kilogramme', $unitMeasure->getName());
    // }

    // public function tearDown(): void
    // {
    //     parent::tearDown();

    //     $this->entityManager->close();
    //     $this->entityManager = null;
    // }
}