<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class OptionsCommand extends Command
{
    protected static $defaultName = 'app:training:options';

    protected function configure()
    {
        $this
            ->setDescription('Describe args behaviors')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('foo', 'f'),
                    new InputOption('bar', 'b', InputOption::VALUE_REQUIRED),
                    new InputOption('cat', 'c', InputOption::VALUE_OPTIONAL),
                    new InputArgument('arg', InputArgument::OPTIONAL)
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $foo = $input->getOption('foo');
        $bar = $input->getOption('bar');
        $cat = $input->getOption('cat');
        $arg = $input->getArgument('arg');

        $formatter = $this->getHelper('formatter');

        // $formattedLine = $formatter->formatSection(
        //     'Options',
        //     sprintf('foo : %s, bar : %s, cat : %s', $foo, $bar, $cat)
        // );
        dd($arg, $foo, $bar, $cat);

        // $output->writeln($formattedLine);

        return Command::SUCCESS;
    }
}