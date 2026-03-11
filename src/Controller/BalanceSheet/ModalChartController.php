<?php

namespace App\Controller\BalanceSheet;

use App\Repository\FoodGroupParentRepository;
use App\Repository\NutrientRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ModalChartController.php
 *
 * Controller qui gère les **charts modales** pour les moyennes nutritionnelles
 * dans l’interface du bilan alimentaire.
 *
 * Ce controller fournit :
 * - Graphiques pour les groupes alimentaires (FGP) moyens
 * - Graphiques pour les nutriments moyens
 *
 * Toutes les méthodes utilisent Symfony UX Chart.js pour générer les charts doughnut.
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 *
 * @package App\Controller\BalanceSheet
 */
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class ModalChartController extends AbstractController
{
    /**
     * Génère un diagramme en doughnut pour les **groupes alimentaires (FGP)** ou les **nutriments**.
     *
     * @param string $type Type de graphique : 'fgp' pour Food Group Parent, 'nutrient' pour nutriments
     * @param string $averageData JSON encodé des moyennes (quantités par FGP ou nutriments)
     * @param bool $displayLabel Si vrai, affiche les labels dans la vue
     * @param string $title Titre du graphique
     * 
     * @return \Symfony\Component\HttpFoundation\Response Vue contenant le graphique doughnut
     */
    #[Route(
        '/{type}/{averageData}/{title}/{displayLabel}',
        name: 'app_balance_sheet_modal_chart',
        methods: ['GET'],
        requirements: ['type' => 'fgp|nutrient', 'displayLabel' => '0|1']
    )]
    public function chart(
        ChartBuilderInterface $chartBuilder,
        SerializerInterface $serializer,
        FoodGroupParentRepository $foodGroupParentRepository,
        NutrientRepository $nutrientRepository,
        string $type,
        string $averageData,
        bool $displayLabel,
        string $title
    ): Response {
        // Décodage JSON des données envoyées
        $averageData = $serializer->decode($averageData, 'json');

        // Création du chart doughnut
        $chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

        // Selon le type, on prépare labels et couleurs
        if ($type === 'fgp') {
            $labels = [];
            $colors = [];
            $fgpColors = [];

            foreach ($averageData as $fgpAlias => $quantity) {
                $fgp = $foodGroupParentRepository->findOneByAlias($fgpAlias);
                if (!$fgp) continue; // sécurité

                $labels[] = $fgp->getName();
                $colors[] = $fgp->getColor();
                $fgpColors[$fgp->getName()] = $fgp->getColor();
            }

            // Configuration du chart
            $chart->setData([
                'labels' => $labels,
                'datasets' => [
                    ['data' => array_values($averageData), 'backgroundColor' => $colors]
                ],
            ]);

            $chart->setOptions($this->getDoughnutOptions());

            return $this->render('balance_sheet/_chart_average_fgp.html.twig', [
                'chart' => $chart,
                'fgpColors' => $fgpColors,
                'displayLabel' => $displayLabel,
                'title' => $title,
            ]);
        } elseif ($type === 'nutrient') {
            $labels = [];
            $colors = [];
            $nutrientColors = [];

            foreach ($nutrientRepository->findAll() as $nutrient) {
                $nutrientColors[$nutrient->getCode()] = $nutrient->getColor();
                $labels[] = $nutrient->getName();
            }

            $data = [];
            foreach ($nutrientColors as $code => $color) {
                if (isset($averageData[$code])) {
                    $data[] = $averageData[$code];
                    $colors[] = $color;
                }
            }

            $chart->setData([
                'labels' => $labels,
                'datasets' => [['data' => $data, 'backgroundColor' => $colors]],
            ]);

            $chart->setOptions($this->getDoughnutOptions());

            return $this->render('balance_sheet/_chart_average_nutrient.html.twig', [
                'chart' => $chart,
                'nutrientColors' => $nutrientColors,
                'displayLabel' => $displayLabel,
                'title' => $title,
            ]);
        }

        throw $this->createNotFoundException("Type de graphique inconnu : $type");
    }

    /**
     * Options communes pour tous les doughnuts.
     */
    private function getDoughnutOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '50%',
            'plugins' => ['legend' => ['display' => false]],
        ];
    }
}
