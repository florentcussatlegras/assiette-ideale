<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

trigger_deprecation('florent/liveforeat', '3.0', 'The "%s" class is deprecated, use "%s" instead', RecommendedQuantityController::class, RecommendationController::class);

/**
 * @deprecated since liveforeat3.0, use RecommendationController class instead
 */

#[Route('/recommended-quantity', name: 'app_recommended_quantity_')]
class RecommendedQuantityController extends AbstractController
{

    #[Route('/', name: 'index')]
    public function index()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        // return $this->render('recommendations/index.html.twig');
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