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

/**
 * Commande CLI `app:dish:cleanup`.
 *
 * Permet de supprimer les plats anciens et inutiles de la base de données.
 * Fonctionnalités principales :
 *  - Suppression conditionnelle par ancienneté ou nom de plat.
 *  - Mode dry-run pour simuler la suppression sans modification réelle.
 *  - Confirmation interactive avant suppression définitive.
 *  - Affichage stylé et tableau récapitulatif des plats concernés.
 *  - Support des modes verbose et debug pour messages détaillés.
 */
class DishCleanupCommand extends Command
{
    // Nom de la commande CLI : 'php bin/console app:dish:cleanup'
    protected static $defaultName = 'app:dish:cleanup';

    private DishRepository $dishRepository;

    public function __construct(DishRepository $dishRepository)
    {
        // Injection du repository pour accéder aux plats en base
        $this->dishRepository = $dishRepository;
        parent::__construct();
    }

    /**
     * Configure la commande : description, argument optionnel, et option dry-run
     */
    public function configure(): void
    {
        $this
            ->setDescription('Deletes old and useless dish from the database')
            ->addArgument('dishname', InputArgument::OPTIONAL) // permet de cibler un plat précis
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run'); // simule la suppression sans exécuter
    }

    /**
     * Exécution principale de la commande
     * - Gestion des entrées utilisateurs
     * - Affichage des plats à supprimer
     * - Suppression conditionnelle ou simulation (dry-run)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Message d’accueil en mode verbose
        if ($output->isVerbose()) {
            $io->title('Bienvenue dans la commande de nettoyage des plats!');
        }

        $questionHelper = $this->getHelper('question');

        // Récupération du nom du plat si fourni en argument
        $dishname = $input->getArgument('dishname');
        if (!$dishname) {
            // Confirmation utilisateur pour supprimer un plat précis
            $confirmation = new ConfirmationQuestion('Voulez-vous supprimer un plat précis?', false);
            if ($questionHelper->ask($input, $output, $confirmation)) {
                $dishname = $questionHelper->ask($input, $output, new Question('Veuillez indiquer le nom du plat:'));
            }
        }

        // Récupération de l’ancienneté minimale des plats à supprimer (par défaut 7 jours)
        $daysBeforeRejected = $io->ask('Indiquez l\'ancienneté des plats à supprimer (en jours)', 7);

        if ($input->getOption('dry-run')) {
            // Mode simulation : seulement compte des plats concernés
            $io->note('Dry mode enabled');
            $count = $this->dishRepository->countOldRejected($daysBeforeRejected);
        } else {
            // Récupération des plats éligibles à la suppression
            $dishs = $this->dishRepository->getDisplayDataOldRejected($daysBeforeRejected, $dishname);

            if (!$dishs) {
                $io->error('Il n\'y a aucun plat à supprimer!');
                // Message debug détaillé si activé
                if ($output->isDebug()) {
                    $debugFormatter = $this->getHelper('debug_formatter');
                    $process = new Process('debug');
                    $output->writeln($debugFormatter->start(
                        spl_object_hash($process),
                        "<error>Pas de plat avec une ancienneté supérieure à {$daysBeforeRejected} jours</error>"
                    ));
                }
                return Command::SUCCESS;
            }

            // Formatage des dates pour affichage
            array_walk_recursive($dishs, fn(&$v) => $v instanceof DateTime ? $v->format('d/m/Y') : $v);

            // Style personnalisé pour l’affichage
            $output->getFormatter()->setStyle('confirm', new OutputFormatterStyle('bright-white', '', ['underscore']));
            $output->writeln('<confirm>Les plats que vous souhaitez supprimer sont:</confirm>');

            // Affichage des plats dans un tableau
            $io->table(['id', 'Nom', 'Crée le'], $dishs);

            $io->warning('Attention : la suppression des plats est définitive');

            // Confirmation finale avant suppression
            $question = new ConfirmationQuestion('Confirmez-vous la suppression?', true);
            if ($questionHelper->ask($input, $output, $question)) {
                // $count = $this->dishRepository->deleteOldRejected($daysBeforeRejected, $dishname);
                $output->getFormatter()->setStyle('confirm', new OutputFormatterStyle('bright-white', 'bright-green', ['bold', 'underscore']));
                $output->writeln('<confirm>Les plats ont bien été supprimés!</confirm>');

                if ($output->isVerbose()) {
                    $output->writeln('<options=bold,underscore>Vous pouvez répéter l\'opération quand vous voulez</>');
                }
            } else {
                $io->error('La suppression des plats anciens a été annulée!');
            }
        }

        // Message de remerciement en mode very verbose
        if ($output->isVeryVerbose()) {
            $output->getFormatter()->setStyle('thanks', new OutputFormatterStyle('white', 'red', ['bold']));
            $output->writeln('<thanks>Merci !</thanks>');
        }

        return Command::SUCCESS;
    }
}