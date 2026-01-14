<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TrainingController extends AbstractController
{
    /**
     * @Route("/apikernel")
     */
    public function apikernel()
    {
        return new Response('api kernel');
    }
}