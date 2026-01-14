<?php

namespace App\Test\Unit\Repository;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }
    // /**
    //  * @var \Doctrine\ORM\EntityManager
    //  */
    // private $entityManager;

    // protected function setUp(): void
    // {
    //     $kernel = self::bootKernel();

    //     $container = static::getContainer();

    //     $this->entityManager = $kernel->getContainer()
    //                     ->get('doctrine')
    //                     ->getManager();

    //     // trigger_deprecation('vendor-name/package-name', '1.3', 'Your deprecation message');
    // }


    // public function testSearchByName()
    // {
    //     $user = $this->entityManager
    //                 ->getRepository(User::class)
    //                 ->findOneBy(['username' => 'florent_admin'])
    //     ;

    //     $this->assertSame('florent_admin@example.com', $user->getEmail());

    //     trigger_deprecation('foo', '1.2', 'message deprecation foo');
    // }

    // public function testOk()
    // {
    //     $this->assertTrue(1==1);

    //     trigger_deprecation('foo2', '1.1', 'message deprecation foo2');
    // }

    // protected function tearDown(): void
    // {
    //     parent::tearDown();

    //     $this->entityManager->close();
    //     $this->entityManager = null;
    // }
}
