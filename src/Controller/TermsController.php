<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TermsController extends AbstractController
{
    #[Route('/terms', name: 'app_terms', methods: 'GET')]
    public function index(): Response
    {
        // Affiche le template HTML de la page Termes et Conditions
        return $this->render('terms/index.html.twig');
    }
}
