<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

class DotEnvModelCommand extends Command
{
    protected static $defaultName = 'app:helper:dotenvmodel';
    protected static $defaultDescription = 'Commande sur le modèle de debug:dotenv';

    protected function configure()
    {
        $this->addArgument('scanned files', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Scanned files (in descending priority');
        $this->addOption('hello', 'he', InputOption::VALUE_NONE, 'Dire bonjour');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $scannedFiles = $input->getArgument('scanned files');

        $io = new SymfonyStyle($input, $output);

        $io->title('Dotenv Variables & Files');

        $io->section('Scanned Files (in descending priority)');

        $io->newLine();

        foreach($scannedFiles as $file)
        {
            $io->listing([
                'Element #1 Lorem ipsum dolor sit amet',
                'Element #2 Lorem ipsum dolor sit amet',
                'Element #3 Lorem ipsum dolor sit amet',
            ]);
        }

        $io->newline();

        $output->writeln('<subtitle>Variables</subtitle>');

        $io->newLine();

        $io->table(
            ['Variable', 'Value', '.env.dev.local', '.env.local', '.env'],
            [
                ['ALGOLIA_API_KEY', 'a1eeb8d7410c4720c08c749d8b5c1195', 'a1eeb8d7410c4720c08c749d8b5c11', 'a1eeb8d7410c4720c08c749d8b5c11', 'n/a']
            ]
        );

        $io->comment('Note real values might be different between web and CLI. ');

        return Command::SUCCESS;
    }
}