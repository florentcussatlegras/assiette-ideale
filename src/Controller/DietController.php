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
}
