<?php

namespace App\Controller\BalanceSheet;

use App\Repository\FoodGroupParentRepository;
use App\Repository\NutrientRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/balance_sheet/modal-chart', name: 'app_balance_sheet_modal_chart_')]
class ModalChartController extends AbstractController
{
    #[Route('/fgp/{averageFgp}/{title}/{displayLabel}', name: 'fgp')]
    public function chartFgp(Request $request, ChartBuilderInterface $chartBuilder, SerializerInterface $serializer, FoodGroupParentRepository $foodGroupParentRepository, string $averageFgp, bool $displayLabel, string $title)
    {
        $averageFgp = $serializer->decode($averageFgp, 'json');
   
        $chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

        // foreach($averageFgp as $fgpAlias => $quantity) {
        //     $fgp = $foodGroupParentRepository->findOneByAlias($fgpAlias);
        //     $fgpColors[$fgp->getName()] = $fgp->getColor();
        // }

        // $chart->setData([
        //     'labels' => false,
        //     'datasets' => [
        //         [
        //             'label' => 'Groupe d\'aliment',
        //             'data' => array_values($averageFgp),
        //             'backgroundColor' => array_values($fgpColors)
        //         ]
        //     ],
        // ]);

        $data = [];
        $fgpColors = [];
        $fgpNames = [];
        $colors = [];
        $labels = [];

        foreach ($averageFgp as $fgpAlias => $quantity) {
            $fgp = $foodGroupParentRepository->findOneByAlias($fgpAlias);
            $fgpColors[$fgp->getName()] = $fgp->getColor();

            if (!$fgp) {
                continue; // sécurité
            }

            $labels[] = $fgp->getName();
            $data[] = $quantity;
            $colors[] = $fgp->getColor();
        }

        $chart->setData([
            // 'labels' => $labels,
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ]
            ],
        ]);
        
        $chart->setOptions([
            'responsive' => true,  // permet de respecter width/height du canvas
            'maintainAspectRatio' => false,
            'cutout' => '50%',      // pour réduire la taille du "trou" central
            'plugins' => [
                'legend' => [
                    'display' => false,
                ]
            ],
        ]);


        return $this->render('balance_sheet/_chart_average_fgp.html.twig', [
            'chart' => $chart,
            'fgpColors' => $fgpColors,
            'displayLabel' => $displayLabel,
            'title' => $title,
        ]);
    }

    #[Route('/fgp/{averageNutrient}/{title}/{displayLabel}', name: 'nutrient')]
    public function chartNutrient(Request $request, ChartBuilderInterface $chartBuilder, SerializerInterface $serializer, NutrientRepository $nutrientRepository, string $averageNutrient, bool $displayLabel, string $title)
    {
        $averageNutrient = $serializer->decode($averageNutrient, 'json');
   
        $chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
    
        foreach($nutrientRepository->findAll() as $nutrient) {
            $nutrientColors[$nutrient->getCode()] = $nutrient->getColor();
            $labels[] = $nutrient->getName();
        }

        $data = [];
        $colors = [];

        foreach ($nutrientColors as $code => $color) {
            if (isset($averageNutrient[$code])) {
                $data[] = $averageNutrient[$code];
                $colors[] = $color;
            }
        }

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ]
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,  // permet de respecter width/height du canvas
            'maintainAspectRatio' => false,
            'cutout' => '50%',      // pour réduire la taille du "trou" central
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ]);

        return $this->render('balance_sheet/_chart_average_nutrient.html.twig', [
            'chart' => $chart,
            'nutrientColors' => $nutrientColors,
            'displayLabel' => $displayLabel,
            'title' => $title,
        ]);
    }
}