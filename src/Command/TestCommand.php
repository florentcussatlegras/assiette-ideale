<?php

namespace App\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TestCommand extends Command
{
    protected static $defaultName = 'app:create-user';
    protected static $defaultDescription = 'Description de la commande app:create-user';
    private $router;

    public function __construct(RouterInterface $router)
    {
        parent::__construct();

        $this->router = $router;
    }

    protected function configure(): void
    {
        $this->setHelp('texte d\'aide à la commande app:create-user');

        $this->addOption(
            'yell',
            'y',
            InputOption::VALUE_OPTIONAL,
            'Yell or not?',
            false
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        dd($input->getOption('yell'));


        return Command::SUCCESS;
    }
}