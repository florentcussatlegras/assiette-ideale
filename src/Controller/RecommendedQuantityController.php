<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/recommended-quantity', name: 'app_recommended_quantity_')]
class RecommendedQuantityController extends AbstractController
{

    #[Route('/', name: 'index')]
    public function index()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return new Response('test depr');
    }

    #[Route('/edit', name: 'edit')]
    public function edit(EntityManagerInterface $manager)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();

        $recommendedQuantities = $user->getRecommendedQuantities();
        $recommendedQuantities['FGP_CONDIMENT'] = 200;

        $user->setRecommendedQuantities($recommendedQuantities);

        $manager->persist($user);
        $manager->flush();

        return new Response('Quantités mises à jour');
    }
}