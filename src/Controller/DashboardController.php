<?php

namespace App\Controller;

use App\Entity\Alert\LevelAlert;
use App\Repository\MealRepository;
use App\Service\AlertFeature;
use App\Service\DashboardUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard_index', methods: ['GET'])]
    public function index(Request $request, DashboardUtils $dashboardUtils, MealRepository $mealRepository, AlertFeature $alertFeature)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if($request->getSession()->has('recipe_dish')) {
            $request->getSession()->remove('recipe_dish');
            $request->getSession()->remove('recipe_foods');
        }

        if(!$user->hasFirstFillProfile())
        {
            return $this->redirectToRoute('app_profile_edit');
        }

        return $this->render('dashboard/index.html.twig', [
            'principals' => $dashboardUtils->getPrincipal(),
            'secondary' => $dashboardUtils->getSecondary(),
            'countMealsDay' => $mealRepository->hasMealsToday($this->getUser()),
        ]);
    }
}