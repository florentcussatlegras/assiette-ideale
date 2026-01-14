<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SitemapController extends AbstractController
{
    /**
     * @Route("/sitemap", name="app_sitemap", format="xml")
     */
    public function index(Request $request): Response
    {
        dd($request->getSchemeAndHttpHost());
    }
}