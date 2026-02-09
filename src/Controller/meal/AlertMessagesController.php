<?php

namespace App\Controller\meal;

use App\Service\AlertFeature;
use App\Service\BalanceSheetFeature;
use App\Controller\AlertUserController;
use App\Service\LabelResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AlertMessagesController extends AbstractController implements AlertUserController
{
    #[Route('/alert-messages', name: 'app_alert_messages', methods: ['GET'])]
    public function index(Request $request, BalanceSheetFeature $balanceSheetFeature, AlertFeature $alertFeature, LabelResolver $labelResolver)
    {
        $user = $this->getUser();

        $dateStart = $request->query->get('start');
        $dateEnd = $request->query->get('end');

        $averageDailyEnergy = $balanceSheetFeature->averageDailyEnergyForAPeriod($dateStart, $dateEnd);
        $averageDailyFgp = $balanceSheetFeature->averageDailyFgpForAPeriod($dateStart, $dateEnd);
        $averageDailyNutrient = $balanceSheetFeature->averageDailyNutrientForAPeriod($dateStart, $dateEnd);
        $balanceSheetAlerts = $alertFeature->getBalanceSheetAlerts($averageDailyEnergy, $averageDailyNutrient, $averageDailyFgp);

        $resolved = [];

        foreach ($balanceSheetAlerts as $key => $alert) {
            $balanceSheetAlertsResolved[] = [
                'key'   => $key,
                'alert' => $alert,
                'label' => $labelResolver->resolve($key)
            ];
        }

        // Tri par priorité (plus grave d'abord)
        usort($balanceSheetAlertsResolved, function ($a, $b) {
            return ($a['alert']->getPriority() ?? 0) <=> ($b['alert']->getPriority() ?? 0);
        });

        return $this->render('meals/week/_alerts_messages_list.html.twig', [
            'balanceSheetAlerts' => $balanceSheetAlertsResolved,
        ]);
    }
}