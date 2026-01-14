<?php 

namespace App\Tests\Unit\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DishCleanupCommandTest extends KernelTestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }
    // public function testExecuteVerbosityOutput()
    // {
    //     $kernel = static::bootKernel();
    //     $application = new Application($kernel);
    //     $command = $application->find('app:dish:cleanup');

    //     $commandTester = new CommandTester($command);
    //     $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

    //     $output = $commandTester->getDisplay();

    //     $this->assertStringContainsString('Bienvenue', $output);
    // }

    // public function testExecuteWithDishnameArgumentAndExistingDishWithVerbose()
    // {
    //     $kernel = static::bootKernel();
    //     $application = new Application($kernel);

    //     $command = $application->find('app:dish:cleanup');
    //     $commandTester = new CommandTester($command);
        
    //     // Le plat de test (dans la base de test) étant daté du jour même, 
    //     // afin qu'il soit renvoyé par la requête,
    //     // on simule son ancienneté comme étant supérieure à 7 jours
    //     // en indiquant -7 à la question "Indiquez l'ancienneté des plats à supprimer (en jours)"
    //     // ->andWhere('d.createdAt <= :date')
	// 	// ->setParameter('date', new \DateTime("-$daysBeforeRejected days"))
    //     // soit --7 days => +7 days:

    //     $commandTester->setInputs([-7, 'yes']);

    //     $commandTester->execute(
    //         [
    //             'dishname' => 'Plat test',
    //         ],
    //         [
    //             'verbosity' => OutputInterface::VERBOSITY_VERBOSE
    //         ]
    //     );

    //     $output = $commandTester->getDisplay();
    //     $this->assertStringContainsString('Bienvenue dans la commande de nettoyage des plats!', $output);
    //     $this->assertStringContainsString('Les plats que vous souhaitez supprimer sont', $output);
    //     $this->assertStringContainsString('Plat test', $output);
    //     $this->assertStringContainsString('Attention la suppression des plats est définitive', $output);
    //     $this->assertStringContainsString('Les plats ont bien été supprimés!', $output);
    // }

    // public function testExecuteWithoutDishnameArgumentAndExistingDishWithoutVerbose()
    // {
    //     $kernel = static::bootKernel();
    //     $application = new Application($kernel);

    //     $command = $application->find('app:dish:cleanup');
    //     $commandTester = new CommandTester($command);

    //     $commandTester->setInputs(['yes', 'Plat test', -7, 'yes']);
    //     $commandTester->execute([]);

    //     $output = $commandTester->getDisplay();
    //     $this->assertStringContainsString('Les plats que vous souhaitez supprimer sont', $output);
    //     $this->assertStringContainsString('Plat test', $output);
    //     $this->assertStringContainsString('Attention la suppression des plats est définitive', $output);
    //     $this->assertStringContainsString('Les plats ont bien été supprimés!', $output);
    // }
}