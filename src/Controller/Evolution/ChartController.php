<?php

namespace App\Controller\Evolution;

use App\Service\MealUtil;
use App\Repository\MealRepository;
use App\Repository\NutrientRepository;
use Symfony\UX\Chartjs\Model\Chart;
use App\Service\BalanceSheetFeature;
use App\Repository\FoodGroupParentRepository;
use App\Repository\WeightLogRepository;
use App\Service\NutrientHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * ChartController.php
 *
 * Controller gérant les **charts d'évolution** pour l'utilisateur :
 * - énergie quotidienne
 * - nutriments (protéines, lipides, glucides, sodium)
 * - poids
 * - groupes alimentaires (FGP)
 *
 * Toutes les routes de ce controller nécessitent que l'utilisateur soit authentifié.
 *
 * Routes principales :
 * - /evolution/chart/energy     -> évolution énergétique
 * - /evolution/chart/nutrient   -> évolution des nutriments
 * - /evolution/chart/weight     -> évolution du poids
 * - /evolution/chart/fgp        -> évolution des groupes alimentaires
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 *
 * @package App\Controller\Evolution
 */
#[Route('/evolution/chart', name: 'app_evolution_chart_')]
class ChartController extends AbstractController
{
    /**
     * Page d'accueil des charts d'évolution : redirige vers le chart correspondant
     * selon le paramètre 'category' GET : energy, nutrient, weight, fgp
     *
     * @param Request $request
     * 
     * @return Response
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Récupération des dates
        [$start, $end] = $this->getStartEndDates($request);

        // Récupération de la catégorie demandée
        $category = $request->query->get('category', 'energy');

        // Redirection vers la méthode correspondante
        return $this->forward('App\Controller\Evolution\ChartController::chart' . ucfirst($category), [], [
            'start' => $start,
            'end' => $end,
        ]);
    }

    /**
     * Chart de l'évolution énergétique quotidienne
     *
     * @param Request $request
     * @param ChartBuilderInterface $chartBuilder
     * @param MealUtil $mealUtil
     * @param BalanceSheetFeature $balanceSheetFeature
     * 
     * @return Response
     */
    #[Route('/energy', name: 'energy', methods: ['GET'])]
    public function chartEnergy(Request $request, ChartBuilderInterface $chartBuilder, MealUtil $mealUtil, BalanceSheetFeature $balanceSheetFeature): Response
    {
        // Récupère les dates de début et de fin depuis la requête GET
        // La méthode getStartEndDates fournit des valeurs par défaut si start/end ne sont pas spécifiés
        [$start, $end] = $this->getStartEndDates($request);

        // Récupère tous les repas de l'utilisateur sur la période spécifiée
        $meals = $balanceSheetFeature->getMealsForAPeriod($start, $end);

        // Vérifie si aucun repas n'a été trouvé
        if (empty($meals)) {
            return new Response('Aucun repas trouvés pour cette période');
        }

        // Initialisation des tableaux pour construire le graphique
        $tabDates = []; // Contiendra toutes les dates des repas (axe X)
        $results = [];  // Contiendra l'énergie totale par jour (axe Y)

        // Parcourt chaque jour et ses repas
        foreach ($meals as $dateDay => $list) {
            $tabDates[] = $dateDay; // Ajoute la date à l'axe X
            $results[$dateDay] = 0; // Initialise l'énergie totale du jour à 0

            // Cumule l'énergie de chaque repas pour le jour
            foreach ($list as $meal) {
                $results[$dateDay] += $mealUtil->getEnergy($meal);
            }
        }

        // Création du graphique ChartJS de type LINE pour représenter l'évolution de l'énergie
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

        // Paramétrage des données du graphique
        $chart->setData([
            'labels' => $tabDates,                       // Axe X : dates
            'datasets' => [
                [
                    'label' => 'Énergie',               // Légende du dataset
                    'data' => $results,                 // Axe Y : énergie par jour
                ],
            ],
        ]);

        return $this->render('evolution/charts/_chart_energy_evolution.html.twig', [
            'chart' => $chart,
            'start' => $start,
            'end' => $end,
        ]);
    }

