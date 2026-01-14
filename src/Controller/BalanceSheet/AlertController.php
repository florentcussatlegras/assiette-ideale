<?php

namespace App\Controller\BalanceSheet;

use App\Service\AlertFeature;
use App\Entity\Alert\LevelAlert;
use App\Service\BalanceSheetFeature;
use App\Controller\AlertUserController;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\FoodGroupParentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/balance_sheet/alert', name: 'app_balance_sheet_')]
class AlertController extends AbstractController implements AlertUserController
{
    #[Route('/show-imc-alert', name: 'show_imc_alert')]
    public function showImcAlert(AlertFeature $alertFeature)
    {
        $levelAlert = $alertFeature->getImcAlert();

        switch ($levelAlert->getCode()) 
        {
            case LevelAlert::BALANCE_WELL:
                // $message = "Votre IMC et votre poids sont au top!";
                $message = new TranslatableMessage('alert.imc.balance_well', [], 'alert');
                break;

            case LevelAlert::BALANCE_LACK:
                // $message = "Votre IMC et votre poids sont bas, augmentez vos apports caloriques";
                $message = new TranslatableMessage('alert.imc.balance_lack', ['imc_ideal' => (int)round($this->getUser()->getIdealImc())], 'alert');
                break;

            case LevelAlert::BALANCE_VERY_LACK:
                // $message = "Votre IMC et votre poids sont beaucoup trop bas, augmentez vos apports caloriques";
                $message = new TranslatableMessage('alert.imc.balance_very_lack', ['imc_ideal' => (int)round($this->getUser()->getIdealImc())], 'alert');
                break;

            case LevelAlert::BALANCE_CRITICAL_LACK:
                // $message = "Votre IMC et votre poids sont critiquement bas, il faut manger!!";
                $message = new TranslatableMessage('alert.imc.balance_critical_lack', ['imc_ideal' => (int)round($this->getUser()->getIdealImc())], 'alert');
                break;

            case LevelAlert::BALANCE_EXCESS:
                // $message = "Votre IMC et votre poids sont élevés, diminuez vos apports caloriques";
                $message = new TranslatableMessage('alert.imc.balance_excess', ['imc_ideal' => (int)round($this->getUser()->getIdealImc())], 'alert');
                break;

            case LevelAlert::BALANCE_VERY_EXCESS:
                // $message = "Votre IMC et votre poids sont beaucoup trop élevés, diminuez vos apports caloriques";
                $message = new TranslatableMessage('alert.imc.balance_very_excess', ['imc_ideal' => (int)round($this->getUser()->getIdealImc())], 'alert');
                break;

            case LevelAlert::BALANCE_CRITICAL_EXCESS:
                // $message = "Votre IMC et votre poids sont beaucoup critiquement élevés, il faut manger moins!!";
                $message = new TranslatableMessage('alert.imc.balance_critical_excess', ['imc_ideal' => (int)round($this->getUser()->getIdealImc())], 'alert');
                break;
        }

        return $this->render('balance_sheet/_imc_weight_alert.html.twig', [
            'levelAlert' => $levelAlert,
            'message' => $message ?? null,
        ]);
    }

    #[Route('/show-weight-alert', name: 'show_weight_alert')]
    public function showWeightAlert(AlertFeature $alertFeature)
    {
        $levelAlert = $alertFeature->getWeightAlert();

        switch ($levelAlert->getCode()) 
        {
            case LevelAlert::BALANCE_WELL:
                $message = new TranslatableMessage('alert.weight.balance_well', [], 'alert');

                break;

            case LevelAlert::BALANCE_LACK:
                $message = new TranslatableMessage('alert.weight.balance_lack', ['weight_ideal' => $this->getUser()->getIdealWeight()], 'alert');

                break;

            case LevelAlert::BALANCE_VERY_LACK:
                $message = new TranslatableMessage('alert.weight.balance_very_lack', ['weight_ideal' => $this->getUser()->getIdealWeight()], 'alert');

                break;

            case LevelAlert::BALANCE_CRITICAL_LACK:
                $message = new TranslatableMessage('alert.weight.balance_critical_lack', ['weight_ideal' => round($this->getUser()->getIdealWeight())], 'alert');

                break;

            case LevelAlert::BALANCE_EXCESS:
                $message = new TranslatableMessage('alert.weight.balance_excess', ['weight_ideal' => round($this->getUser()->getIdealWeight())], 'alert');

                break;

            case LevelAlert::BALANCE_VERY_EXCESS:
                $message = new TranslatableMessage('alert.weight.balance_very_excess', ['weight_ideal' => round($this->getUser()->getIdealWeight())], 'alert');

                break;

            case LevelAlert::BALANCE_CRITICAL_EXCESS:
                $message = new TranslatableMessage('alert.weight.balance_critical_excess', ['weight_ideal' => round($this->getUser()->getIdealWeight())], 'alert');

                break;
        }

        return $this->render('balance_sheet/_imc_weight_alert.html.twig', [
            'levelAlert' => $levelAlert,
            'message' => $message ?? null,
        ]);
    }

