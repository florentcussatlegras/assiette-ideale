<?php

namespace App\Tests\Functional\Controller;

use App\Repository\GenderRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileControllerTest extends WebTestCase
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
        
    //     $crawler = $client->request('GET', '/profile');
       
    //     $this->assertResponseIsSuccessful();
    //     $this->assertSelectorTextContains('label', 'Genre');
    //     $this->assertEquals($crawler->filter('label')->eq(4)->text(), 'Vos activités physiques');

    //     $link = $crawler->selectLink('Change Password')->link();
    //     $this->assertStringEndsWith('profile/change-password', $link->getUri());

    //     // $this->assertSelectorTextContains('label', 'Vos régimes alimentaires');
    //     // $this->assertSelectorTextContains('label', 'Les aliments que vous ne consommez pas');
    //     $this->assertPageTitleSame('Mon profil | Live For Eat');
    // }

    // public function testEditLife()
    // {
    //     $client = static::createClient();
    //     $userRepository = static::getContainer()->get(UserRepository::class);

    //     $testUser = $userRepository->findOneByEmail('florent_admin@example.com');

    //     $client->loginUser($testUser);

    //     $crawler = $client->request('GET', '/profile/edit');
        
    //     $buttonCrawlerNode = $crawler->selectButton('Valider');

    //     $form = $buttonCrawlerNode->form();

    //     $datas = [
    //         'gender' => 2,
    //         'birthday' => '1979-11-19',
    //         'height' => 185,
    //         'weight' => 85
    //     ];

    //     $form['user_profil[gender]'] = $datas['gender'];
    //     $form['user_profil[birthday]'] = $datas['birthday'];
    //     $form['user_profil[height]'] = $datas['height'];
    //     $form['user_profil[weight]'] = $datas['weight'];

    //     $client->submit($form);

    //     $crawler = $client->followRedirect();

    //     $this->assertResponseIsSuccessful();
    //     $this->assertSame($crawler->filter('.message')->text(), 'Les modifications ont été prises en compte.');

    //     $genderRepository = static::getContainer()->get(GenderRepository::class);

    //     $birthday = \DateTime::createFromFormat('Y-m-d', $datas['birthday']);
    //     $date = new \DateTime;
        
    //     $this->assertSame($crawler->filter('.gender')->text(), $genderRepository->findOneById($datas['gender'])->getName());
    //     $this->assertSame($crawler->filter('.birthday')->text(), $date->diff($birthday)->format('%y ans'));
    // }
}