    /**
     * Chart de l'évolution des nutriments (protein, lipid, carbohydrate, sodium)
     *
     * @param Request $request
     * @param ChartBuilderInterface $chartBuilder
     * @param NutrientRepository $nutrientRepository
     * @param BalanceSheetFeature $balanceSheetFeature
     * @param MealUtil $mealUtil
     * 
     * @return Response
     */
    #[Route('/nutrient', name: 'nutrient', methods: ['GET'])]
    public function chartNutrient(Request $request, ChartBuilderInterface $chartBuilder, NutrientRepository $nutrientRepository, BalanceSheetFeature $balanceSheetFeature, MealUtil $mealUtil): Response
    {
        // Récupère les dates de début et de fin à partir de la requête (GET start/end)
        // La méthode getStartEndDates gère les valeurs par défaut si start/end ne sont pas fournies
        [$start, $end] = $this->getStartEndDates($request);

        // Récupère tous les repas de l'utilisateur sur la période spécifiée
        $meals = $balanceSheetFeature->getMealsForAPeriod($start, $end);

        // Vérifie si aucun repas n'a été trouvé
        if (empty($meals)) {
            return new Response('Aucun repas trouvés pour cette période');
        }

        // Initialisation des tableaux pour construire le graphique
        $tabDates = []; // Contiendra toutes les dates des repas (axe X)
        $nutrientsKeys = NutrientHandler::NUTRIENTS; // Nutriments suivis
        $results = []; // Stocke les quantités cumulées par nutriment et par date

        // Parcourt tous les repas pour chaque jour
        foreach ($meals as $dateDay => $list) {
            $tabDates[] = $dateDay; // Ajoute la date à l'axe X

            // Initialise les valeurs des nutriments à 0 pour cette date
            foreach ($nutrientsKeys as $nutrient) {
                $results[$nutrient][$dateDay] = 0;
            }

            // Parcourt chaque repas du jour pour accumuler les nutriments
            foreach ($list as $meal) {
                // Récupère les valeurs des nutriments pour le repas
                $nutrientValues = $mealUtil->getNutrients($meal);

                // Cumule les valeurs de chaque nutriment pour la date
                foreach ($nutrientsKeys as $nutrient) {
                    $results[$nutrient][$dateDay] += $nutrientValues[$nutrient];
                }
            }
        }

        // Création des graphiques ChartJS pour chaque nutriment
        $charts = [];
        foreach ($results as $nutrientAlias => $values) {
            // Récupère l'entité Nutrient pour accéder au nom et à la couleur
            $nutrient = $nutrientRepository->findOneByCode($nutrientAlias);

            // Prépare le dataset pour ChartJS
            $dataset = [
                'label' => $nutrient->getName(),          // Nom du nutriment pour la légende
                'data' => array_values($values),          // Valeurs cumulées par date
                'backgroundColor' => $nutrient->getColor(), // Couleur de fond de la ligne
                'borderColor' => $nutrient->getColor(),     // Couleur de la bordure de la ligne
            ];

            // Création du graphique ChartJS de type LINE pour ce nutriment
            $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
            $chart->setData([
                'labels' => $tabDates,          // Axe X
                'datasets' => [$dataset],       // Dataset correspondant au nutriment
            ]);

            // Stocke le graphique dans un tableau associatif par nutriment
            $charts[$nutrientAlias] = $chart;
        }

        return $this->render('evolution/charts/_chart_nutrient_evolution.html.twig', [
            'charts' => $charts,
            'start' => $start,
            'end' => $end,
        ]);
    }

    /**
     * Chart de l'évolution du poids
     *
     * @param Request $request
     * @param ChartBuilderInterface $chartBuilder
     * @param WeightLogRepository $weightLogRepository
     * @return Response
     */
    #[Route('/weight', name: 'weight', methods: ['GET'])]
    public function chartWeight(Request $request, ChartBuilderInterface $chartBuilder, WeightLogRepository $weightLogRepository): Response
    {
        /** @var App\Entity\User|null $user */
        $user = $this->getUser();

        // Récupère les dates de début et de fin ainsi que les logs de poids pour l'utilisateur
        // getWeightLogs renvoie un tableau : [$start, $end, $logs]
        [$start, $end, $logs] = $this->getWeightLogs($request, $weightLogRepository, $user);

        // Vérifie si aucun log n'a été trouvé pour cette période
        if (empty($logs)) {
            return new Response(
                '<span class="text-center">Vous n\'avez archivé aucune valeur de poids sur cette période</span>'
            );
        }

        // Initialisation des tableaux pour les labels (dates) et les données (poids)
        $dates = [];   // Contiendra toutes les dates des logs pour le graphique
        $weights = []; // Contiendra les poids correspondants aux dates

        // Parcourt tous les logs pour remplir les tableaux
        foreach ($logs as $log) {
            $dates[] = $log->getCreatedAt()->format('d/m/Y'); // Formate la date pour l'affichage sur l'axe X
            $weights[] = $log->getWeight();                  // Ajoute le poids correspondant à cette date
        }

        // Création du graphique Chart.js pour l'évolution du poids
        // createWeightChart prend : ChartBuilder, poids idéal, labels (dates), valeurs (poids)
        $chart = $this->createWeightChart(
            $chartBuilder,
            $user->getIdealWeight(), // Poids idéal pour afficher la zone cible
            $dates,                  // Labels pour l'axe X
            $weights                 // Valeurs pour l'axe Y
        );

        return $this->render('evolution/charts/_chart_weight_evolution.html.twig', [
            'chart' => $chart,
            'start' => $start,
            'end' => $end,
        ]);
    }

