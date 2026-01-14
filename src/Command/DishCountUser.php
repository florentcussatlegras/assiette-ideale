<?php

namespace App\Command;

use symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DishCountUser extends Command
{
    protected static $defaultName = 'app:dish:count';

    public function configure()
    {
        $this->setDescription('Renvoi le nombre de recette des utilisateurs');
        $this->addArgument(
                    'names',
                    InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                    'Quels sont les utilisateurs concernés?'
        );
        $this->addOption(
                'order', 
                'o',
                InputOption::VALUE_NONE,
                'Voulez-vous les trier du plus grand au plus petit?'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if($input->getArgument('names'))
        {
            $names = $input->getArgument('names');
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Continue with this action?', false);

        if(!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        $formatter = $this->getHelper('formatter');
        $formattedLine = $formatter->formatSection('Bonjour', 'Bonjour, vous êtes le bienvenue!');
        $output->writeln($formattedLine);

        if($output->isVerbose()) {
            $style = new OutputFormatterStyle('blue', 'white', ['bold']);
            $output->getFormatter()->setStyle('verbose', $style);
            $output->writeln('<verbose>N\'hésitez pas à passer nous voir!</verbose>');
        }

        // $formatter = $this->getHelper('formatter');

        // $formattedLine = $formatter->formatSection('SomeSection','Here is some message');
        // $output->writeln($formattedLine);

        // $errorMessages = ['Error!', 'Something went wrong'];
        // $formattedBlock = $formatter->formatBlock($errorMessages, 'error');
        // $output->writeln($formattedBlock);

        // $message = "This is a very long message, which should be truncated";
        // $truncatedMessage = $formatter->truncate($message, 7);
        // $output->writeln($truncatedMessage);

        // $truncatedMessage = $formatter->truncate($message, -5);
        // $output->writeln($truncatedMessage);

        // $truncatedMessage = $formatter->truncate($message, 7, '!!');
        // $output->writeln($truncatedMessage);

        // $output->writeln('<error>Message erreur</error>');

        // $style = new OutputFormatterStyle('blue', 'white', ['bold']);
        // $output->getFormatter()->setStyle('custom', $style);
        // $output->writeln('<custom>message dans custom</custom>');

        // $helper = $this->getHelper('process');
        // $process = new Process(['figlet', 'Symfony']);
        // $helper->run($output, $process);


        return Command::SUCCESS;
    }
}