<?php

namespace App\Controller;

use App\Repository\MealRepository;
use App\Service\AlertFeature;
use App\Service\DashboardUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * DashboardController.php
 * 
 * Contrôleur responsable du dashboard.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 */
class DashboardController extends AbstractController
{
    /**
     * Page principale du dashboard utilisateur.
     *
     * Vérifie que l'utilisateur est authentifié, supprime les sessions temporaires
     * de recette si elles existent, et redirige vers le profil si le profil n'a pas
     * encore été complété pour la première fois.
     *
     * @param Request $request La requête HTTP courante
     * @param DashboardUtils $dashboardUtils Service utilitaire pour récupérer les infos du dashboard
     * @param MealRepository $mealRepository Repository pour vérifier les repas du jour
     * @param AlertFeature $alertFeature Service pour gérer les alertes (non utilisé ici mais injecté)
     *
     * @return \Symfony\Component\HttpFoundation\Response|RedirectResponse
     */
    #[Route('/dashboard', name: 'app_dashboard_index', methods: ['GET'])]
    public function index(Request $request, DashboardUtils $dashboardUtils, MealRepository $mealRepository)
    {
        // Empêche l'accès aux utilisateurs non authentifiés
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser(); // Récupère l'utilisateur connecté

        // Si l'utilisateur a une recette en cours dans la session, la supprime
        if($request->getSession()->has('recipe_dish')) {
            $request->getSession()->remove('recipe_dish');
            $request->getSession()->remove('recipe_foods');
        }

        // Redirige l'utilisateur vers la page de complétion du profil si ce n'est pas fait
        if(!$user->hasFirstFillProfile())
        {
            return $this->redirectToRoute('app_profile_edit');
        }

        // Rend le template dashboard avec :
        // - 'principals' : informations principales du dashboard
        // - 'secondary' : informations secondaires du dashboard
        // - 'countMealsDay' : nombre de repas enregistrés pour aujourd'hui
        return $this->render('dashboard/index.html.twig', [
            'principals' => $dashboardUtils->getPrincipal(),
            'secondary' => $dashboardUtils->getSecondary(),
            'countMealsDay' => $mealRepository->hasMealsToday($this->getUser()),
        ]);
    }
}