    /**
     * Chart de l'évolution par Food Group Parent
     *
     * @param Request $request
     * @param ChartBuilderInterface $chartBuilder
     * @param BalanceSheetFeature $balanceSheetFeature
     * @param MealRepository $mealRepository
     * @param FoodGroupParentRepository $foodGroupParentRepository
     * @param MealUtil $mealUtil
     * @param string|null $start
     * @param string|null $end
     * 
     * @return Response
     */
    #[Route('/fgp/{start?}/{end?}', name: 'fgp', methods: ['GET'])]
    public function chartFgp(
        Request $request,
        ChartBuilderInterface $chartBuilder,
        BalanceSheetFeature $balanceSheetFeature,
        FoodGroupParentRepository $foodGroupParentRepository,
        MealUtil $mealUtil,
        ?string $start,
        ?string $end
    ): Response {
        // Récupère les dates de début et de fin à partir des paramètres GET ou valeurs par défaut
        [$start, $end] = $this->getStartEndDates($request);

        // Récupère tous les repas de l'utilisateur pour la période spécifiée
        $meals = $balanceSheetFeature->getMealsForAPeriod($start, $end);

        // Vérifie si aucun repas n'a été trouvé pour cette période
        if (empty($meals)) {
            return new Response('Aucun repas trouvés pour cette période');
        }

        // Initialisation des tableaux pour les dates et les résultats
        $tabDates = [];   // Contiendra toutes les dates de la période
        $results = [];    // Contiendra les quantités par FGP pour chaque date

        foreach ($meals as $dateDay => $list) {
            $tabDates[] = $dateDay; // Ajoute la date au tableau des labels pour le graphique

            // 1️⃣ Initialisation des valeurs FGP pour cette date
            // Parcourt tous les groupes alimentaires et met leur quantité à 0 pour la date courante
            foreach ($foodGroupParentRepository->findAll() as $fgp) {
                $results[$fgp->getAlias()][$dateDay] = 0;
            }

            // 2️⃣ Parcourt tous les repas de la date pour accumuler les quantités par FGP
            foreach ($list as $meal) {
                // Récupère la répartition des FGP pour le repas courant
                $fgpValues = $mealUtil->getFoodGroupParents($meal);

                // Ajoute les quantités de chaque FGP au total de la journée
                foreach ($fgpValues as $fgpAlias => $value) {
                    $results[$fgpAlias][$dateDay] += $value;
                }
            }
        }

        // Tableau final des graphiques par FGP
        $charts = [];

        // Transformation des résultats en datasets Chart.js
        foreach ($results as $fgpAlias => $values) {
            // Récupère le groupe alimentaire correspondant pour obtenir nom et couleur
            $fgp = $foodGroupParentRepository->findOneByAlias($fgpAlias);

            // Prépare le dataset pour le graphique Chart.js
            $dataset = [
                'label' => $fgp->getName(),          // Nom du FGP affiché dans la légende
                'data' => array_values($values),     // Valeurs pour chaque date
                'backgroundColor' => $fgp->getColor(), // Couleur de remplissage
                'borderColor' => $fgp->getColor(),     // Couleur de la ligne
            ];

            // Création du graphique Chart.js pour ce FGP
            $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

            // Injection des labels (dates) et des datasets (quantités)
            $chart->setData([
                'labels' => $tabDates,
                'datasets' => [$dataset],
            ]);

            // Stocke le graphique dans le tableau final avec l'alias du FGP comme clé
            $charts[$fgpAlias] = $chart;
        }

        return $this->render('evolution/charts/_chart_fgp_evolution.html.twig', [
            'charts' => $charts,
            'start' => $start,
            'end' => $end,
        ]);
    }

    // -------------------- Private Helpers --------------------

