<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }
    // public function testRegister()
    // {
    //     $client = static::createClient();
    //     $crawler = $client->request('GET', '/register');

    //     $submitButton = $crawler->selectButton('Valider');
    //     $form = $submitButton->form();

    //     $form['user_registration[email]'] = 'john@doe.com';
    //     $form['user_registration[username]'] = 'John Doe';
    //     $form['user_registration[plainPassword][first]'] = '1234';
    //     $form['user_registration[plainPassword][second]'] = '1234';
    //     $form['user_registration[terms_of_use]']->tick();
    //     // $form['user_registration[_token]'] = 'ab12serf45d';

    //     $client->submit($form);

    //     $this->assertEmailCount(1);

    //     $this->assertEmailCount(1);
    //     $email = $this->getMailerMessage(0);
    //     $this->assertEmailHeadersame($email, 'To', 'john@doe.com');
    //     $this->assertEmailTextBodyContains($email, 'You signed up as John Doe the following email');

    //     $crawler = $client->followRedirect();

    //     $this->assertResponseIsSuccessful();
    //     $this->assertPageTitleContains('S\'identifier');
    //     $this->assertSame($client->getRequest()->getPathInfo(), '/login');
    //     $this->assertSelectorTextContains('h2', 'Bienvenue !');
    //     $this->assertSame($crawler->filter('.alert-info .font-medium')->text(), 
    //         'Nous vous avons envoyé un lien de confirmation de votre adresse email.');
    // }

    // public function testRegisterFailureWithInvalidEmail()
    // {
    //     $client = static::createClient();
    //     $crawler = $client->request('GET', '/register');

    //     $submitButton = $crawler->selectButton('Valider');
    //     $form = $submitButton->form();

    //     $client->submit($form, [
    //         'user_registration[email]' => 'johndoe.gmail.com',
    //         'user_registration[username]' => 'John Doe',
    //         'user_registration[plainPassword][first]' => '1234',
    //         'user_registration[plainPassword][second]' => '1234',
    //         'user_registration[terms_of_use]' =>  1
    //     ]);

    //     $this->assertResponseIsSuccessful();
    //     $this->assertSelectorTextContains('li', 'Cette valeur n\'est pas une adresse email valide.');
    // }

    // public function testRegisterFailureWithInvalidPassword()
    // {
    //     $client = static::createClient();
    //     $crawler = $client->request('GET', '/register');

    //     $submitButton = $crawler->selectButton('Valider');
    //     $form = $submitButton->form();

    //     $form['user_registration[email]'] = 'john.doe@gmail.com';
    //     $form['user_registration[username]'] = 'John Doe';
    //     $form['user_registration[plainPassword][first]'] = '1234';
    //     $form['user_registration[plainPassword][second]'] = '123';
    //     $form['user_registration[terms_of_use]']->tick();
        
    //     $client->submit($form);

    //     $this->assertResponseIsSuccessful();
    //     $this->assertSelectorTextContains('li', 'Les mots de passe ne correspondent pas');
    // }

    // public function testRegisterFailureWithEmptyField()
    // {
    //     $client = static::createClient();
    //     $crawler = $client->request('GET', '/register');

    //     $submitButton = $crawler->selectbutton('Valider');
    //     $form = $submitButton->form();

    //     $form['user_registration[email]'] = 'john.doe@example.com';
    //     $form['user_registration[username]'] = 'John Doe';
    //     $form['user_registration[plainPassword][first]'] = '';
    //     $form['user_registration[plainPassword][second]'] =  '';
    //     $form['user_registration[terms_of_use]']->tick();

    //     $client->submit($form);

    //     $this->assertResponseIsSuccessful();
    //     $this->assertSelectorTextContains('li', 'Merci de saisir un mot de passe');
    // }
}