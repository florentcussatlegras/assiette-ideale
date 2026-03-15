<?php

namespace App\Controller;

use App\Repository\DietRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * DietController.php
 * 
 * Contrôleur responsable de la gestion des régimes alimentaires.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 * 
 */
#[Route('/diets')]
class DietController extends AbstractController
{
    /**
     * Affiche la liste des régimes alimentaires.
     *
     * @param DietRepository $dietRepository Repository permettant d'accéder aux entités Diet
     *
     * @return Response Réponse HTTP contenant le rendu du template Twig
     */
    #[Route('/', name: 'app_diets_index', methods: ['GET'])]
    public function index(DietRepository $dietRepository): Response
    {
        /**
         * Récupère tous les régimes alimentaires présents en base
         *
         * @var Diet[] $diets
         */
        $diets = $dietRepository->findAll();

        return $this->render('diet/index.html.twig', [
            'diets' => $diets
        ]);
    }

    /**
     * Active ou désactive l'affichage des aliments interdits liés aux régimes de l'utilisateur.
     *
     * @param string $value variable contenant la valeur envoyée par le toggle 'true' ou 'false'
     * @param EntityManagerInterface $em Gestionnaire Doctrine permettant de persister les modifications
     *
     * @return JsonResponse Réponse JSON indiquant le succès de l'opération
     */
    #[Route(
        '/toggle-diet-foods/{value?}', 
        name: 'app_diets_toggle_visibility_diet_items', 
        methods: ['GET'],
        requirements: ['value' => 'true|false'] // $value doit être false ou true
    )]
    public function toggleVisibilityDietFoods(string $value = 'false', EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
    
        // Convertit la string "true" ou "false" en bool
        $user->setShowDietFoods($value === 'true');
    
        $em->flush();
    
        return new JsonResponse(['success' => true]);
    }

    /**
     * Active ou désactive l'affichage des aliments explicitement interdits par l'utilisateur.
     *
     * @param string $value variable contenant la valeur envoyée par le toggle 'true' ou 'false'
     * @param EntityManagerInterface $em Gestionnaire Doctrine pour appliquer les modifications
     *
     * @return JsonResponse Confirmation de mise à jour
     */
    #[Route(
        '/toggle-forbidden-foods/{value?}', 
        name: 'app_diets_toggle_visibility_forbidden_items', 
        methods: ['GET'],
        requirements: ['value' => 'true|false'] // $value doit être false ou true
    )]
    public function toggleVisibilityForbiddenFoods(string $value = 'false', EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
    
        // Convertit la string "false" ou "true" en bool
        $user->setShowForbiddenFoods($value === 'true');
    
        $em->flush();
    
        return new JsonResponse(['success' => true]);
    }
}
