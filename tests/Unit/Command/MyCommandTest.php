<?php

namespace App\Tests\Unit\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyCommandtest extends WebTestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }
    // public function testExecute()
    // {
    //     $client = static::createClient();
    //     $client->request('GET', '/login');

    //     $userRepository = static::getContainer()->get(UserRepository::class);
    //     $user = $userRepository->findOneBy(['email' => 'florent_admin@example.com']);

    //     $client->loginUser($user);
    // }
}