    /**
     * Récupère les dates de début et fin depuis la requête ou valeurs par défaut
     *
     * @param Request $request
     * @return array [DateTime $start, DateTime $end]
     */
    private function getStartEndDates(Request $request): array
    {
        /** @var App\Entity\User|null $user */
        $user = $this->getUser();

        $start = $request->query->get('start') ?: $user->getRegisterAt()->format('Y-m-d');
        $end = $request->query->get('end') ?: date('Y-m-d');

        $start = \DateTime::createFromFormat('Y-m-d', $start);
        $end = \DateTime::createFromFormat('Y-m-d', $end);

        return [$start, $end];
    }

    /**
     * Récupère les logs de poids pour un utilisateur selon filtres
     *
     * @param Request $request
     * @param WeightLogRepository $weightLogRepository
     * @param \App\Entity\User $user
     * @return array [DateTime $start, DateTime $end, array $logs]
     */
    private function getWeightLogs(Request $request, WeightLogRepository $weightLogRepository, $user): array
    {
        if ($request->query->has('start') && $request->query->has('end')) {
            $start = \DateTime::createFromFormat('Y-m-d', $request->query->get('start'));
            $end = \DateTime::createFromFormat('Y-m-d', $request->query->get('end'));
            $logs = $weightLogRepository->findByUserBetweenDates($user, $start, $end);
        } else {
            $logs = $weightLogRepository->findByUserOrdered($user);
            $start = $user->getRegisterAt();
            $end = new \DateTime();
        }

        return [$start, $end, $logs];
    }

    /**
     * Création d'un graphique poids avec zone idéale
     *
     * @param ChartBuilderInterface $chartBuilder
     * @param float $idealWeight
     * @param array $dates
     * @param array $weights
     * @return Chart
     */
    private function createWeightChart(ChartBuilderInterface $chartBuilder, float $idealWeight, array $dates, array $weights): Chart
    {
        // Création d'un graphique ChartJS de type LINE pour représenter l'évolution du poids
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

        // Définition de la zone idéale autour du poids cible (+/- 5 kg)
        $idealMin = $idealWeight - 5; // Limite basse de la zone idéale
        $idealMax = $idealWeight + 5; // Limite haute de la zone idéale

        // Prépare des tableaux de valeurs pour chaque dataset correspondant aux lignes horizontales du graphique
        $idealMinData = array_fill(0, count($dates), $idealMin);      // Remplit le dataset min avec la même valeur pour toutes les dates
        $idealMaxData = array_fill(0, count($dates), $idealMax);      // Remplit le dataset max avec la même valeur pour toutes les dates
        $idealCenterData = array_fill(0, count($dates), $idealWeight); // Ligne centrale représentant le poids idéal

        // Configuration des données du graphique
        $chart->setData([
            'labels' => $dates, // Axe X : dates des relevés
            'datasets' => [
                // Ligne MIN : base invisible pour le remplissage de la zone idéale
                [
                    'label' => 'Zone idéale min',
                    'data' => $idealMinData,
                    'borderColor' => 'rgba(0,0,0,0)',
                    'backgroundColor' => 'rgba(0,0,0,0)',
                    'pointRadius' => 0,
                    'fill' => false,
                ],

                // Ligne MAX : zone idéale colorée entre MIN et MAX
                [
                    'label' => 'Zone idéale',
                    'data' => $idealMaxData,
                    'borderColor' => 'rgba(0,0,0,0)',
                    'backgroundColor' => 'rgba(34,197,94,0.2)', // vert clair transparent
                    'pointRadius' => 0,
                    'fill' => '-1', // remplit la zone vers le dataset précédent (min)
                ],

                // Ligne centrale : poids idéal
                [
                    'label' => 'Poids idéal',
                    'data' => $idealCenterData,
                    'borderColor' => '#22c55e', // vert foncé
                    'borderDash' => [6, 6],     // ligne pointillée
                    'pointRadius' => 0,
                    'fill' => false,
                ],

                // Courbe réelle de l'utilisateur
                [
                    'label' => 'Poids',
                    'data' => $weights,
                    'borderColor' => '#3b82f6',                   // bleu
                    'backgroundColor' => 'rgba(59,130,246,0.2)', // remplissage transparent sous la courbe
                    'tension' => 0.3,                            // courbe légèrement lissée
                    'fill' => false,
                ],
            ],
        ]);

        // Configuration des options du graphique
        $chart->setOptions([
            'elements' => [
                'line' => ['borderWidth' => 2], // largeur des lignes
            ],
            'plugins' => [
                'legend' => ['display' => true], // afficher la légende
            ],
        ]);

        return $chart;
    }
}
