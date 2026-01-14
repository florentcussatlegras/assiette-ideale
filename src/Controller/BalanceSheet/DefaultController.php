<?php

namespace App\Controller\BalanceSheet;

use App\Service\DishUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;
use App\Entity\Alert\LevelAlert;
use App\Repository\FoodGroupParentRepository;
use App\Controller\AlertUserController;
use App\Service\BalanceSheetFeature;
use App\Service\AlertFeature;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/balance_sheet', name: 'app_balance_sheet_')]
class DefaultController extends AbstractController implements AlertUserController
{
    #[Route('/favorite-dish', name: 'favorite_dish')]
    public function favoriteDish(Request $request, BalanceSheetFeature $balanceSheetFeature)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        if($request->query->get('start') && $request->query->get('end')) {
            $start = $request->query->get('start');
            $end = $request->query->get('end');

            $item = $balanceSheetFeature->getFavoriteDishPerPeriod($start, $end);
        }

        return $this->render('balance_sheet/_favorite_item.html.twig', [
            'item' => $item ?? null,
            'type' => 'dish',
        ]);
    }

    #[Route('/favorite-food', name: 'favorite_food')]
    public function favoriteFood(Request $request, BalanceSheetFeature $balanceSheetFeature)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        if($request->query->get('start') && $request->query->get('end')) {
            $start = $request->query->get('start');
            $end = $request->query->get('end');

            $item = $balanceSheetFeature->getFavoriteFoodPerPeriod($start, $end);
        }

        return $this->render('balance_sheet/_favorite_item.html.twig', [
            'item' => $item ?? null,
            'type' => 'food',
        ]);
    }

    #[Route('/most-caloric-meal', name: 'most_caloric_meal')]
    public function mostCaloricMeal(Request $request, BalanceSheetFeature $balanceSheetFeature)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        if($request->query->get('start') && $request->query->get('end')) {
            $start = $request->query->get('start');
            $end = $request->query->get('end');

            $meal = $balanceSheetFeature->getMostCaloricPerPeriod($start, $end);
        }

        return $this->render('balance_sheet/_most_caloric_meal.html.twig', [
            'meal' => $meal ?? null,
        ]);
    }
 
    #[Route('/{start?}/{end?}', name: 'index')]
    public function index(Request $request, ?string $start, ?string $end)
    {
        // $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        if($start) {
            $start = \DateTime::createFromFormat('Y-m-d', $start);
            // $start = $start->format('Y-m-d');
        }else{
            $start = new \DateTime('-1 day');
        }
        $start = $start->format('m/d/Y');

        if($end) {
            $end = \DateTime::createFromFormat('Y-m-d', $end);
            // $end = $end->format('Y-m-d');
        }else{
            $end = new \DateTime('-1 day');
        }
        $end = $end->format('m/d/Y');
        
        return $this->render('balance_sheet/index.html.twig', [
            'start' => $start,
            'end' => $end,
        ]);
    }
}