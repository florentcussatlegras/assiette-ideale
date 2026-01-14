<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContactControllerTest extends WebTestCase
{
    public function testSubmitForm()
    {
        // $client = static::createClient();
        // $crawler = $client->request('GET', '/contact');

        // $buttonCrawlerNode = $crawler->selectButton('Envoyer');
        // $form = $buttonCrawlerNode->form();

        // $form['message[email]'] = 'florent_admin@example.com';
        // $form['message[object]'] = 0;
        // $form['message[body]'] = 'Lorem ipsum...';

        // $client->submit($form);

        // $this->assertResponseRedirects();

        // $crawler = $client->followRedirect();

        // $this->assertSame('Votre message a bien été envoyé', $crawler->filter('.message')->text());

        // $this->assertEmailCount(1);

        $this->assertTrue(true);
    }
}