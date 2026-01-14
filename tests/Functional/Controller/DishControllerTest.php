<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DishControllerTest extends WebTestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }
    // public function testNew()
    // {
    //     $client = static::createClient();
    //     $userRepository = static::getContainer()->get(UserRepository::class);
    //     $testUser = $userRepository->findOneByEmail(['florent_admin@example.com']);
    //     $client->loginUser($testUser);

    //     $crawler = $client->request('GET', '/plat/nouveau');
    //     $client->followRedirects();

    //     $crawler = $client->submitForm('Ajouter', [
    //         'dish_form[name]' => 'Mon plat',
    //         'dish_form[lengthPersonForRecipe]' => 1,
    //         'dish_form[preparationTime]' => 25,
    //         'dish_form[preparationTimeUnitTime]' => 1
    //     ]);
    // }
}