    #[Route('/show-average-data', name: 'show_average_data')]
    public function averageData(Request $request, BalanceSheetFeature $balanceSheetFeature, AlertFeature $alertFeature)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();

        if($request->query->get('start') && $request->query->get('end')) {
            $start = $request->query->get('start');
            $end = $request->query->get('end');
            $averageDailyEnergy = $balanceSheetFeature->averageDailyEnergyForAPeriod($start, $end);
            if(!$averageDailyEnergy) {
                $start = new \DateTime($start);
                $end = new \DateTime($end);
                $params = [
                    'start_date' => $start->format('d M Y'),
                    'end_date' => $end->format('d M Y'),
                    'no_meals' => true,
                ];
            }else{
                $averageDailyNutrient = $balanceSheetFeature->averageDailyNutrientForAPeriod($start, $end);
                $averageDailyNutrientRecommended = [
                    'protein' => $user->getProtein(),
                    'lipid' => $user->getLipid(),
                    'carbohydrate' => $user->getCarbohydrate(),
                    'sodium' => $user->getSodium(),
                ];
                $averageDailyFgp = $balanceSheetFeature->averageDailyFgpForAPeriod($start, $end);
                $balanceSheetAlerts = $alertFeature->getBalanceSheetAlerts($averageDailyEnergy, $averageDailyNutrient, $averageDailyFgp);
                $start = new \DateTime($start);
                $end = new \DateTime($end);
                $params = [
                    'start_date' => $start->format('d M Y'),
                    'end_date' => $end->format('d M Y'),
                    'average_energy' => $averageDailyEnergy ?? 0,
                    'average_nutrient' => $averageDailyNutrient ?? 0,
                    'average_nutrient_recommended' => $averageDailyNutrientRecommended,
                    'average_fgp' => $averageDailyFgp ?? 0,
                    'average_fgp_recommended' => $user->getRecommendedQuantities(),
                    'balance_sheet_alerts' => $balanceSheetAlerts,
                ];
            }
        }

        if(isset($balanceSheetAlerts) && $balanceSheetAlerts instanceof Response) {
            return $balanceSheetAlerts;
        }

        if($request->query->get('ajax')) {
            return $this->render('balance_sheet/_content.html.twig', $params);
        }
        
