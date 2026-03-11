<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * TermsController.php
 *
 * Affiche la page "Termes et Conditions" du site.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale 
 */
class TermsController extends AbstractController
{
    /**
     * Page principale des Termes et Conditions.
     *
     * @return Response
     */
    #[Route('/terms', name: 'app_terms', methods: ['GET'])]
    public function index(): Response
    {
        // Rendu du template Twig correspondant à la page Termes et Conditions
        return $this->render('terms/index.html.twig');
    }
}