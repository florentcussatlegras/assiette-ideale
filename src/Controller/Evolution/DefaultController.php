<?php

namespace App\Controller\Evolution;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/evolution', name: 'app_evolution_')]
class DefaultController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        if($request->query->has('start')) {
            $start = \DateTime::createFromFormat('Y-m-d', $request->query->get('start'));
        }else{
            $start = new \DateTime('-1 day');
        }

        if($request->query->has('end')) {
            $end = \DateTime::createFromFormat('Y-m-d', $request->query->get('end'));
        }else{
            $end = new \DateTime('-1 day');
        }
        $start = $start->format('Y-m-d');
        $end = $end->format('Y-m-d');

        return $this->render('evolution/index.html.twig', [
            'start' => $start,
            'end' => $end,
        ]);
    }
}