        return $this->render('balance_sheet/index.html.twig', $params);
    }

    // #[Route('/show-weight-imc-energy', name: 'show_weight_imc_energy')]
    // public function weightImcEnergy(Request $request, BalanceSheetFeature $balanceSheetFeature, AlertFeature $alertFeature)
    // {
    //     return $this->render('balance_sheet/_weight_imc_energy.html.twig', [
    //         'balanceWeightEnergyAndImcAlerts' => $alertFeature->getWeightEnergyAndImcBalanceAlerts(),
    //     ]);
    // }

    #[Route('/message-details-alert-energy/{energy?}', name: 'message_details_alert_energy', methods: ['GET'])]
	public function messageDetailsAlertEnergy(Request $request, AlertFeature $alertFeature, ?int $energy)
	{
		$session = $request->getSession();
		$mealDayEnergy = null === $energy ? $session->get('_meal_day_energy') : $energy;
		
		$remainingMealDayEnergy = abs(round($this->getUser()->getEnergy() - $mealDayEnergy));
		$alert = $alertFeature->isWellBalanced($mealDayEnergy, $this->getUser()->getEnergy());

		if(LevelAlert::BALANCE_WELL === $alert->getCode()) {
			$title = "Super, vous êtes bon !";
			$message = "Votre consommation calorique est bonne";
		} elseif(LevelAlert::BALANCE_LACK === $alert->getCode()
				||
				LevelAlert::BALANCE_VERY_LACK === $alert->getCode()
				||
				LevelAlert::BALANCE_CRITICAL_LACK === $alert->getCode()
		){
			$title = "Consommez plus !";
			$message = "Vous devriez consommer environ $remainingMealDayEnergy Kcal supplémentaire";
		} else {
			$title = "Consommez moins !";
			$message = "Vous dépassez d'environ $remainingMealDayEnergy Kcal nos recommendations";
		}

		return $this->render('balance_sheet/_message_details_alert.html.twig', [
					'title' => $title,
			      'message' => $message ?? null,
		]);
	}

    #[Route('/message-details-alert-fgp/{fgpAlias}/{quantity}/{alertCode}', name: 'message_details_alert_fgp', methods: ['GET'])]
    public function messageDetailsAlertFgp(Request $request, FoodGroupParentRepository $foodGroupParentRepository, string $fgpAlias, int $quantity, string $alertCode)
    {
        $user = $this->getUser();
  
        $fgp = $foodGroupParentRepository->findOneByAlias($fgpAlias);
		$remainingDayQuantity = abs(round($user->getRecommendedQuantities()[$fgpAlias] - $quantity));
 
		// if(LevelAlert::BALANCE_WELL === $alertCode) {
		// 	$title = "Super, vous êtes bon en {$fgp->getName()} !";
		// 	$message = "Votre consommation quotidiennement de {$fgp->getName()} est bonne";
		// } elseif(LevelAlert::BALANCE_LACK === $alertCode
		// 		||
		// 		LevelAlert::BALANCE_VERY_LACK === $alertCode
		// 		||
		// 		LevelAlert::BALANCE_CRITICAL_LACK === $alertCode
		// ){
		// 	$title = "Consommez plus de {$fgp->getName()} !";
		// 	$message = "Vous devriez consommer quotidiennement environ $remainingDayQuantity g supplémentaire";
		// } else {
		// 	$title = "Consommez moins de {$fgp->getName()} !";
		// 	$message = "Vous consommez quotidiennement $remainingDayQuantity g en trop";
		// }
        $title= '';
        $message = '';

        switch ($alertCode) 
        {
            case LevelAlert::BALANCE_WELL:
                $title = "Super, vous êtes bon en {$fgp->getName()} !";
			    $message = "Votre consommation quotidiennement de {$fgp->getName()} est bonne";
                break;

            case LevelAlert::BALANCE_LACK:
                $title = "Consommez plus de {$fgp->getName()} !";
			    $message = "Consommez-en quotidiennement environ $remainingDayQuantity g supplémentaire";
                break;

            case LevelAlert::BALANCE_VERY_LACK:
                $title = "Votre consommation de {$fgp->getName()} est trop faible!";
			    $message = "Consommez-en quotidiennement environ $remainingDayQuantity g supplémentaire";
                break;

            case LevelAlert::BALANCE_CRITICAL_LACK:
                $title = "Votre consommation de {$fgp->getName()} est beaucoup trop faible!";
			    $message = "Mangez-en beaucoup plus! ($remainingDayQuantity g supplémentaire)";
                break;

            case LevelAlert::BALANCE_EXCESS:
                $title = "Consommez moins de {$fgp->getName()} !";
			    $message = "Vous consommez quotidiennement $remainingDayQuantity g en trop";
                break;

            case LevelAlert::BALANCE_VERY_EXCESS:
                $title = "Votre consommation de {$fgp->getName()} est trop importante!";
			    $message = "Baissez votre consommation d'environ $remainingDayQuantity g";
                break;

            case LevelAlert::BALANCE_CRITICAL_EXCESS:
                $title = "Votre consommation de {$fgp->getName()} est beaucoup trop importante!";
			    $message = "Mangez-en beaucoup moins! ($remainingDayQuantity g de moins)";
                break;
        }

		return $this->render('balance_sheet/_message_details_alert.html.twig', [
					'title' => $title,
			      'message' => $message ?? null,
		]);
    }

    #[Route('/message-details-alert-nutrient/{nutrient}/{quantity}/{alertCode}', name: 'message_details_alert_nutrient', methods: ['GET'])]
    public function messageDetailsAlertNutrient(Request $request, string $nutrient, int $quantity, string $alertCode)
    {
        $user = $this->getUser();

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $quantityDayRecommended = (int)$propertyAccessor->getValue($this->getUser(), $nutrient);
		$remainingDayQuantity = abs(round($quantity - $quantityDayRecommended));

        $title= '';
        $message = '';

        switch ($alertCode) 
        {
            case LevelAlert::BALANCE_WELL:
                $title = "Super, vous êtes bon en {$nutrient} !";
			    $message = "Votre consommation quotidiennement de {$nutrient} est bonne";
                break;

            case LevelAlert::BALANCE_LACK:
                $title = "Consommez plus de {$nutrient} !";
			    $message = "Consommez-en quotidiennement environ $remainingDayQuantity g supplémentaire";
                break;

            case LevelAlert::BALANCE_VERY_LACK:
                $title = "Votre consommation de {$nutrient} est trop faible!";
			    $message = "Consommez-en quotidiennement environ $remainingDayQuantity g supplémentaire";
                break;

            case LevelAlert::BALANCE_CRITICAL_LACK:
                $title = "Votre consommation de {$nutrient} est beaucoup trop faible!";
			    $message = "Mangez-en beaucoup plus! ($remainingDayQuantity g supplémentaire)";
                break;

            case LevelAlert::BALANCE_EXCESS:
                $title = "Consommez moins de {$nutrient} !";
			    $message = "Vous consommez quotidiennement $remainingDayQuantity g en trop";
                break;

            case LevelAlert::BALANCE_VERY_EXCESS:
                $title = "Votre consommation de {$nutrient} est trop importante!";
			    $message = "Baissez votre consommation d'environ $remainingDayQuantity g";
                break;

            case LevelAlert::BALANCE_CRITICAL_EXCESS:
                $title = "Votre consommation de {$nutrient} est beaucoup trop importante!";
			    $message = "Mangez-en beaucoup moins! ($remainingDayQuantity g de moins)";
                break;
        }

		return $this->render('balance_sheet/_message_details_alert.html.twig', [
					'title' => $title,
			      'message' => $message ?? null,
		]);
    }
}