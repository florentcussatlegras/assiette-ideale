<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultControllerTest extends WebTestCase
{
    /*
    * @group legacy
    */
    public function testIndex()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        // $this->expectException(\Exception::class);
        // $this->expectExceptionMessage('Something went wrong!');
    
        $this->assertTrue(true);
    }

    // public function testCrawler()
    // {
    //     $client = static::createClient();
    //     $crawler = $client->request('GET', '/test-crawler');
        
    //     $data = $crawler->filter('h1')->each(function($node, $i){
    //         return $node->attr('class');
    //     });

    //     dump($data);
    
    //     $this->assertTrue(true);
    // }





    // public function testProfiler()
    // {
        
    // }

    // public function testLink()
    // {
    //     $client = static::createClient();
    //     $client->request('GET', '/foo/bar');

    //     $client->clickLink('Click here');
    // }

    // public function testLink2()
    // {
    //     $client = static::createClient();
    //     $crawler = $client->request('GET', 'foo/bar');

    //     $link = $crawler->selectLink('Click here')->link();
    //     $client->click($link);

    //     $this->assertResponseIsSuccessful();
    //     $this->assertResponseStatusCodeSame(200);
    //     $this->assertResponseRedirects();

    //     $this->assertResponseHasHeader('<h1>');
    //     $this->assertResponseHasHeader('<h1>');
    //     $this->assertResponseHasHeader('<h1>');
    //     $this->assertResponseHasHeader('<h1>');
    //     $this->assertResponseHasHeader('<h1>');
    //     $this->assertResponseHasHeader('<h1>');
    //     $this->assertResponseHasHeader('<h1>');
    //     $this->assertResponseHasHeader('<h1>');
    //     $this->assertResponseHasHeader('<h1>');
    //     $this->assertResponseHasHeader('<h1>');

    //     $this->assertResponseNotHasHeader('<h1>');
    //     $this->assertResponseNotHasHeader('<h1>');
    //     $this->assertResponseNotHasHeader('<h1>');
    //     $this->assertResponseNotHasHeader('<h1>');
    //     $this->assertResponseNotHasHeader('<h1>');
    //     $this->assertResponseNotHasHeader('<h1>');
    //     $this->assertResponseNothasHeader('<h1>');
    //     $this->assertResponseNotHasHeader('<h1>');
    //     $this->assertResponseNotHasHeader('<h1>');
    //     $this->assertResponseNotHasHeader('<h1>');
    //     $this->assertResponseNotHasHeader('<h1>');

    //     $this->assertResponseNotHasheader('<h1>');
    //     $this->assertResponseNotHasHeader('<h1>');
    //     $this->assertResponseNothasHeader('<h1>');
    //     $this->assertresponseNotHasHeader('<h1>');
    //     $this->assertResponseNotHasHeader('<h1>');
    //     $this->assertResponseNotHasHeader('<h1>');
    //     $this->assertResponseNothasHeader('<h1>');
    //     $this->assertResponseNotHasHeader('<h1>');
    //     $this->assertResponseNothasheader('<h1>');
    //     $this->assertResponseNothasHeader('<h1>');
    //     $this->assertResponseNotHasHeader('<h1>');
    //     $this->assertResponseNotHasHeader('<h1>');
    //     $this->assertResponseNothasHeader('<h1>');
    // }

}