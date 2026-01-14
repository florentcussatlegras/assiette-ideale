<?php

namespace App\Controller\Evolution;

use App\Service\MealUtil;
use App\Repository\MealRepository;
use App\Repository\NutrientRepository;
use Symfony\UX\Chartjs\Model\Chart;
use App\Service\BalanceSheetFeature;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\FoodGroupParentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/evolution/chart', name: 'app_evolution_chart_')]
class ChartController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(Request $request)
    {
        if($request->query->has('start')) {
            $start = \DateTime::createFromFormat('Y-m-d', $request->query->get('start'));
            // $start = $start->format('Y-m-d');
        }else{
            $start = new \DateTime('-1 day');
        }

        if($request->query->has('end')) {
            $end = \DateTime::createFromFormat('Y-m-d', $request->query->get('end'));
            // $end = $end->format('Y-m-d');
        }else{
            $end = new \DateTime('-1 day');
        }
        $start = $start->format('Y-m-d');
        $end = $end->format('Y-m-d');
        // $response = $this->forward('App\Controller\Evolution\ChartController::chart'.$request->query->get('category'), [], [
        //     'start' => $request->query->get('start') ?? null,
        //     'end' => $request->query->get('end') ?? null,
        // ]);

        $response = $this->forward('App\Controller\Evolution\ChartController::chart'.$request->query->get('category'), [], [
            'start' => $start,
            'end' => $end,
        ]);

        return $response;
    }

    #[Route('/energy', name: 'energy')]
    public function chartEnergy(Request $request, ChartBuilderInterface $chartBuilder, MealUtil $mealUtil, BalanceSheetFeature $balanceSheetFeature)
    {
        $user = $this->getUser();
      
        $start = \DateTime::createFromFormat('Y-m-d', !empty($request->query->get('start')) ? $request->query->get('start') : $user->getRegisterAt());
        $end = \DateTime::createFromFormat('Y-m-d', !empty($request->query->get('end')) ? $request->query->get('end') : date('m/d/Y'));

        $meals = $balanceSheetFeature->getMealsForAPeriod($start, $end);
        if(empty($meals)) {
            return new Response('Aucun repas trouvés pour cette période');    
        }

        $results = [];

        foreach ($meals as $dateDay => $list) {
            $tabDates[] = $dateDay;
            $results[$dateDay] = 0;
            foreach($list as $meal) {
                $results[$dateDay] += $mealUtil->getEnergy($meal);
            }
        }     
        
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
    
        $chart->setData([
            'labels' => $tabDates,
            'datasets' => [
                [
                    'label' => 'Energy',
                    'data' => $results,
                ]
            ],
        ]);  

        return $this->render('evolution/charts/_chart_energy_evolution.html.twig', [
            'chart' => $chart,
            'start' => $start,
            'end' => $end,
        ]);
    }

    #[Route('/nutrient', name: 'nutrient')]
    public function chartNutrient(Request $request, ChartBuilderInterface $chartBuilder, NutrientRepository $nutrientRepository, BalanceSheetFeature $balanceSheetFeature, MealUtil $mealUtil)
    {
        $user = $this->getUser();

        $start = \DateTime::createFromFormat('Y-m-d', !empty($request->query->get('start')) ? $request->query->get('start') : $user->getRegisterAt());
        $end = \DateTime::createFromFormat('Y-m-d', !empty($request->query->get('end')) ? $request->query->get('end') : date('m/d/Y'));

        $meals = $balanceSheetFeature->getMealsForAPeriod($start, $end);
        if(empty($meals)) {
            return new Response('Aucun repas trouvés pour cette période');    
        }

        foreach ($meals as $dateDay => $list) {
            $tabDates[] = $dateDay; 
            $results['protein'][$dateDay] = 0;
            $results['lipid'][$dateDay] = 0;
            $results['carbohydrate'][$dateDay] = 0;
            $results['sodium'][$dateDay] = 0;
            foreach($list as $meal) {
                $nutrientsValues = $mealUtil->getNutrients($meal);
                $results['protein'][$dateDay] += $nutrientsValues['protein'];
                $results['lipid'][$dateDay] += $nutrientsValues['lipid'];
                $results['carbohydrate'][$dateDay] += $nutrientsValues['carbohydrate'];
                $results['sodium'][$dateDay] += $nutrientsValues['sodium'];
            }
        }
 
        foreach($results as $nutrientAlias => $values) {
            $nutrient = $nutrientRepository->findOneByCode($nutrientAlias);
            $datasets[$nutrientAlias] = [
                'label' => $nutrient->getName(),
                'backgroundColor' => $nutrient->getColor(),
                'borderColor' => $nutrient->getColor(),
                'data' => array_values($values),
            ];
        }
  
        foreach($datasets as $nutrientAlias => $dataset) {
            $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
            $chart->setData(
                [
                    'labels' => $tabDates,
                    'datasets' => [$dataset],
                ],
            ); 
            // ${'chart'.$fgpAlias} = $chart;
            $charts[$nutrientAlias] = $chart;
        }    

        return $this->render('evolution/charts/_chart_nutrient_evolution.html.twig', [
            'charts' => $charts,
            'start' => $start,
            'end' => $end,
        ]);
    }

    #[Route('/weight', name: 'weight')]
    public function chartWeight(Request $request, ChartBuilderInterface $chartBuilder)
    {
        $user = $this->getUser();

        if(empty($user->getWeightEvolution())) {
            return new Response('<span class="text-center">Vous n\'avez archivé aucune valeur de poids sur cette période</span>');
        }

        if($request->query->has('start') && $request->query->has('end') && !empty($request->query->get('start')) && !empty($request->query->get('end'))) {

            $start = \DateTime::createFromFormat('Y-m-d', $request->query->get('start'));
            $end = \DateTime::createFromFormat('Y-m-d', $request->query->get('end'));

            $datas = array_filter($user->getWeightEvolution(), function($date) use ($start, $end) {
                $date = \DateTime::createFromFormat('y-m-d', $date);
                return ($date->diff($start)->format('%R%a') <= 0 && $date->diff($end)->format('%R%a') >= 0);
            }, ARRAY_FILTER_USE_KEY);

            $dates = array_keys($datas);
            $weights = array_values($datas);

        }else{

            $start = $user->getRegisterAt();
            $end = date('m/d/Y');

            $dates = array_keys($user->getWeightEvolution());
            $weights = array_values($user->getWeightEvolution());

        }
    
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Poids',
                    'data' => $weights,
                ]
            ],
        ]);      

        return $this->render('evolution/charts/_chart_weight_evolution.html.twig', [
            'chart' => $chart,
            'start' => $start,
            'end' => $end,
        ]);
    }

    #[Route('/fgp/{start?}/{end?}', name: 'fgp')]
    public function chartFgp(Request $request, ChartBuilderInterface $chartBuilder, BalanceSheetFeature $balanceSheetFeature, MealRepository $mealRepository, FoodGroupParentRepository $foodGroupParentRepository, MealUtil $mealUtil, ?string $start, ?string $end)
    {
        $user = $this->getUser();

        $start = \DateTime::createFromFormat('Y-m-d', !empty($request->query->get('start')) ? $request->query->get('start') : $user->getRegisterAt());
        $end = \DateTime::createFromFormat('Y-m-d', !empty($request->query->get('end')) ? $request->query->get('end') : date('m/d/Y'));

        $meals = $balanceSheetFeature->getMealsForAPeriod($start, $end);
        if(empty($meals)) {
            return new Response('Aucun repas trouvés pour cette période');    
        }

        $results = [];

        foreach ($meals as $dateDay => $list) {
            $tabDates[] = $dateDay; 
            foreach($foodGroupParentRepository->findAll() as $fgp) {
                $results[$fgp->getAlias()][$dateDay] = 0;
            }
            foreach($list as $meal) {
                $fgpValues = $mealUtil->getFoodGroupParents($meal);

                foreach($fgpValues as $fgpAlias => $value) {
                    $results[$fgpAlias][$dateDay] += $value;
                }
            }
        }
 
        foreach($results as $fgpAlias => $values) {
            $fgp = $foodGroupParentRepository->findOneByAlias($fgpAlias);
            $datasets[$fgpAlias] = [
                'label' => $fgp->getName(),
                'backgroundColor' => $fgp->getColor(),
                'borderColor' => $fgp->getColor(),
                'data' => array_values($values),
            ];
        }
  
        foreach($datasets as $fgpAlias => $dataset) {
            $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
            $chart->setData(
                [
                    'labels' => $tabDates,
                    'datasets' => [$dataset],
                ],
            ); 
            $charts[$fgpAlias] = $chart;
        }
        
        return $this->render('evolution/charts/_chart_fgp_evolution.html.twig', [
            'charts' => $charts,
            'start' => $start,
            'end' => $end,
        ]);
    }
}