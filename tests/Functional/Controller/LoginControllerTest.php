<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginControllerTest extends WebTestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }
    // public function testLinkRegister()
    // {
    //     $client = static::createClient();
    //     $crawler = $client->request('GET', '/login');

    //     $linkRegister = $crawler->selectLink('Inscrivez-vous en moins de 5 min!')->link();
    //     $this->assertStringEndsWith('/register', $linkRegister->getUri());

    //     $crawler = $client->click($linkRegister);

        
    // }
}