<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * HelpController.php
 *
 * Gère l'affichage de la page d'aide.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class HelpController extends AbstractController
{
    /**
     * Affiche la page d'aide aux utilisateurs.
     *
     * @param Request $request
     * 
     * @return Response
     */
    #[Route('/help', name: 'app_help', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Rendu du template Twig pour la page d'aide
        return $this->render('help/index.html.twig');
    }
}