<?php

namespace App\Command;

use App\Entity\User;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Process\Process;

class MyCommand extends Command
{
    private $router;

    public static $defaultName = 'app:my-command';
    // public static $defaultDescription = 'Description de la commande MyCommand';

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;

        parent::__construct();

    }

    protected function configure(): void
    {
        $this->setHelp('Message d\'aide de la commande app:my-command');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('process');
        $process = new Process(['figlet', 'Symfony']);

        $helper->run($output, $process);

        return Command::SUCCESS;
    }
}