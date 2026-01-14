<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

class FormatterHelperCommand extends Command
{
    protected static $defaultName = 'app:helper:formatter';
    protected static $defaultDescription = 'Une commande console pour s\'entraîner aux helpers';

    public function configure()
    {
        $this->addArgument('name', InputArgument::IS_ARRAY);
        $this->addOption('upper', 'u', InputOption::VALUE_OPTIONAL);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        // $text = 'Hello';

        // $formatter = $this->getHelper('formatter');

        // $names = $input->getArgument('name');
        // if(count($names) > 0) {
        //     $text .= ' '.implode(', ', $names);
        //     $formattedOutput = $formatter->formatSection(
        //         'Bonjour',
        //         $text
        //     );
        // }else{
        //     $outputStyle = new OutputFormatterStyle('yellow', 'red', ['bold']);
        //     $output->getFormatter()->setStyle('customstyle', $outputStyle);
        //     $infoMessages = ['info!', 'Something went wrong'];
        //     $formattedOutput = $formatter->formatBlock($infoMessages, 'customstyle', true);
        // }

        // $output->writeln($formattedOutput);

        // $message = 'this is a very long message, wich should be truncated';
        // $truncatedMessage = $formatter->truncate($message, 7);
        // $output->writeln($truncatedMessage);

        // $truncatedMessage = $formatter->truncate($message, -5);
        // $output->writeln($truncatedMessage);

        // $truncatedMessage = $formatter->truncate($message, 7, '!!');
        // $output->writeln($truncatedMessage);

        // $truncatedMessage = $formatter->truncate($message, 7, '');
        // $output->writeln($truncatedMessage);

        // $truncatedMessage = $formatter->truncate('test', 10);
        // $output->writeln($truncatedMessage);

        $io = new SymfonyStyle($input, $output);
        
        #ADMONITION
        $io->error('Message error');

        return Command::SUCCESS;
    }
}