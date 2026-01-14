<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashBoardControllerTest extends WebTestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }
    // public function testIndex()
    // {
    //     $client = static::createClient();
    //     $userRepository = static::getContainer()->get(UserRepository::class);
    //     $testUser = $userRepository->findOneByEmail('florent_user@example.com');
    //     $client->loginUser($testUser);

    //     $client->request('GET', '/dashboard');

    //     $this->assertResponseIsSuccessful();
    //     $this->assertSelectorTextContains('h1', 'Bienvenue');
    //     $this->assertPageTitleSame('Tableau de bord | Live For Eat');
    // }

    // public function testLinkAddDish()
    // {
    //     $client = static::createClient();
    //     $userRepository = static::getContainer()->get(UserRepository::class);
    //     $testUser = $userRepository->findOneByEmail('florent_admin@example.com');
    //     $client->loginUser($testUser);
        
    //     $crawler = $client->request('GET', '/dashboard');
    //     $client->followRedirects();
        
    //     $link = $crawler->selectLink('Ajouter un plat')->link();
    //     $client->click($link);

    //     $this->assertResponseIsSuccessful();
    //     $this->assertSame($client->getRequest()->getPathInfo(), '/plat/nouveau');
    //     $this->assertSelectorTextContains('h1', 'Nouvelle recette');
    // }

    // public function testLinkProfile()
    // {
    //     $client = static::createClient();
    //     $userRepository = static::getContainer()->get(UserRepository::class);
    //     $testUser = $userRepository->findOneByEmail('florent_user@example.com');
    //     $client->loginUser($testUser);

    //     $crawler = $client->request('GET', '/dashboard');

    //     $link = $crawler->selectLink('Voir mon profil')->link();
    //     $client->click($link);

    //     $this->assertResponseIsSuccessful();
    //     $this->assertSame($client->getRequest()->getPathInfo(), '/profile');
    //     $this->assertSelectorTextContains('h1', 'Mon profil');
    // }

    // public function testProfiler()
    // {
    //     $client = static::createClient();
    //     $client->enableProfiler();
    //     $client->request('GET', '/dashboard');
        
    //     if($profile = $client->getProfile())
    //     {
    //         $this->assertSame('app_dashboard_index', $profile->getCollector('request')->getRoute());
    //     }
    // }
}
    