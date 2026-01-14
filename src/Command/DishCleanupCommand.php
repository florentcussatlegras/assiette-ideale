<?php

namespace App\Command;

use \DateTime;
use App\Repository\DishRepository;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class DishCleanupCommand extends Command
{
    protected static $defaultName = 'app:dish:cleanup';

    public function __construct(DishRepository $dishRepository)
    {
        $this->dishRepository = $dishRepository;

        parent::__construct();
    }

    public function configure()
    {
        $this->setDescription('Deletes old and useless dish from the database')
            ->addArgument('dishname', InputArgument::OPTIONAL)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        if($output->isVerbose()) {
            $io->title('Bienvenue dans la commande de nettoyage des plats!');
        }
        
        $questionHelper = $this->getHelper('question');

       if(!$dishname = $input->getArgument('dishname')) {
            // if($io->confirm('Voulez-vous supprimer un plat précis?', 0)) {
            //     $dishname = $io->ask('Veuillez indiquer le nom du plat:');
            // }
            $confirmation = new ConfirmationQuestion('Voulez-vous supprimer un plat précis?', false);
            if($questionHelper->ask($input, $output, $confirmation)) {
                $question = new Question('Veuillez indiquer le nom du plat:');
                $dishname = $questionHelper->ask($input, $output, $question);
            }
        }
        
        $daysBeforeRejected = $io->ask('Indiquez l\'ancienneté des plats à supprimer (en jours)', 7);

        if($input->getOption('dry-run')) {

            $io->note('Dry mode enabled');

            $count = $this->dishRepository->countOldRejected($daysBeforeRejected);

        }else{

            $dishs = $this->dishRepository->getDisplayDataOldRejected($daysBeforeRejected, $dishname);

            if(!$dishs) {

                $io->error('Il n\'y a aucun plat à supprimer!');

                if($output->isDebug()) {

                    $debugFormatter = $this->getHelper('debug_formatter');

                    $process = new Process('debug');
                    $output->writeln($debugFormatter->start(
                        spl_object_hash($process),
                        "<error>La commande n'a pas fonctionné car aucun plat n'a d'ancienneté supérieur au nombre de jours saisis</error>"
                    ));

                }
                           
            }else{

                array_walk_recursive($dishs, function(&$value, $key){
                    if($value instanceof DateTime){
                        $value = $value->format('d/m/Y');
                    }
                });
                
                $confirmStyle = new OutputFormatterStyle('bright-white', '', ['underscore']);
                $output->getFormatter()->setStyle('confirm', $confirmStyle);
                $output->writeln('<confirm>Les plats que vous souhaitez supprimer sont:</confirm>');

                $io->table(
                    ['id', 'Nom', 'Crée le'],
                    $dishs
                );
                
                $io->warning('Attention la suppression des plats est définitive');

                $questionHelper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Confirmez-vous la suppression?', true);

                // if($io->confirm('Confirmez-vous la suppression?')) {
                if($questionHelper->ask($input, $output, $question)) {
                    //$count = $this->dishRepository->deleteOldRejected($daysBeforeRejected, $dishname);
                    
                    $outputStyle = new OutputFormatterStyle('bright-white', 'bright-green', ['bold', 'underscore']);
                    $output->getFormatter()->setStyle('confirm', $outputStyle);
            
                    $output->writeln('<confirm>Les plats ont bien été supprimés!</confirm>');

                    if($output->isVerbose()) {
                        $output->writeln('<options=bold,underscore>Vous pouvez répéter l\'opération quand vous voulez</>');
                    }
                }else{
                    $io->error('La suppression des plats anciens abandonnées!');
                }
            }
        }

        if ($output->isVeryVerbose()) {
            $thanksStyle = new OutputFormatterStyle('white', 'red', ['bold']);
            $output->getFormatter()->setStyle('thanks', $thanksStyle);

            $output->writeln('<thanks>Merci !</thanks>');
        }

        return Command::SUCCESS;
    }
}