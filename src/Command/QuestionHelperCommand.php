<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

use Symfony\Component\Console\Style\SymfonyStyle;

class QuestionHelperCommand extends Command
{
    protected static $defaultName = 'app:helper:question';
    protected static $defaultDescription = 'Entrainement question helper';

    public function configure()
    {
        // $this->addArgument('name', InputArgument::IS_ARRAY || InputArgument::REQUIRED);
        // $this->addOption('greet', 'g', InputOption::VALUE_REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        
        //NOTE POUR PRENDRE DES NOTES COMME UN POST IT
        // $io->note('Je prends des notes avec $io->note');
        // $io->note('Je prends des notes avec $io->note');
        // $io->note('Je prends des notes avec $io->note');
        // $io->note('Je prends des notes avec $io->note');
        // $io->note('Je prends des notes avec $io->note');
        // $io->note('Je prends des notes avec $io->note');
        // $io->note('Je prends des notes avec $io->note');
        // $io->note('Je prends des notes avec $io->note');
        // $io->note('Je prends des notes avec $io->note');
        // $io->note('Je prends des notes avec $io->note');

        // $io->caution('Message caution [caution] fond rouge');

        // $io->warning('Message warning [warning] fond jaune');

        // $io->success('message success [OK] fond vert');

        // $io->error('Message error [ERROR] fond rouge');

        // $io->note();
        // $io->caution();

        // $io->warning();
        // $io->error();
        // $io->success();
        $io->info('info en vert');



        // $helper = $this->getHelper('question');
        // $question = new Question('Quel est votre mot de passe?');
        // $question->setValidator(function($password) {
        //     if(trim($password) == '') {
        //         throw new \RuntimeException('Cette valeur ne doit pas être vide!');
        //     }

        //     return $password;
        // });
        // $question->setHidden(true);
        // $question->setHiddenFallback(false);
        // $question->setMaxAttempts(3);

        // $password = $helper->ask($input, $output, $question);

        // $io = new SymfonyStyle($input, $output);
        // $io->note([
        //     'Lorem ipsum',
        //     'Lorem ipsum',
        //     'Lorem ipsum'
        // ]);

        // $io->caution('Lorem Ipsum!');

        // $io->ask('Quel est le nombre de travailleurs?', 1, function($number) {
        //     if(!is_numeric($number)) {
        //         throw new \RuntimeException('Cette valeur doit être numérique');
        //     }

        //     return $number;
        // });

        // $io->askHidden('Quel est votre mot de passe?', function($password) {
        //     if(trim($password == '')) {
        //         throw new \RuntimeException('Cette valeur ne doit pas être vide!');
        //     }
        // });

        // $io->confirm('Redemarrer le serveur?', true);

        // $io->choice('Select the queue to analyze', ['queue1', 'queue2', 'queue3'], 'queue1');

        // $helper = $this->getHelper('question');
        // $choicesQuestion = new ChoiceQuestion('Select the queue to analyze', ['queue1', 'queue2', 'queue3'], 'queue2');
        // $choicesQuestion->setAutocompleterValues(['queue1']);
        // $queue = $helper->ask($input, $output, $choicesQuestion);

        // $output->writeln('<fg=blue;bg=yellow;options=bold>'.$queue.'</>');

        return Command::SUCCESS;
    }
}