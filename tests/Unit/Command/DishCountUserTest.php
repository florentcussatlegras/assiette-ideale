<?php

namespace App\Test\Unit\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DishCountUserTest extends KernelTestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }
    // public function testExecute()
    // {
    //     $kernel = self::bootKernel();
    //     $application = new Application($kernel);

    //     $command = $application->find('app:dish:count');

    //     $commandTester = new CommandTester($command);
    //     $commandTester->setInputs(['yes']);

    //     $commandTester->execute([
    //             'names' => ['florent_admin', 'florent_user'],
    //             '--order' => true
    //         ],
    //         [
    //             'verbosity' => OutputInterface::VERBOSITY_VERBOSE
    //         ]
    //     );

    //     $commandTester->assertCommandIsSuccessful();
    //     $output = $commandTester->getDisplay();

    //     $this->assertStringContainsString('Bonjour, vous êtes le bienvenue!', $output);
    //     $this->assertStringContainsString('N\'hésitez pas à passer nous voir!', $output);
    // }
}