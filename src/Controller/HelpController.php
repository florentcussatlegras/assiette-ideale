<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HelpController extends AbstractController
{
    #[Route('/help', name: 'app_help', methods: ['GET'])]
    public function index(Request $request): Response
    {
        return $this->render('help/index.html.twig');
    }
}