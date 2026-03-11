<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// Dépréciation : cette classe est remplacée par RecommendationController
trigger_deprecation(
    'florent/liveforeat',
    '3.0',
    'The "%s" class is deprecated, use "%s" instead',
    RecommendedQuantityController::class,
    RecommendationController::class
);

/**
 * RecommendationController.php
 * 
 * @deprecated since liveforeat 3.0
 * Use RecommendationController instead
 *
 * Contrôleur pour gérer les quantités recommandées (ancienne version)
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale 
 */
#[Route('/recommended-quantity', name: 'app_recommended_quantity_')]
class RecommendedQuantityController extends AbstractController
{
    /**
     * Page de test ou index pour les quantités recommandées.
     *
     * @return Response
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        // Vérifie que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        // Ici c'est juste un test pour la dépréciation
        return new Response('test depr');
        // Pour l'ancien rendu réel :
        // return $this->render('recommendations/index.html.twig');
    }

    /**
     * Modifie les quantités recommandées pour l'utilisateur.
     *
     * @param EntityManagerInterface $manager
     *
     * @return Response
     */
    #[Route('/edit', name: 'edit', methods: ['POST'])]
    public function edit(EntityManagerInterface $manager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var User $user */
        $user = $this->getUser();

        // Récupère les quantités recommandées existantes
        $recommendedQuantities = $user->getRecommendedQuantities();

        // Ajoute/modifie la quantité pour les condiments
        $recommendedQuantities['FGP_CONDIMENT'] = 200;

        // Enregistre la mise à jour dans l'entité User
        $user->setRecommendedQuantities($recommendedQuantities);

        // Persiste les changements dans la base de données
        $manager->persist($user);
        $manager->flush();

        return new Response('Quantités mises à jour');
    }
}