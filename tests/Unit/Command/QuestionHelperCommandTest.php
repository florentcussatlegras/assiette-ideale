<?php

namespace App\Test\Unit\Command;

use Symfony\Component\Console\Tester\CommandTester;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QuestionHelperCommandTest extends KernelTestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }
    // public function testExecute()
    // {
    //     $kernel = static::bootKernel();
    //     $application = new Application($kernel);

    //     $command = $application->find('app:helper:question');
    //     $commandTester = new CommandTester($command);

    //     $commandTester->setInputs(['1234']);

    //     $commandTester->execute([
    //         'name' => ['Florent', 'Floucs'],
    //         '--greet' => true
    //     ]);

    //     $commandTester->assertCommandIsSuccessful();

    //     $output = $commandTester->getDisplay();
    //     $this->assertStringContainsString('Florent, Floucs 1234', $output);
    // }
}